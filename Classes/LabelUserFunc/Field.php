<?php
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
namespace K3n\Tonictypes\LabelUserFunc;

use K3n\Tonictypes\Utility\LocalizationUtility as Locale;
use K3n\Tonictypes\Configuration\ExtensionConfiguration as Config;
use TYPO3\CMS\Backend\Utility\BackendUtility;


class Field
{
	/**
	 * Field Repository
	 *
	 * @var \K3n\Tonictypes\Domain\Repository\FieldRepository
	 */
	protected $fieldRepository;

	/**
	 * UserFunc for Field Label
	 *
	 * @param array $pObj Object Information
	 * @return void
	 */
	public function displayLabel(&$pObj)
	{
	    $record = BackendUtility::getRecordWSOL($pObj['table'], $pObj['row']['uid']);
        if(is_array($record)) {
            $type = (is_array($record) && is_array($record['type'])) ? reset($record['type']) : $record['type'];
            $label = ($record['frontend_label']) ? $record['frontend_label'] : "[" . Locale::translate("no_label") . "]";
            $pObj['title'] = "[{$record['pid']}] " . strtoupper($type) . ": " . $label . " {" . $record['variable_name'] . "}";
        }
	}

}
