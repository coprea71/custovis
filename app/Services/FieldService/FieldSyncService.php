<?php

namespace App\Services\FieldService;

use App\Models\FieldSyncOperation;
use App\Models\ServiceAppointment;
use App\Models\TechnicianProfile;
use App\States\Appointment\AppointmentState;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Applies operations queued offline by the field PWA (14.md). Each op is
 * applied at most once (operation_uuid), only to the technician's own
 * appointments, and on conflicting status the server state wins.
 */
class FieldSyncService
{
    public const TYPES = ['status', 'checklist_item', 'signature', 'part', 'delivery', 'location'];

    public function __construct(private readonly ProofService $proofs) {}

    /**
     * @param  array<int, array<string, mixed>>  $operations
     */
    public function apply(TechnicianProfile $technician, array $operations): void
    {
        foreach ($operations as $operation) {
            $alreadyDone = FieldSyncOperation::query()
                ->where('technician_profile_id', $technician->id)
                ->where('operation_uuid', $operation['id'])
                ->exists();

            if (! $alreadyDone) {
                [$result, $message] = $this->applyOne($technician, $operation);
                FieldSyncOperation::query()->create([
                    'technician_profile_id' => $technician->id,
                    'operation_uuid' => $operation['id'],
                    'type' => $operation['type'],
                    'result' => $result,
                    'message' => $message,
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array{0: string, 1: string|null}
     */
    private function applyOne(TechnicianProfile $technician, array $operation): array
    {
        try {
            if ($operation['type'] === 'location') {
                return $this->location($technician, $operation['payload'] ?? []);
            }

            $appointment = ServiceAppointment::query()
                ->where('technician_profile_id', $technician->id)
                ->findOrFail($operation['appointment_id'] ?? 0);

            return match ($operation['type']) {
                'status' => $this->status($appointment, $operation['payload'] ?? []),
                'checklist_item' => $this->checklistItem($appointment, $operation['payload'] ?? []),
                'signature' => $this->signature($appointment, $operation['payload'] ?? []),
                'part' => $this->part($appointment, $operation['payload'] ?? []),
                'delivery' => $this->delivery($appointment, $operation['payload'] ?? []),
            };
        } catch (ValidationException $e) {
            return [FieldSyncOperation::REJECTED, collect($e->errors())->flatten()->first()];
        } catch (Throwable $e) {
            report($e);

            return [FieldSyncOperation::REJECTED, 'Operation konnte nicht verarbeitet werden.'];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: string|null}
     */
    private function status(ServiceAppointment $appointment, array $payload): array
    {
        $keys = array_keys(AppointmentState::byKey());
        $data = Validator::make($payload, ['from' => ['required', Rule::in($keys)], 'to' => ['required', Rule::in($keys)]])->validate();
        $target = AppointmentState::byKey()[$data['to']];

        // Server wins: the dispatcher changed the appointment while the technician was offline.
        if ($appointment->stateKey() !== $data['from'] || ! $appointment->state->canTransitionTo($target)) {
            return [FieldSyncOperation::CONFLICT, "Status ist inzwischen „{$appointment->state->label()}\"."];
        }

        $appointment->state->transitionTo($target);

        return [FieldSyncOperation::APPLIED, null];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: string|null}
     */
    private function checklistItem(ServiceAppointment $appointment, array $payload): array
    {
        $data = Validator::make($payload, ['item_id' => ['required', 'integer'], 'checked' => ['required', 'boolean']])->validate();

        $item = $appointment->checklists()->with('items')->get()->flatMap->items->firstWhere('id', (int) $data['item_id'])
            ?? throw ValidationException::withMessages(['item_id' => 'Checklistenpunkt gehört nicht zu diesem Termin.']);

        $item->update(['checked' => (bool) $data['checked'], 'checked_at' => $data['checked'] ? now() : null]);

        return [FieldSyncOperation::APPLIED, null];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: string|null}
     */
    private function signature(ServiceAppointment $appointment, array $payload): array
    {
        $data = Validator::make($payload, ['signer_name' => ['required', 'string', 'max:255'], 'png' => ['required', 'string']])->validate();

        $this->proofs->storeSignature($appointment, 'checklist', $data['signer_name'], $data['png']);

        return [FieldSyncOperation::APPLIED, null];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: string|null}
     */
    private function part(ServiceAppointment $appointment, array $payload): array
    {
        $data = Validator::make($payload, [
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'unit' => ['nullable', 'string', 'max:20'],
        ])->validate();

        $appointment->partsUsed()->create(['unit' => 'Stk', ...array_filter($data, fn ($value) => $value !== null)]);

        return [FieldSyncOperation::APPLIED, null];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: string|null}
     */
    private function delivery(ServiceAppointment $appointment, array $payload): array
    {
        $data = Validator::make($payload, [
            'delivery_note_number' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._\/-]+$/'],
            'items_text' => ['nullable', 'string', 'max:5000'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9 ()\/-]{5,40}$/'],
            'signature' => ['required', 'string'],
        ])->validate();

        if ($appointment->kind !== ServiceAppointment::KIND_DELIVERY) {
            throw ValidationException::withMessages(['kind' => 'Termin ist keine Warenauslieferung.']);
        }

        $this->proofs->recordDelivery($appointment, $data);

        return [FieldSyncOperation::APPLIED, null];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: string|null}
     */
    private function location(TechnicianProfile $technician, array $payload): array
    {
        if (! $technician->location_tracking_consent) {
            return [FieldSyncOperation::REJECTED, 'Keine Einwilligung zur Standortübermittlung.'];
        }

        $coordinates = RoutingService::validCoordinates($payload['lat'] ?? null, $payload['lng'] ?? null)
            ?? throw ValidationException::withMessages(['lat' => 'Ungültige Koordinaten.']);

        $technician->locations()->create([...$coordinates, 'recorded_at' => now()]);

        return [FieldSyncOperation::APPLIED, null];
    }
}
