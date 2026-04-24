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
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class DebugUtility
{
    /**
     * Generates a class name by a table name
     *
     * @param mixed $variable
     * @param string|null $title
     * @return string
     */
    public static function debugVariable($variable, string $title = null): string
    {
        return DebuggerUtility::var_dump($variable, $title,12,false,true,true);
    }

    /**
     * Logs content to a file that will be created in
     * the root of the instance and has the current date
     * in its name
     *
     * @param string $content
     * @param string|null $customIdentfier
     * @param bool $clear Clear the file before write
     * @return void
     */
	public static function log($content, string $customIdentfier = null, bool $clear = false): void
	{
		if (is_null($customIdentfier)) {
			$customIdentfier = date("Y-m-d")."_tonictypes";
        }

        if(is_array($content)) {
            $content = print_r($content, true);
        }

		$file = GeneralUtility::getFileAbsFileName("".$customIdentfier.".log");

		if ($clear == true) {
			@file_put_contents($file, "");
        }

		$dateStr = date("Y-m-d H:i:s");
		file_put_contents($file, "___[{$dateStr}]___".str_repeat("_", 20)."\r\n", FILE_APPEND);
		file_put_contents($file, $content."\r\n", FILE_APPEND);
	}
}
