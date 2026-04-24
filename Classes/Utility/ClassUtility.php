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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClassUtility
{
	/**
	 * Generates a class name by a table name
	 *
	 * @param string $string
	 * @return string
	 */
	public static function underscoredToUpperCamelCaseUnderscored(string $string): string
	{
		$upperCamelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', GeneralUtility::strtolower($string))));
		$upperCamelCase = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $upperCamelCase);
		return $upperCamelCase;
	}
}