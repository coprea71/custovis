<?php

namespace App\Services\Invoicing;

use App\Models\Setting;

/**
 * Seller master data and defaults of the invoicing module, stored as
 * settings "invoicing.<key>" (single company per installation).
 */
class InvoiceSettings
{
    public const DEFAULTS = [
        'company' => '',
        'street' => '',
        'postal_code' => '',
        'city' => '',
        'country' => 'DE',
        'vat_id' => '',
        'tax_number' => '',
        'contact_name' => '',
        'email' => '',
        'phone' => '',
        'iban' => '',
        'bic' => '',
        'bank_name' => '',
        'small_business' => '0',
        'tax_rate' => '19',
        'hourly_rate' => '90.00',
        'payment_days' => '14',
        'number_prefix' => 'RE-',
        'mailbox_id' => '',
    ];

    /**
     * Required for a valid XRechnung/ZUGFeRD EN16931 invoice (BR-DE-1, BR-DE-2, BT-31/32, BT-34).
     */
    private const REQUIRED_FOR_ISSUE = [
        'company' => 'Firmenname', 'street' => 'Straße', 'postal_code' => 'PLZ', 'city' => 'Ort',
        'contact_name' => 'Ansprechpartner', 'email' => 'E-Mail', 'phone' => 'Telefon', 'iban' => 'IBAN',
    ];

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $stored = Setting::query()->where('key', 'like', 'invoicing.%')->pluck('value', 'key');

        return collect(self::DEFAULTS)
            ->map(fn (string $default, string $key) => (string) ($stored['invoicing.'.$key] ?? $default))
            ->all();
    }

    public function get(string $key): string
    {
        return $this->all()[$key];
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function save(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            Setting::write('invoicing.'.$key, trim((string) $value));
        }
    }

    /**
     * @return list<string> labels of missing mandatory seller fields
     */
    public function missingForIssue(): array
    {
        $settings = $this->all();
        $missing = collect(self::REQUIRED_FOR_ISSUE)->filter(fn (string $label, string $key) => $settings[$key] === '')->values()->all();

        if ($settings['vat_id'] === '' && $settings['tax_number'] === '') {
            $missing[] = 'USt-IdNr. oder Steuernummer';
        }

        return $missing;
    }

    public function smallBusiness(): bool
    {
        return $this->get('small_business') === '1';
    }

    public function hourlyRateCents(): int
    {
        return (int) round((float) $this->get('hourly_rate') * 100);
    }
}
