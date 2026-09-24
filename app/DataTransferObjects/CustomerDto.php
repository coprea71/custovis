<?php

namespace App\DataTransferObjects;

/**
 * A customer record as returned by an ERP adapter. Values are normalised to
 * short plain strings because they come from an external system.
 */
readonly class CustomerDto
{
    private const MAX_VALUE_LENGTH = 255;

    /**
     * @param  array<string, mixed>  $attributes  raw remote attributes
     */
    public function __construct(
        public string $externalId,
        public array $attributes,
    ) {}

    /**
     * Applies the connection's field mapping (remote field => label) so only
     * released fields ever reach the UI.
     *
     * @param  array<string, string>  $fieldMapping
     * @return array<string, ?string> label => value
     */
    public function mapped(array $fieldMapping): array
    {
        $result = [];

        foreach ($fieldMapping as $field => $label) {
            $result[$label] = self::normalise($this->attributes[$field] ?? null);
        }

        return $result;
    }

    private static function normalise(mixed $value): ?string
    {
        // Odoo returns many2one relations as [id, display_name] and "false" for empty fields.
        if (is_array($value) && array_is_list($value) && count($value) === 2 && is_string($value[1])) {
            $value = $value[1];
        }

        if ($value === null || $value === false || $value === '' || ! is_scalar($value)) {
            return null;
        }

        if ($value === true) {
            return 'Ja';
        }

        return mb_substr(trim((string) $value), 0, self::MAX_VALUE_LENGTH);
    }
}
