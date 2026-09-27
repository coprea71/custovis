<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Tickets created before the automatic e-mail matching stay unlinked
// otherwise, so the same sender would appear as customer and non-customer.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tickets')->whereNotNull('requester_email')
            ->update(['requester_email' => DB::raw('LOWER(TRIM(requester_email))')]);

        DB::table('customers')->select('id', 'email')->orderBy('id')->each(
            fn (object $customer) => DB::table('tickets')
                ->whereNull('customer_id')
                ->where('requester_email', $customer->email)
                ->update(['customer_id' => $customer->id])
        );
    }

    public function down(): void
    {
        // Links are data, not schema; undoing them would detach tickets from their customers.
    }
};
