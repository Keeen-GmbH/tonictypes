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
defined('TYPO3') or die();

call_user_func(
    function () {

        // Add 'tonictypes' group to CType dropdown
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup('tt_content', 'CType','tonictypes', 'LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:tx_tonictypes.plugins');

        /***********************************
         * Plugin - List
         ***********************************/
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Tonictypes',
            'List',
            'LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:plugin.list',
            'tonictypes-icon-tonictypes-list',
            'tonictypes'
        );

        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['tonictypes_list'] = 'layout,select_key,recursive';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['tonictypes_list']     = 'pi_flexform';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            'tonictypes_list',
            'FILE:EXT:tonictypes/Configuration/FlexForms/Plugins/List.xml'
        );

        /***********************************
         * Plugin - Detail
         ***********************************/
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Tonictypes',
            'Detail',
            'LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:plugin.detail',
            'tonictypes-icon-tonictypes-detail',
            'tonictypes'
        );

        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['tonictypes_detail'] = 'layout,select_key,recursive';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['tonictypes_detail']     = 'pi_flexform';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            'tonictypes_detail',
            'FILE:EXT:tonictypes/Configuration/FlexForms/Plugins/Detail.xml'
        );

        /***********************************
         * Plugin - Dynamic
         ***********************************/
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Tonictypes',
            'Dynamic',
            'LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:plugin.dynamic',
            'tonictypes-icon-tonictypes-dynamic',
            'tonictypes'
        );

        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['tonictypes_dynamic'] = 'layout,select_key,recursive';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['tonictypes_dynamic']     = 'pi_flexform';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            'tonictypes_dynamic',
            'FILE:EXT:tonictypes/Configuration/FlexForms/Plugins/Dynamic.xml'
        );

        /***********************************
         * Plugin - Plain
         ***********************************/
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Tonictypes',
            'Plain',
            'LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:plugin.plain',
            'tonictypes-icon-tonictypes-plain',
            'tonictypes'
        );

        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['tonictypes_plain'] = 'layout,select_key,recursive';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['tonictypes_plain']     = 'pi_flexform';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            'tonictypes_plain',
            'FILE:EXT:tonictypes/Configuration/FlexForms/Plugins/Plain.xml'
        );
    }
);
