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

namespace K3n\Tonictypes\Service\Settings\Plugin;

use K3n\Tonictypes\Configuration\ExtensionConfiguration as Configuration;
use K3n\Tonictypes\Service\Settings\AbstractSettingsService;
use K3n\Tonictypes\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PluginSettingsService extends AbstractSettingsService
{
    /**
     * Template Selection
     * @var string
     */
    const TEMPLATE_SELECTION_DEBUG          = 'DEBUG';
    const TEMPLATE_SELECTION_CUSTOM			= 'CUSTOM';
    const TEMPLATE_SELECTION_FLUID			= 'FLUID';

    /**
     * Plugin Name
     * @var string
     */
    protected $extensionName = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setExtensionName( 'tx_' . Configuration::EXTENSION_KEY );
    }

    /**
     * Sets the extension name
     *
     * @param string $extensionName
     * @return void
     */
    public function setExtensionName(string $extensionName): void
    {
        $this->extensionName = $extensionName;
    }

    /**
     * Gets the extension name
     *
     * @return string
     */
    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    /**
     * Gets the configured variable name
     * for records
     *
     * @return string
     */
    public function getRecordsVarName(): string
    {
        $name = (string)$this->getConfiguration('plugin.tx_tonictypes.settings.recordsVariableName');
        if(!is_string($name)) {
            return 'records';
        }
        return StringUtility::createCodeFromString($name);
    }

    /**
     * Gets the configured variable name for
     * a single record
     *
     * @return string
     */
    public function getRecordVarName(): string
    {
        $name = (string)$this->getConfiguration('plugin.tx_tonictypes.settings.singleRecordVariableName');
        if(!is_string($name)) {
            return 'record';
        }
        return StringUtility::createCodeFromString($name);
    }

    /**
     * Gets the configuration for the predefines
     * templates
     *
     * @return array
     */
    public function getPredefinedTemplates(): array
    {
        $configuration = $this->getConfiguration('plugin.tx_tonictypes.templates');
        if (is_array($configuration) && !empty($configuration)) {
            $configuration = GeneralUtility::removeDotsFromTS($configuration);
        }

        return $configuration;
    }

    /**
     * Gets the predefined template by a given
     * id
     *
     * @param string $templateId
     * @return string|null
     */
    public function getPredefinedTemplateById(string $templateId): ?string
    {
        $templates = $this->getPredefinedTemplates();
        $info = (isset($templates[$templateId]))?$templates[$templateId]:null;

        if (isset($info['file'])) {
            return $info['file'];
        }

        return $info;
    }

    /**
     * Gets the cache lifetime configuration
     *
     * @return int
     */
    public function getCacheLifetime()
    {
        return (int)$this->getConfiguration('plugin.tx_tonictypes.developer.cache_lifetime');
    }
}
