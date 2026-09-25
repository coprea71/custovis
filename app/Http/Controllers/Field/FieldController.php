<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Http\Requests\Field\FieldSyncRequest;
use App\Jobs\ProcessFieldSyncJob;
use App\Models\FieldSyncOperation;
use App\Models\ServiceAppointment;
use App\Models\TechnicianProfile;
use App\States\Appointment\AppointmentState;
use App\States\Appointment\Cancelled;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Technician PWA (/field, 14.md): the page shell is cached by the service
 * worker, /field/today delivers the day's data set for offline use, and
 * /field/sync accepts the offline queue.
 */
class FieldController extends Controller
{
    private const DAYS_AHEAD = 1;

    public function show(Request $request): View
    {
        return view('field.app', ['technician' => $this->technician($request)]);
    }

    public function today(Request $request): JsonResponse
    {
        $technician = $this->technician($request);

        $appointments = $technician->appointments()
            ->with(['ticket:id,subject,requester_name,requester_phone', 'checklists.items', 'delivery'])
            ->whereNotState('state', Cancelled::class)
            ->whereBetween('scheduled_start', [now()->startOfDay(), now()->addDays(self::DAYS_AHEAD)->endOfDay()])
            ->orderBy('scheduled_start')
            ->get();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'location_consent' => $technician->location_tracking_consent,
            'appointments' => $appointments->map(fn (ServiceAppointment $appointment) => $this->present($appointment))->all(),
            'recent_results' => FieldSyncOperation::query()->where('technician_profile_id', $technician->id)
                ->where('result', '!=', FieldSyncOperation::APPLIED)->latest()->limit(10)->get(['operation_uuid', 'result', 'message']),
        ]);
    }

    public function sync(FieldSyncRequest $request): JsonResponse
    {
        $operations = $request->validated('operations');
        ProcessFieldSyncJob::dispatch($this->technician($request), $operations);

        return response()->json(['accepted' => array_column($operations, 'id')], 202);
    }

    private function technician(Request $request): TechnicianProfile
    {
        abort_unless($request->user()->can('appointments.view.own'), 403, 'Ihnen fehlt die Berechtigung für die Techniker-App.');

        // An explicit 403 instead of a bare 404: the route exists, only the
        // profile is missing — admins otherwise read "Not Found" as a broken deploy.
        return TechnicianProfile::query()->where('user_id', $request->user()->id)->where('active', true)->first()
            ?? abort(403, 'Für Ihr Konto ist kein aktives Technikerprofil hinterlegt. Eine Administratorin oder ein Administrator kann es unter Administration → Techniker anlegen bzw. aktivieren.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ServiceAppointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'kind' => $appointment->kind,
            'state' => $appointment->stateKey(),
            'state_label' => $appointment->state->label(),
            'next_states' => collect($appointment->state->transitionableStates())
                ->map(fn (string $class) => ['key' => AppointmentState::keyOf($class), 'label' => (new $class($appointment))->label()])
                ->values(),
            'start' => $appointment->scheduled_start->toIso8601String(),
            'end' => $appointment->scheduled_end->toIso8601String(),
            'address' => $appointment->address,
            'lat' => $appointment->lat,
            'lng' => $appointment->lng,
            'notes' => $appointment->notes,
            'ticket' => $appointment->ticket->only(['id', 'subject', 'requester_name', 'requester_phone']),
            'checklist_items' => $appointment->checklists->flatMap->items
                ->map(fn ($item) => $item->only(['id', 'label', 'checked']))->values(),
            'delivered' => $appointment->delivery?->delivered_at !== null,
        ];
    }
}
