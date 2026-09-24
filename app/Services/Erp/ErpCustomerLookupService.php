<?php

namespace App\Services\Erp;

use App\Models\AuditLog;
use App\Models\ErpConnection;
use App\Models\User;
use App\Services\Erp\Adapters\OdooAdapter;
use App\Services\Erp\Adapters\ShopwareAdapter;
use App\Services\Erp\Contracts\CustomerLookupInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * On-demand customer lookup against a team's ERP connection. Results live
 * only in the cache for a few minutes and are never persisted.
 */
class ErpCustomerLookupService
{
    public const CACHE_TTL_SECONDS = 300;

    public const FAILURE_THRESHOLD = 3;

    public const DOWN_SECONDS = 300;

    /**
     * @return array<string, ?string>|null label => value of the released fields, null when not found
     *
     * @throws ErpLookupException when the ERP is unreachable or the circuit is open
     */
    public function lookup(ErpConnection $connection, string $reference, User $user): ?array
    {
        $reference = mb_strtolower(trim($reference));
        $cacheKey = "erp_customer:{$connection->id}:{$reference}";
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            $this->audit($connection, $user, $reference, 'cache');

            return $cached['fields'];
        }

        if ($this->isDown($connection)) {
            throw new ErpLookupException('ERP connection is temporarily marked as down.');
        }

        $this->audit($connection, $user, $reference, 'live');
        $fields = $this->fetch($connection, $reference);
        Cache::put($cacheKey, ['fields' => $fields], self::CACHE_TTL_SECONDS);

        return $fields;
    }

    public function isDown(ErpConnection $connection): bool
    {
        return Cache::has($this->downKey($connection));
    }

    private function fetch(ErpConnection $connection, string $reference): ?array
    {
        try {
            $customer = $this->adapterFor($connection)->findCustomer($reference);
        } catch (ConnectionException|ErpLookupException $e) {
            $this->recordFailure($connection);
            // Only the exception class is logged: messages may echo remote payloads.
            Log::warning('ERP customer lookup failed.', ['erp_connection_id' => $connection->id, 'error' => $e::class]);

            throw new ErpLookupException('ERP customer lookup failed.', previous: $e);
        }

        Cache::forget($this->failureKey($connection));

        return $customer?->mapped($connection->field_mapping ?? []);
    }

    private function adapterFor(ErpConnection $connection): CustomerLookupInterface
    {
        return match ($connection->type) {
            ErpConnection::TYPE_ODOO => new OdooAdapter($connection),
            ErpConnection::TYPE_SHOPWARE => new ShopwareAdapter($connection),
            default => throw new ErpLookupException('Unsupported ERP type.'),
        };
    }

    private function recordFailure(ErpConnection $connection): void
    {
        $key = $this->failureKey($connection);
        Cache::add($key, 0, self::DOWN_SECONDS);

        if (Cache::increment($key) >= self::FAILURE_THRESHOLD) {
            Cache::put($this->downKey($connection), true, self::DOWN_SECONDS);
            Cache::forget($key);
        }
    }

    private function audit(ErpConnection $connection, User $user, string $reference, string $source): void
    {
        AuditLog::record('erp.customer.lookup', $user, $connection->team, $connection, [
            'erp_connection_id' => $connection->id,
            'erp_type' => $connection->type,
            'customer_reference' => $reference,
            'source' => $source,
        ]);
    }

    private function failureKey(ErpConnection $connection): string
    {
        return "erp_failures:{$connection->id}";
    }

    private function downKey(ErpConnection $connection): string
    {
        return "erp_down:{$connection->id}";
    }
}
