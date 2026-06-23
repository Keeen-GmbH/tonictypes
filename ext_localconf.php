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

$boot = static function (): void {
    /***********************************
     * Additional Information in Constants
     ***********************************/
    $GLOBALS['TYPO3_CONF_VARS']['BE']['tonictypes']['additionalInformation'] = '
        <br />
        <hr style="background-color: #c0c0c0; color:#c0c0c0;" size="1" />
        <div style="float:left;">
        Auth: B. Zagar / Maint: J. Pietschmann<br />
        E-Mail: <a href="mailto:support@tonictypes.com">support@tonictypes.com</a><br />
        Website: <a href="https://tonictypes.com" target="_blank">tonictypes.com</a>
        </div>
        <div style="clear:both;"></div>
        <hr style="background-color: #c0c0c0; color:#c0c0c0;" size="1" />
    ';

    /***********************************
     * Backend CSS File Include
     ***********************************/
    $GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets'][] = 'EXT:tonictypes/Resources/Public/Css/tonictypes-backend.css';


    /***********************************
     * Custom User Element for
     * running custom userFuncs directly
     ***********************************/
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry']['user'] = [
        'nodeName' => 'user',
        'priority' => '1',
        'class'    => 'K3n\\Tonictypes\\Form\\Element\\UserElement',
    ];

    /***********************************
     * Custom User Element for
     * QueryBuilder
     ***********************************/
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1606422099] = [
        'nodeName' => 'queryBuilder',
        'priority' => 40,
        'class'    => \K3n\Tonictypes\Form\Element\QueryBuilderElement::class,
    ];

    /***********************************
     * Hook when saving record
     ***********************************/
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tonictypes'] = \K3n\Tonictypes\Hooks\DataHandling::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['tonictypes'] 	= \K3n\Tonictypes\Hooks\DataHandling::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\K3n\Tonictypes\Evaluation\DatatypeNameEvaluation::class] = '';

    // We need to add our DataHandler on top of the processing array, to leave version management behind
    array_unshift($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'],\K3n\Tonictypes\Hooks\DataHandling::class);
    array_unshift($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'],\K3n\Tonictypes\Hooks\DataHandling::class);

    /***********************************
     * Register Cache
     * for tca storage in files
     ***********************************/
    if (!array_key_exists('tonictypes_tca_cache', $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tonictypes_tca_cache'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tonictypes_tca_cache']['frontend'] = \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tonictypes_tca_cache']['backend'] = \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tonictypes_tca_cache']['options']['defaultLifetime'] = 86400;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tonictypes_tca_cache']['groups'] = ['system'];
    }

    /***********************************
     * Registering global namespace for Tonictypes ViewHelpers
     ***********************************/
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['dv'][] = 'K3n\Tonictypes\ViewHelpers';

    /***********************************
     * Tonictypes Enhancer and Aspect
     * for Routing
     ***********************************/
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['enhancers']['Tonictypes'] = \K3n\Tonictypes\Routing\Enhancer\TonictypesEnhancer::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['TonictypesMapper'] = \K3n\Tonictypes\Routing\Aspect\TonictypesMapper::class;

    /***********************************
     * Xclassing the PageRouter to
     * make full access to
     * TonictypesEnhancer and TonictypesMapper
     * for better routing
     ***********************************/
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Routing\PageRouter::class] = [
        'className' => K3n\Tonictypes\Xclass\Core\Routing\PageRouter::class
    ];

    /***********************************
     * Tonictypes Plugins
     ***********************************/
    // #1 - List Records
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Tonictypes',
        'List',
        [
            \K3n\Tonictypes\Controller\RecordController::class => 'list',
        ],
        [
            \K3n\Tonictypes\Controller\RecordController::class => 'list',
        ],
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    // #2 - Detail view for a record
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Tonictypes',
        'Detail',
        [
            \K3n\Tonictypes\Controller\RecordController::class => 'detail',
        ], // Cached
        [
            \K3n\Tonictypes\Controller\RecordController::class => 'detail',
        ], // UnCached
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    // #3 - Dynamic Detail view listener for a record
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Tonictypes',
        'Dynamic',
        [
            \K3n\Tonictypes\Controller\RecordController::class => 'dynamicDetail',
        ], // Cached
        [
            \K3n\Tonictypes\Controller\RecordController::class => 'dynamicDetail',
        ], // UnCached
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    // #4 - Plain fluid view
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Tonictypes',
        'Plain',
        [
            \K3n\Tonictypes\Controller\RecordController::class => 'plain',
        ], // Cached
        [
            \K3n\Tonictypes\Controller\RecordController::class => 'plain',
        ], // UnCached
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );


};

$boot();
unset($boot);


