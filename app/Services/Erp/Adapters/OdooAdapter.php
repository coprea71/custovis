<?php

namespace App\Services\Erp\Adapters;

use App\DataTransferObjects\CustomerDto;
use App\Services\Erp\ErpLookupException;

/**
 * Odoo external API via the built-in JSON-RPC endpoint (/jsonrpc) using the
 * API key of a read-only integration user.
 */
class OdooAdapter extends HttpAdapter
{
    public function findCustomer(string $reference): ?CustomerDto
    {
        $database = $this->connection->credential('database');
        $apiKey = $this->connection->credential('api_key');

        $uid = $this->call('common', 'login', [$database, $this->connection->credential('login'), $apiKey]);

        if (! is_int($uid) || $uid <= 0) {
            throw new ErpLookupException('Odoo authentication failed.');
        }

        $records = $this->call('object', 'execute_kw', [
            $database, $uid, $apiKey, 'res.partner', 'search_read',
            [['|', ['email', '=ilike', $this->escapeLike($reference)], ['ref', '=', $reference]]],
            ['fields' => $this->requestedFields(), 'limit' => 1],
        ]);

        return $this->firstRecord($records, 'Odoo');
    }

    private function call(string $service, string $method, array $args): mixed
    {
        $response = $this->http()->post($this->connection->endpoint('jsonrpc'), [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'params' => ['service' => $service, 'method' => $method, 'args' => $args],
            'id' => 1,
        ]);

        if ($response->failed() || $response->json('error') !== null) {
            throw new ErpLookupException('Odoo request failed.');
        }

        return $response->json('result');
    }

    // "=ilike" treats % and _ as wildcards; an e-mail must match literally.
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
