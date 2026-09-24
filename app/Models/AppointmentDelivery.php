<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentDelivery extends Model
{
    protected $fillable = [
        'appointment_id',
        'delivery_note_number',
        'items_text',
        'recipient_name',
        'recipient_email',
        'recipient_phone',
        'delivered_at',
        'pdf_attachment_id',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ServiceAppointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(ServiceAppointment::class, 'appointment_id');
    }

    /**
     * @return BelongsTo<TicketAttachment, $this>
     */
    public function pdfAttachment(): BelongsTo
    {
        return $this->belongsTo(TicketAttachment::class, 'pdf_attachment_id');
    }
}
