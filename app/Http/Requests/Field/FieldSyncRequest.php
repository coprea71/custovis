<?php

namespace App\Http\Requests\Field;

use App\Services\FieldService\FieldSyncService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Envelope validation only; each operation's payload is validated per type
 * when it is applied (FieldSyncService), so one bad op cannot block the batch.
 */
class FieldSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('appointments.view.own') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'operations' => ['required', 'array', 'min:1', 'max:100'],
            'operations.*.id' => ['required', 'uuid'],
            'operations.*.type' => ['required', Rule::in(FieldSyncService::TYPES)],
            'operations.*.appointment_id' => ['nullable', 'integer'],
            'operations.*.payload' => ['present', 'array'],
        ];
    }
}
