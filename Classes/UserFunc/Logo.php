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

namespace K3n\Tonictypes\UserFunc;

use K3n\Tonictypes\Service\Backend\BackendAccessService;
use K3n\Tonictypes\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class Logo
{
    /**
     * @var BackendAccessService
     */
    protected $backendAccessService;

    /**
     * @param BackendAccessService $backendAccessService
     */
    public function injectBackendAccessService(BackendAccessService $backendAccessService)
    {
        $this->backendAccessService = $backendAccessService;
    }

	/**
	 * Displays the tonictypes logo in a field
	 *
	 * @param array $config
	 * @param mixed $parentObject
	 * @return string
	 */
	public function displayLogoText(array &$config, &$parentObject): string
	{
		if ($this->backendAccessService->disableTonictypesLogo()) {
            return '';
        }

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return '';
        }

		$logoUrl = $this->backendAccessService->getLogoUrl();
        $logoBrightUrl = $this->backendAccessService->getBrightLogoUrl();
		$supportEmail = $this->backendAccessService->getSupportEmail();

		$version = ExtensionManagementUtility::getExtensionVersion('tonictypes');

		$html = '';
		$html .= "<div class=\"form-control-wrap\">";
		if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() == 12) {
			$html .= "<img src=\"{$logoUrl}\" border=\"0\" alt=\"Tonictypes\" title=\"Tonictypes {$version}\" height=\"70\" />";
		}else{
			$html .= "<picture>";
			$html .= "<source srcset=\"{$logoBrightUrl}\" media=\"(prefers-color-scheme: dark)\">";
			$html .= "<img src=\"{$logoUrl}\" border=\"0\" alt=\"Tonictypes\" title=\"Tonictypes {$version}\" height=\"70\" />";
			$html .= "</picture>";
		}


		$html .= "<div style=\"margin-top:10px;\">Version <strong>{$version}</strong>&nbsp;| Mail:&nbsp;<a href=\"mailto:{$supportEmail}\">{$supportEmail}</a></div>";

        if (!$this->backendAccessService->disableSupportMessage()) {
            $html .= '<small>'
                ."<a class=\"btn btn-warning\" style=\"color:white; font-weight:bold; display:inline-block; margin-top:5px;\" href=\"https://t3planet.de/tonictypes-typo3-extension\" target=\"_blank\">"
                . LocalizationUtility::translate('LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:pro.upgrade_to_pro')
                . '</a>'
                . '</small>'
            ;
        }

        $html .= '</div>';

		return $html;
	}

	/**
	 * Displays the Tonictypes logo in a field and a message
	 *
	 * @param array $config
	 * @param mixed $parentObject
	 * @return string
	 */
	public function displayLogoAndMessage(array &$config, &$parentObject): string
	{
		$parameters = $config['parameters'];

		$html = $this->displayLogoText($config, $parentObject);

		if (isset($parameters['message'])) {
			$severity = (isset($parameters['severity']))?$parameters['severity']:'info';
			$html .= "<div class=\"alert alert-{$severity}\" style=\"margin-top:25px;\">{$parameters['message']}</div>";
		}

		return $html;
	}

	/**
	 * Display an information message, when no record
	 * storage pages were selected
	 *
	 * @param array $config Configuration Array
	 * @param mixed $parentObject Parent Object
	 * @return string
	 */
	public function displayLogoAndMessageOnEmptyRecordStoragePage(array &$config, &$parentObject): string
	{
		$row = $config['row'];

		if (!isset($row['pages']) || empty($row['pages'])) {
			$message = LocalizationUtility::translate('message.no_record_storage_page_configured');
			$config['parameters']['message'] = $message;
			$config['parameters']['severity'] = 'warning';
			return $this->displayLogoAndMessage($config, $parentObject);
		}

		return $this->displayLogoText($config, $parentObject);
	}

	/**
	 * Displays the Tonictypes logo in a field
	 *
	 * @param array $config
	 * @param array $parentObject
	 * @return string
	 */
	public function displayLogo(array &$config, &$parentObject): string
	{
		if ($this->backendAccessService->disableTonictypesLogo()) {
            return '';
        }

		$html = '';
		$path = GeneralUtility::getFileAbsFileName('EXT:tonictypes/Resources/Public/Images/logo_tonictypes.svg');
		$logoUrl = PathUtility::getAbsoluteWebPath($path);

		$html .= "<img src=\"{$logoUrl}\" border=\"0\" alt=\"Tonictypes\" title=\"Tonictypes\" />";
		return $html;
	}
}
