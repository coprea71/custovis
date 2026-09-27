<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Invoice extends Model
{
    public const TYPE_INVOICE = 'invoice';

    public const TYPE_CANCELLATION = 'cancellation';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Only these may still change once an invoice is issued (GoBD: the
     * document itself is final, only its processing state moves on).
     */
    private const MUTABLE_AFTER_ISSUE = ['status', 'sent_at', 'paid_at', 'xml_path', 'pdf_path', 'updated_at'];

    protected $attributes = ['type' => self::TYPE_INVOICE, 'status' => self::STATUS_DRAFT];

    protected $fillable = [
        'type', 'status', 'cancels_invoice_id', 'team_id', 'customer_id', 'service_from', 'service_to',
        'tax_rate', 'small_business', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'service_from' => 'date',
            'service_to' => 'date',
            'tax_rate' => 'decimal:2',
            'small_business' => 'boolean',
            'seller' => 'array',
            'buyer' => 'array',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Invoice $invoice) {
            if ($invoice->getOriginal('status') !== self::STATUS_DRAFT
                && array_diff(array_keys($invoice->getDirty()), self::MUTABLE_AFTER_ISSUE) !== []) {
                throw new LogicException('Issued invoices are immutable; cancel and re-issue instead.');
            }
        });

        static::deleting(function (Invoice $invoice) {
            if ($invoice->getOriginal('status') !== self::STATUS_DRAFT) {
                throw new LogicException('Only draft invoices can be deleted.');
            }
        });
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function cancelledInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'cancels_invoice_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCancellation(): bool
    {
        return $this->type === self::TYPE_CANCELLATION;
    }

    /**
     * UNTDID 1001: 380 commercial invoice, 381 credit note (used for cancellations).
     */
    public function documentTypeCode(): string
    {
        return $this->isCancellation() ? '381' : '380';
    }

    public function title(): string
    {
        return $this->isCancellation() ? 'Stornorechnung' : 'Rechnung';
    }

    public function fileBaseName(): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($this->number ?? 'entwurf-'.$this->id));
    }

    public static function money(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' €';
    }
}
