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

namespace K3n\Tonictypes\UserFunc;

use K3n\Tonictypes\Domain\Model\Variable;
use K3n\Tonictypes\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Text
{
    /**
     * Variable Repository
     *
     * @var \K3n\Tonictypes\Domain\Repository\VariableRepository
     */
    protected $variableRepository;

    /**
     * Plugin Settings Service
     *
     * @var \K3n\Tonictypes\Service\Settings\Plugin\PluginSettingsService
     */
    protected $pluginSettingsService;

    /**
     * FlexForm Service
     *
     * @var \K3n\Tonictypes\Service\FlexForm\FlexFormService
     */
    protected $flexformService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->variableRepository		= GeneralUtility::makeInstance(\K3n\Tonictypes\Domain\Repository\VariableRepository::class);
        $this->pluginSettingsService    = GeneralUtility::makeInstance(\K3n\Tonictypes\Service\Settings\Plugin\PluginSettingsService::class);
        $this->flexformService          = GeneralUtility::makeInstance(\K3n\Tonictypes\Service\FlexForm\FlexFormService::class);
    }

    /**
     * Just display nothing :)
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return array
     */
    public function displayNothing(array &$config, &$parentObject)
    {
        return '';
    }

    /**
     * Just display nothing :)
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return array
     */
    public function displayNoConfigurationMessage(array &$config, &$parentObject)
    {
        $message = LocalizationUtility::translate('message.this_field_has_no_configuration');
        return "<div class=\"message message-alert\">{$message}</div>";
    }

    /**
     * Notice when a stored field type is no longer registered (e.g. Pro-only type without Pro).
     *
     * @param array $config
     * @param mixed $parentObject
     */
    public function displayUnsupportedFieldTypeMessage(array &$config, &$parentObject): string
    {
        $type = '';
        $row = $config['row'] ?? [];
        if (isset($row['type'])) {
            $type = is_array($row['type']) ? (string)($row['type'][0] ?? '') : (string)$row['type'];
        }
        if ($type === '' && isset($config['flexParentDatabaseRow']['type'])) {
            $parentType = $config['flexParentDatabaseRow']['type'];
            $type = is_array($parentType) ? (string)($parentType[0] ?? '') : (string)$parentType;
        }
        $typeLabel = $type !== '' ? strtoupper($type) : 'TCA';

        $message = LocalizationUtility::translate(
            'message.unsupported_field_type',
            [$typeLabel]
        );
        if ($message === '') {
            $message = sprintf(
                'The "%s" field is not available in the free version. Please upgrade to Tonictypes Professional to use this field type.',
                $typeLabel
            );
        }

        $linkLabel = LocalizationUtility::translate('message.unsupported_field_type.link');
        if ($linkLabel === '') {
            $linkLabel = LocalizationUtility::translate('pro.upgrade_to_pro');
        }
        if ($linkLabel === '') {
            $linkLabel = 'Upgrade to Tonictypes Professional';
        }

        return '<div class="alert tonictypes-alert-pro tonictypes-pro-upgrade-box" role="alert">'
            . '<p class="tonictypes-pro-upgrade-box__text">'
            . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</p>'
            . '<a class="btn tonictypes-btn-pro"'
            . ' href="https://t3planet.de/tonictypes" target="_blank" rel="noopener noreferrer">'
            . htmlspecialchars($linkLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</a>'
            . '</div>';
    }

    /**
     * Display a simple error text in backend
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return array
     */
    public function displayMessage(array &$config, &$parentObject)
    {
        $message = "Error @ {$config['itemFormElName']}";

        $parameters = $config['parameters'];
        if (isset($parameters['message'])) {
            $message = $parameters['message'];
        }

        $severity = 'danger';
        if (isset($parameters['severity'])) {
            $severity = $parameters['severity'];
        }

        return "<div class=\"alert alert-{$severity}\">{$message}</div>";
    }

}
