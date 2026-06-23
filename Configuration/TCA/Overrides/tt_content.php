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

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    // Add 'tonictypes' group to CType dropdown
    ExtensionManagementUtility::addTcaSelectItemGroup('tt_content', 'CType','tonictypes', 'LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:tx_tonictypes.plugins');

    $typo3Major = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();
    $plugins = [
        'List',
        'Detail',
        'Dynamic',
        'Plain',
    ];


    foreach ($plugins as $plugin) {
        $pluginLower = strtolower($plugin);
        $cType = 'tonictypes_' . $pluginLower;
        if ($typo3Major >= 14) {
            ExtensionUtility::registerPlugin(
                'Tonictypes',
                $plugin,
                'LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:plugin.' . $pluginLower,
                'tonictypes-icon-tonictypes-' . $pluginLower,
                'tonictypes',
                '',
                'FILE:EXT:tonictypes/Configuration/FlexForms/Plugins/' . $plugin . '.xml'
            );
        }

        $signature = ExtensionUtility::registerPlugin(
            'Tonictypes',
            $plugin,
            'LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:plugin.' . $pluginLower,
            'tonictypes-icon-tonictypes-' . $pluginLower,
            'tonictypes',
            '',
        );
        $GLOBALS['TCA']['tt_content']['types'][$signature]['subtypes_excludelist'] = 'layout,select_key,recursive';
        $GLOBALS['TCA']['tt_content']['types'][$signature]['subtypes_addlist']     = 'pi_flexform';

        $flexFormFile = 'FILE:EXT:tonictypes/Configuration/FlexForms/Plugins/' . $plugin . '.xml';
        ExtensionManagementUtility::addPiFlexFormValue($signature, $flexFormFile, $cType);
        ExtensionManagementUtility::addPiFlexFormValue('*', $flexFormFile, $cType);

        ExtensionManagementUtility::addToAllTCAtypes(
            'tt_content',
            '--div--;LLL:EXT:tonictypes/Resources/Private/Language/locallang_db.xlf:tx_tonictypes, pi_flexform',
            $signature,
            'after:palette:headers'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'tt_content',
            'pages',
            $cType,
            'after:pi_flexform'
        );
    }
})();