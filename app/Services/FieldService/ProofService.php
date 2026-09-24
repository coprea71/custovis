<?php

namespace App\Services\FieldService;

use App\Models\AppointmentDelivery;
use App\Models\AppointmentSignature;
use App\Models\ServiceAppointment;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Services\AttachmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Signatures and delivery receipts captured in the field PWA (14.md).
 */
class ProofService
{
    private const DISK = 'local';

    private const MAX_SIGNATURE_BYTES = 512 * 1024;

    public function __construct(private readonly AttachmentService $attachments) {}

    /**
     * Only PNG data URLs are accepted and the decoded bytes are checked for
     * the PNG magic number — the payload comes from the (offline) client.
     */
    public function storeSignature(ServiceAppointment $appointment, string $context, string $signerName, string $pngDataUrl): AppointmentSignature
    {
        $binary = base64_decode(Str::after($pngDataUrl, 'data:image/png;base64,'), true);

        if (! str_starts_with($pngDataUrl, 'data:image/png;base64,') || $binary === false
            || ! str_starts_with($binary, "\x89PNG\r\n\x1a\n") || strlen($binary) > self::MAX_SIGNATURE_BYTES) {
            throw ValidationException::withMessages(['signature' => 'Unterschrift ist kein gültiges PNG.']);
        }

        $path = "appointment-signatures/{$appointment->id}/".Str::uuid().'.png';
        Storage::disk(self::DISK)->put($path, $binary);

        return $appointment->signatures()->create([
            'context' => $context,
            'signer_name' => $signerName,
            'disk' => self::DISK,
            'path' => $path,
            'signed_at' => now(),
        ]);
    }

    /**
     * @param  array{delivery_note_number: string, items_text?: string|null, recipient_name: string, recipient_email?: string|null, recipient_phone?: string|null, signature: string}  $data
     */
    public function recordDelivery(ServiceAppointment $appointment, array $data): AppointmentDelivery
    {
        return DB::transaction(function () use ($appointment, $data) {
            $signature = $this->storeSignature($appointment, 'delivery', $data['recipient_name'], $data['signature']);

            $delivery = $appointment->delivery()->updateOrCreate([], [
                ...collect($data)->only(['delivery_note_number', 'items_text', 'recipient_name', 'recipient_email', 'recipient_phone'])->all(),
                'delivered_at' => now(),
            ]);

            $delivery->update(['pdf_attachment_id' => $this->attachDeliveryNote($delivery, $signature)->id]);

            return $delivery;
        });
    }

    private function attachDeliveryNote(AppointmentDelivery $delivery, AppointmentSignature $signature): TicketAttachment
    {
        $appointment = $delivery->appointment;
        $pdf = Pdf::loadView('pdf.delivery-note', [
            'delivery' => $delivery,
            'appointment' => $appointment,
            'parts' => $appointment->partsUsed,
            'signatureDataUri' => 'data:image/png;base64,'.base64_encode(Storage::disk($signature->disk)->get($signature->path)),
        ])->output();

        $message = $appointment->ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_INTERNAL_NOTE,
            'direction' => 'outgoing',
            'body_text' => "Ware ausgeliefert, Lieferschein {$delivery->delivery_note_number}, Empfang bestätigt durch {$delivery->recipient_name}.",
        ]);

        return $this->attachments->storeRawContent($message, $pdf, 'Lieferschein-'.Str::slug($delivery->delivery_note_number).'.pdf', 'application/pdf');
    }
}
