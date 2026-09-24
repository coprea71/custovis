<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules shared with the Livewire portal form (Portal\NewRequest), which
 * validates against rules() since Livewire actions bypass FormRequests.
 */
class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'service_catalog_item_id' => ['required', 'integer', Rule::exists('service_catalog_items', 'id')->where('active', true)],
            'description' => ['required', 'string', 'max:10000'],
        ];
    }
}
