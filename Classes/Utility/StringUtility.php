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
	 * @var array
	 */
	protected static $dividers = [
		';',
		'/',
		'.',
		"\\",
		":",
		"-"
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
		if (strlen($string)) {

			$dividers = self::$dividers;

			if (!empty($allowedSeparators)) {
				$dividers = $allowedSeparators;
            }

			// Check that the divider of the ids is comma separation
			foreach ($dividers as $divider) {
                $string = str_replace($divider, ',', $string);
			}

			$exploded = array_map('trim',explode(",",$string));

			return $exploded;
		}

		return [];
	}

	/**
	 * Creates a usage friendly code from a given string
	 *
	 * @param $string Entry string
	 * @return string
	 */
	public static function createCodeFromString(string $string): string
	{
		$string = self::normalizeEncoding($string);

        // Normalize encoding to UTF-8 (safe for TYPO3)
        $string = self::normalizeEncoding($string);
        // Lowercase (multibyte safe)
        $attrCode = mb_strtolower($string, 'UTF-8');
        // Replace spaces
        $attrCode = str_replace(' ', '', $attrCode);

		$removable_values = [
			";" 	=> 	"",
			":" 	=> 	"",
			"/" 	=> 	"",
			"\\" 	=> 	"",
			"\""	=>  "",
            "/"     =>  "",
			"'"		=>	"",
			":"		=>  "",
			"."		=>	"",
			"("		=>	"",
			")"		=>	"",
			"+"		=>	"",
			"&"		=>  "",
			"@"		=>  "at",
			"ö" 	=> 	"oe",
			"ä" 	=> 	"ae",
			"ü" 	=> 	"ue",
			"ß"		=>	"ss",
			"ö" 	=> 	"oe",
			"ä" 	=> 	"ae",
			"ü" 	=> 	"ue",
			"ß"	    =>	"ss",
			"ö" 	=> 	"oe",
			"ä" 	=> 	"ae",
			"ü" 	=> 	"ue",
			"ß"	    =>	"ss",
			"Ö" 	=> 	"oe",
			"Ä" 	=> 	"ae",
			"Ü" 	=> 	"ue",
			"Ö" 	=> 	"oe",
			"Ä" 	=> 	"ae",
			"Ü" 	=> 	"ue",
			"Ö" 	=> 	"oe",
			"Ä" 	=> 	"ae",
			"Ü" 	=> 	"ue",
			"," 	=> 	"",
			"-"		=>	"",
			"--"	=>	"",
			"-_"	=>	"",
			"---"	=>	"",
			"__" 	=> 	"",
			"___" 	=> 	"",
			"____" 	=> 	"",
		];

		//$attrCode = preg_replace('/[^a-zA-Z0-9_]/u', '_', $attrCode);
		$attrCode = strtr($attrCode,$removable_values);

		if (is_numeric(substr($attrCode, 0, 1))) {
			$attrCode = 'i'.$attrCode;
		}

		$attrCode = str_replace('___', '_', $attrCode);
		$attrCode = str_replace('__', '_', $attrCode);
		$attrCode = substr($attrCode, 0, 250);
		$attrCode = trim($attrCode, '_');

		return $attrCode;
	}

    /**
     * Normalize string encoding to UTF-8
     */
    private static function normalizeEncoding(string $value): string
    {
        // Already UTF-8?
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        // Try detect and convert
        $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        if ($encoding !== false) {
            return mb_convert_encoding($value, 'UTF-8', $encoding);
        }

        // Fallback
        return $value;
    }
}
