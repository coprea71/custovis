<?php

namespace App\Services\Erp\Adapters;

use App\DataTransferObjects\CustomerDto;
use App\Models\ErpConnection;
use App\Services\Erp\Contracts\CustomerLookupInterface;
use App\Services\Erp\ErpLookupException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class HttpAdapter implements CustomerLookupInterface
{
    // Short limits so a slow ERP can never block the ticket UI for long.
    private const TIMEOUT_SECONDS = 5;

    private const CONNECT_TIMEOUT_SECONDS = 3;

    public function __construct(protected ErpConnection $connection) {}

    protected function http(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout(self::TIMEOUT_SECONDS)
            ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS);
    }

    /**
     * Only the released fields (plus the record id) are requested remotely.
     *
     * @return list<string>
     */
    protected function requestedFields(): array
    {
        return array_values(array_unique([...$this->connection->mappedFields(), 'id']));
    }

    /**
     * Turns the first record of a remote result list into a DTO.
     */
    protected function firstRecord(mixed $records, string $system): ?CustomerDto
    {
        if (! is_array($records) || ! array_is_list($records)) {
            throw new ErpLookupException("Unexpected {$system} response.");
        }

        if ($records === []) {
            return null;
        }

        if (! is_array($records[0])) {
            throw new ErpLookupException("Unexpected {$system} record.");
        }

        return new CustomerDto((string) ($records[0]['id'] ?? ''), $records[0]);
    }
}
