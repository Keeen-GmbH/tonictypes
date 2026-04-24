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

namespace K3n\Tonictypes\Hooks;

use K3n\Tonictypes\Fluid\View\StandaloneView;
use K3n\Tonictypes\Icon\TonictypesIconRegistry;
use K3n\Tonictypes\Service\Backend\BackendAccessService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Constants
{
    /**
     * @var StandaloneView
     */
    protected $standaloneView;

    /**
     * @var TonictypesIconRegistry
     */
    protected $tonictypesIconRegistry;

    /**
     * @var BackendAccessService
     */
    protected $backendAccessService;

    /**
     * @param StandaloneView $standaloneView
     */
    public function injectStandaloneView(StandaloneView $standaloneView)
    {
        $this->standaloneView = $standaloneView;
    }

    /**
     * @param TonictypesIconRegistry $tonictypesIconRegistry
     */
    public function injectTonictypesIconRegistry(TonictypesIconRegistry $tonictypesIconRegistry)
    {
        $this->tonictypesIconRegistry = $tonictypesIconRegistry;
    }

    /**
     * @param BackendAccessService $backendAccessService
     */
    public function injectBackendAccessService(BackendAccessService $backendAccessService)
    {
        $this->backendAccessService = $backendAccessService;
    }

    /**
     * Displays the information field in the constants editor
     * @param array $config
     * @param ExtendedTemplateService $extTs
     * @return string
     */
    public function displayExtensionInformation(array $config): string
    {
        $config['fieldName']  = '';
        $config['fieldValue'] = '';

        $_EXTKEY = 'tonictypes';
        $EM_CONF = null;
        $emConf  = 'EXT:tonictypes/ext_emconf.php';
        $emConfPro = 'EXT:tonictypes_pro/ext_emconf.php';
        $emConf = GeneralUtility::getFileAbsFileName($emConf);
        $emConfPro = GeneralUtility::getFileAbsFileName($emConfPro);

        // Check for pro extension existence and use that instead
        if(file_exists($emConfPro)) {
            $emConf = $emConfPro;
        }

        require_once($emConf);

        if (!is_array($EM_CONF[$_EXTKEY])) {
            return '<div class="alert alert-danger" style="padding:10px;">Error loading extension information!</div>';
        }

        $icons = $this->tonictypesIconRegistry->getIcons();

        // Prepare icon path
        foreach ($icons as $_iconId => $_iconPath) {
            $pos               = strpos($_iconPath, 'ext/tonictypes/');
            if($pos !== false) {
                $correctedIconPath = substr($_iconPath, $pos);
                $correctedIconPath = str_replace('ext/tonictypes/', 'EXT:tonictypes/', $correctedIconPath);
                $icons[$_iconId]   = $correctedIconPath;
            }
        }

        $templateFile = 'EXT:tonictypes/Resources/Private/Templates/Backend/Constants/ExtInformation.html';
        $templateFile = GeneralUtility::getFileAbsFileName($templateFile);
        $this->standaloneView->setTemplatePathAndFilename($templateFile);

        $additionalInformation = $GLOBALS['TYPO3_CONF_VARS']['BE']['tonictypes']['additionalInformation'] ?? $GLOBALS['TYPO3_CONF_VARS']['BE']['tonictypes']['additionalInformation'];

        $this->standaloneView->assign('additionalInformation', $additionalInformation);
        $this->standaloneView->assign('config', $config);
        $this->standaloneView->assign('emConf', $EM_CONF[$_EXTKEY]);
        $this->standaloneView->assign('icons', $icons);
        $this->standaloneView->assign('logoUrl', $this->backendAccessService->getLogoUrl());
        $this->standaloneView->assign('supportMessage', $this->backendAccessService->disableSupportMessage());

        return $this->standaloneView->render();
    }

}
