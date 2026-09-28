<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Spam tickets exist only for the admin spam folder: hiding them globally
 * keeps them out of every list, dashboard, portal view and SLA check
 * without each query having to remember it.
 */
class NotSpamScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNull($model->qualifyColumn('spam_at'));
    }
}
