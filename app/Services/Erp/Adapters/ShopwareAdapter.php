<?php

namespace App\Services\Erp\Adapters;

use App\DataTransferObjects\CustomerDto;
use App\Services\Erp\ErpLookupException;

/**
 * Shopware 6 Admin API with OAuth2 client credentials of a read-only
 * integration. Orders are deliberately not loaded (data minimisation).
 */
class ShopwareAdapter extends HttpAdapter
{
    public function findCustomer(string $reference): ?CustomerDto
    {
        $response = $this->http()
            ->withToken($this->accessToken())
            ->post($this->connection->endpoint('api/search/customer'), [
                'limit' => 1,
                'filter' => [[
                    'type' => 'multi',
                    'operator' => 'or',
                    'queries' => [
                        ['type' => 'equals', 'field' => 'email', 'value' => $reference],
                        ['type' => 'equals', 'field' => 'customerNumber', 'value' => $reference],
                    ],
                ]],
                'includes' => ['customer' => $this->requestedFields()],
            ]);

        if ($response->failed()) {
            throw new ErpLookupException('Shopware customer search failed.');
        }

        return $this->firstRecord($response->json('data'), 'Shopware');
    }

    private function accessToken(): string
    {
        $response = $this->http()->post($this->connection->endpoint('api/oauth/token'), [
            'grant_type' => 'client_credentials',
            'client_id' => $this->connection->credential('client_id'),
            'client_secret' => $this->connection->credential('client_secret'),
        ]);

        $token = $response->successful() ? $response->json('access_token') : null;

        if (! is_string($token) || $token === '') {
            throw new ErpLookupException('Shopware authentication failed.');
        }

        return $token;
    }
}
