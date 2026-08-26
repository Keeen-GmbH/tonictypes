<?php

declare(strict_types=1);

/*
 * This file is part of the package k3n/tonictypes.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * Many thanks to Auth: B. Zagar / Maint: J. Pietschmann for sharing this extension – TYPO3 inspiring people to share!
 * Contact: support@tonictypes.com
 *
 */

namespace K3n\Tonictypes\Evaluation;

/**
 * Basic phone number formatting and validation for FormEngine / DataHandler.
 */
class PhoneNumberEvaluation
{
    /**
     * Allows an optional leading +, digits, spaces, dashes, dots, slashes and parentheses.
     */
    private const PHONE_PATTERN = '/^\+?[\d\s\-().\/]{3,30}$/';

    /**
     * @param mixed $value
     * @param string $isIn
     * @param bool $set
     */
    public function evaluateFieldValue($value, $isIn = '', &$set = true): string
    {
        if (!is_scalar($value)) {
            $set = false;
            return '';
        }

        $phone = $this->normalize((string)$value);
        if ($phone === '') {
            return '';
        }

        if (!preg_match(self::PHONE_PATTERN, $phone)) {
            $set = false;
            return '';
        }

        // Must contain at least a few digits
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 3) {
            $set = false;
            return '';
        }

        return $phone;
    }

    /**
     * JS evaluation for FormEngine live checks (optional soft formatting).
     */
    public function returnFieldJS(): string
    {
        return "return value.replace(/\\s+/g, ' ').trim();";
    }

    protected function normalize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
