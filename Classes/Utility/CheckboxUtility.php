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

class CheckboxUtility
{
	/**
	 * Gets the selected checkboxes as an array
	 *
	 * @param int $resultInt
	 * @return array
	 */
	public static function getSelectedIds(int $resultInt, int $max = 10): array
	{
		$ret = [];
		for ($i=0; $i < $max; $i++)	{
			// Separate bits and emit values (0 or 1)
			$ret[$i]= ($resultInt & pow(2,$i)) ? 1 : 0;
		}

		$selected = $ret;

		return $selected;
	}

	/**
	 * Gets an integer value for a selection array
	 *
	 * @param array $selectionArray
	 * @return int
	 */
	public static function getIntForSelectionArray(array $selectionArray): int
	{
		$res = 0;
		if (count($selectionArray) > 0) {
			foreach ($selectionArray as $key=>$val) {
                if ($val == 1) {
					$res+=pow(2,$key);
                }
            }
		}

		return $res;
	}
}
