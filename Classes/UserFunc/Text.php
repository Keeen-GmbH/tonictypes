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
use K3n\Tonictypes\Fluid\View\StandaloneView;
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
        return "";
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
        $message = LocalizationUtility::translate("message.this_field_has_no_configuration");
        return "<div class=\"message message-alert\">{$message}</div>";
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
        $message = "Error @ {$config["itemFormElName"]}";

        $parameters = $config["parameters"];
        if (isset($parameters["message"]))
            $message = $parameters["message"];

        $severity = "danger";
        if (isset($parameters["severity"]))
            $severity = $parameters["severity"];

        return "<div class=\"alert alert-{$severity}\">{$message}</div>";
    }

}
