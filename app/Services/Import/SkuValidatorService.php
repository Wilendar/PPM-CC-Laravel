<?php

namespace App\Services\Import;

use Illuminate\Support\Str;

/**
 * SKU Validator Service
 *
 * Walidacja SKU według zasad biznesowych PPM:
 * - Format: alfanumeryczny + dozwolone znaki specjalne
 * - Długość: 3-50 znaków
 * - Brak polskich znaków
 * - Brak spacji wewnętrznych
 *
 * @package App\Services\Import
 * @version 1.0
 * @since ETAP_08 - Import/Export System
 */
class SkuValidatorService
{
    /**
     * SKU validation rules
     */
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 50;

    /**
     * Allowed characters: A-Z, 0-9, -, _, .
     * (case-insensitive, uppercase conversion recommended)
     */
    private const ALLOWED_PATTERN = '/^[A-Z0-9\-_.]+$/i';

    /**
     * Forbidden characters (Polish, special symbols)
     */
    private const FORBIDDEN_CHARS = [
        'ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż',
        'Ą', 'Ć', 'Ę', 'Ł', 'Ń', 'Ó', 'Ś', 'Ź', 'Ż',
        ' ', // space
        '@', '#', '$', '%', '^', '&', '*', '(', ')', '+', '=',
        '[', ']', '{', '}', '|', '\\', '/', '<', '>', '?', '~', '`',
    ];

    /**
     * Validate SKU format
     *
     * @param string $sku
     * @return array Array of error messages (empty if valid)
     */
    public function validate(string $sku): array
    {
        $errors = [];

        // Trim whitespace
        $sku = trim($sku);

        // Check empty
        if ($sku === '') {
            $errors[] = 'SKU nie może być puste';
            return $errors;
        }

        // Check length
        $length = strlen($sku);
        if ($length < self::MIN_LENGTH) {
            $errors[] = sprintf(
                'SKU zbyt krótkie (min. %d znaków, podano %d)',
                self::MIN_LENGTH,
                $length
            );
        }
        if ($length > self::MAX_LENGTH) {
            $errors[] = sprintf(
                'SKU zbyt długie (max. %d znaków, podano %d)',
                self::MAX_LENGTH,
                $length
            );
        }

        // Check forbidden characters
        foreach (self::FORBIDDEN_CHARS as $char) {
            if (Str::contains($sku, $char)) {
                if ($char === ' ') {
                    $errors[] = 'SKU nie może zawierać spacji';
                } else {
                    $errors[] = sprintf('Niedozwolony znak: "%s"', $char);
                }
                break; // Stop after first forbidden char
            }
        }

        // Check pattern (only if no forbidden chars found)
        if (empty($errors) && !preg_match(self::ALLOWED_PATTERN, $sku)) {
            $errors[] = 'SKU może zawierać tylko: A-Z, 0-9, -, _, .';
        }

        // Check internal spaces (double check)
        if (Str::contains($sku, '  ')) {
            $errors[] = 'SKU zawiera wielokrotne spacje';
        }

        return $errors;
    }

    /**
     * Validate batch of SKUs
     *
     * @param array $skus Array of SKU strings
     * @return array{valid: array, invalid: array}
     */
    public function validateBatch(array $skus): array
    {
        $valid = [];
        $invalid = [];

        foreach ($skus as $sku) {
            $errors = $this->validate($sku);
            if (empty($errors)) {
                $valid[] = $sku;
            } else {
                $invalid[] = [
                    'sku' => $sku,
                    'errors' => $errors,
                ];
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
        ];
    }

    /**
     * Normalize SKU (uppercase, trim)
     *
     * @param string $sku
     * @return string
     */
    public function normalize(string $sku): string
    {
        return strtoupper(trim($sku));
    }

    /**
     * Check if SKU is valid (boolean)
     *
     * @param string $sku
     * @return bool
     */
    public function isValid(string $sku): bool
    {
        return empty($this->validate($sku));
    }

    /**
     * Suggest correction for common mistakes
     *
     * @param string $sku
     * @return string|null Suggested correction or null
     */
    public function suggestCorrection(string $sku): ?string
    {
        // Remove spaces
        $corrected = str_replace(' ', '', $sku);

        // Replace Polish characters
        $polishMap = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
            'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'E', 'Ł' => 'L', 'Ń' => 'N',
            'Ó' => 'O', 'Ś' => 'S', 'Ź' => 'Z', 'Ż' => 'Z',
        ];
        $corrected = str_replace(array_keys($polishMap), array_values($polishMap), $corrected);

        // Uppercase
        $corrected = strtoupper($corrected);

        // Remove remaining forbidden characters
        $corrected = preg_replace('/[^A-Z0-9\-_.]+/', '', $corrected);

        // If correction is valid and different from original, return it
        if ($corrected !== $sku && $this->isValid($corrected)) {
            return $corrected;
        }

        return null;
    }

    /**
     * Get validation rules for form display
     *
     * @return array
     */
    public function getRules(): array
    {
        return [
            'format' => 'Alfanumeryczny + "-", "_", "."',
            'length' => sprintf('%d-%d znaków', self::MIN_LENGTH, self::MAX_LENGTH),
            'case' => 'Wielkie litery zalecane',
            'forbidden' => 'Bez polskich znaków, spacji, znaków specjalnych',
            'examples' => ['ABC123', 'PART-001', 'SKU_2024.V1'],
        ];
    }
}
