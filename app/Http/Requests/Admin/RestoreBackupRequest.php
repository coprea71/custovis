<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RestoreBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('system.maintain') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'archive' => ['required', 'file', 'mimes:zip', 'max:512000'],
            'password_file' => ['required', 'file', 'mimes:txt', 'max:4'],
            'confirm' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['archive' => 'ZIP-Datei', 'password_file' => 'Passwort-Datei', 'confirm' => 'Bestätigung'];
    }
}
