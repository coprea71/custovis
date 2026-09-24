<?php

namespace App\Models\Scopes;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Hard ownership filter for the self-service portal (10.md): a customer
 * only ever sees tickets linked to their account, or unlinked tickets
 * they requested with their account's e-mail (e.g. created by mail before
 * the account existed). Registered by ScopeTicketsToCustomer for portal
 * requests only, so agent views are unaffected.
 */
class CustomerOwnedScope implements Scope
{
    public function __construct(private readonly Customer $customer) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where(fn (Builder $query) => $query
            ->where($model->qualifyColumn('customer_id'), $this->customer->id)
            ->orWhere(fn (Builder $byEmail) => $byEmail
                ->whereNull($model->qualifyColumn('customer_id'))
                ->where($model->qualifyColumn('requester_email'), $this->customer->email)));
    }
}
