<?php

namespace App\Services\Erp\Contracts;

use App\DataTransferObjects\CustomerDto;
use App\Services\Erp\ErpLookupException;

interface CustomerLookupInterface
{
    /**
     * Looks up a customer by e-mail address or customer number.
     *
     * @throws ErpLookupException when the remote system fails or answers unexpectedly
     */
    public function findCustomer(string $reference): ?CustomerDto;
}
