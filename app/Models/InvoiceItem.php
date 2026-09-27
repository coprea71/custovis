<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class InvoiceItem extends Model
{
    public const UNITS = ['HUR' => 'Std.', 'C62' => 'Stk.'];

    protected $fillable = ['position', 'ticket_id', 'description', 'quantity', 'unit_code', 'unit_price_cents', 'net_cents'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price_cents' => 'integer',
            'net_cents' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $guard = function (InvoiceItem $item) {
            if (! $item->invoice->isDraft()) {
                throw new LogicException('Items of an issued invoice are immutable.');
            }
        };

        static::updating($guard);
        static::deleting($guard);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function unitLabel(): string
    {
        return self::UNITS[$this->unit_code] ?? $this->unit_code;
    }
}
