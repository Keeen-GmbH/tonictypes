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

namespace K3n\Tonictypes\Utility;

class StringUtility
{
    /**
     * Possible string dividers
     *
     * @var list<string>
     */
    protected static $dividers = [
        ';',
        '/',
        '.',
        '\\',
        ':',
        '-',
    ];

    /**
     * Gets exploded and trimmed values by
     * a separated string
     *
     * @param string $string String to separate
     * @param array $allowedSeparators List of allowed separators
     * @return array
     */
    public static function explodeSeparatedString(string $string, array $allowedSeparators = []): array
    {
        if ($string === '') {
            return [];
        }

        $dividers = $allowedSeparators !== [] ? $allowedSeparators : self::$dividers;

        // Normalize known dividers to comma separation.
        foreach ($dividers as $divider) {
            $string = str_replace((string)$divider, ',', $string);
        }

        return array_map('trim', explode(',', $string));
    }

    /**
     * Creates a usage friendly code from a given string
     *
     * @param string $string Entry string
     * @return string
     */
    public static function createCodeFromString(string $string): string
    {
        $string = self::normalizeEncoding($string);
        $attrCode = mb_strtolower($string, 'UTF-8');
        $attrCode = str_replace(' ', '', $attrCode);

        $removableValues = [
            ';' => '',
            ':' => '',
            '/' => '',
            '\\' => '',
            '"' => '',
            "'" => '',
            '.' => '',
            '(' => '',
            ')' => '',
            '+' => '',
            '&' => '',
            '@' => 'at',
            'ö' => 'oe',
            'ä' => 'ae',
            'ü' => 'ue',
            'ß' => 'ss',
            'Ö' => 'oe',
            'Ä' => 'ae',
            'Ü' => 'ue',
            ',' => '',
            '-' => '',
            '--' => '',
            '-_' => '',
            '---' => '',
            '__' => '',
            '___' => '',
            '____' => '',
        ];

        $attrCode = strtr($attrCode, $removableValues);

        if ($attrCode !== '' && is_numeric(substr($attrCode, 0, 1))) {
            $attrCode = 'i' . $attrCode;
        }

        $attrCode = str_replace(['___', '__'], '_', $attrCode);
        $attrCode = substr($attrCode, 0, 250);
        $attrCode = trim($attrCode, '_');

        return $attrCode;
    }

    /**
     * Normalize string encoding to UTF-8
     */
    private static function normalizeEncoding(string $value): string
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding !== false) {
            return mb_convert_encoding($value, 'UTF-8', $encoding);
        }

        return $value;
    }
}
