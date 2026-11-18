<?php

namespace App\Support;

use Illuminate\Http\Request;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * International phone numbers: every number is validated against its
 * country's numbering plan and stored in E.164 (e.g. +250788123456).
 * Numbers typed without a country code are read as Rwandan.
 */
final class Phone
{
    public const DEFAULT_REGION = 'RW';

    /** The number in E.164, or null when it is not a valid phone number. */
    public static function e164(?string $raw, string $region = self::DEFAULT_REGION): ?string
    {
        $number = self::parse($raw, $region);

        return $number ? PhoneNumberUtil::getInstance()->format($number, PhoneNumberFormat::E164) : null;
    }

    public static function isValid(?string $raw, string $region = self::DEFAULT_REGION): bool
    {
        return self::parse($raw, $region) !== null;
    }

    /** Human-friendly international format, e.g. "+250 788 123 456". Falls back to the raw value. */
    public static function display(?string $raw): string
    {
        $number = self::parse($raw);

        return $number
            ? PhoneNumberUtil::getInstance()->format($number, PhoneNumberFormat::INTERNATIONAL)
            : (string) $raw;
    }

    /** ISO country code (e.g. "RW") of a valid number. */
    public static function region(?string $raw): ?string
    {
        $number = self::parse($raw);

        return $number ? PhoneNumberUtil::getInstance()->getRegionCodeForNumber($number) : null;
    }

    /** Digits only, without "+", as used in tel:/wa.me links. */
    public static function digits(?string $raw): ?string
    {
        $e164 = self::e164($raw);

        return $e164 ? ltrim($e164, '+') : null;
    }

    /**
     * Rewrites valid phone fields of a request to E.164 before validation,
     * so the stored value is always the standard form. Invalid values are
     * left untouched for the validation rule to reject.
     */
    public static function normalizeRequest(Request $request, array $fields): void
    {
        $changes = [];
        foreach ($fields as $field) {
            $value = $request->input($field);
            if (is_string($value) && trim($value) !== '' && ($e164 = self::e164($value))) {
                $changes[$field] = $e164;
            }
        }

        if ($changes) {
            $request->merge($changes);
        }
    }

    private static function parse(?string $raw, string $region = self::DEFAULT_REGION): ?\libphonenumber\PhoneNumber
    {
        $raw = trim((string) $raw);
        if ($raw === '' || strlen($raw) > 40) {
            return null;
        }

        // "00" international prefix → "+".
        if (str_starts_with($raw, '00')) {
            $raw = '+' . substr($raw, 2);
        }

        $util = PhoneNumberUtil::getInstance();
        try {
            $number = $util->parse($raw, $region);
        } catch (NumberParseException) {
            return null;
        }

        return $util->isValidNumber($number) ? $number : null;
    }
}
