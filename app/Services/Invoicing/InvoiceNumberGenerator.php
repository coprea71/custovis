<?php

namespace App\Services\Invoicing;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Consecutive invoice numbers per calendar year (§ 14 Abs. 4 Nr. 4 UStG),
 * e.g. RE-2026-00001. The counter row is locked so two parallel issues can
 * never receive the same number.
 */
class InvoiceNumberGenerator
{
    public function __construct(private InvoiceSettings $settings) {}

    /**
     * Must run inside the transaction that stores the issued invoice, so a
     * failed issue rolls the counter back and leaves no gap.
     */
    public function next(int $year): string
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Invoice numbers must be drawn inside a transaction.');
        }

        $key = 'invoicing.counter.'.$year;
        Setting::query()->firstOrCreate(['key' => $key], ['value' => '0']);

        $counter = Setting::query()->where('key', $key)->lockForUpdate()->firstOrFail();
        $next = (int) $counter->value + 1;
        $counter->update(['value' => (string) $next]);

        return $this->settings->get('number_prefix').$year.'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
