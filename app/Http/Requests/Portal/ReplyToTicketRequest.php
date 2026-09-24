<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Rules shared with the Livewire portal ticket view (Portal\TicketDetail).
 */
class ReplyToTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'max:10000'],
        ];
    }
}
