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

namespace K3n\Tonictypes\ViewHelpers;

use K3n\Tonictypes\Service\Settings\Plugin\PluginSettingsService;


abstract class AbstractViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	/**
	 * Plugin Settings Service
	 *
	 * @var PluginSettingsService
	 */
	protected $pluginSettingsService;

    /**
     * @param PluginSettingsService $pluginSettingsService
     */
	public function injectPluginSettingsService(PluginSettingsService $pluginSettingsService)
    {
        $this->pluginSettingsService = $pluginSettingsService;
    }
}
