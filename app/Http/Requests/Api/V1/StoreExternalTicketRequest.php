<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreExternalTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Guard `sanctum` + ability `tickets.create` already enforce this at
        // the route level; the team itself is derived from the token, never
        // from this request's input (see TicketApiController).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'requester_email' => ['required', 'email', 'max:255'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
        ];
    }
}
