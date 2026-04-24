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

class LocalizationUtility
{
	/**
	 * Translates
	 *
	 * @param string $key
	 * @param array $arguments
	 * @return string
	 */
	public static function translate(string $key, array $arguments = null): string
	{
		if (!is_array($arguments)) {
			$arguments = [$arguments];
        }

		return (string)\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, \K3n\Tonictypes\Configuration\ExtensionConfiguration::EXTENSION_KEY, $arguments);
	}
}
