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

use K3n\Tonictypes\Service\FlexForm\FlexFormService;
use K3n\Tonictypes\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Variable
{
    /**
     * FlexForm Service
     * @var FlexFormService
     */
    protected $flexFormService;

    /**
     * Field Repository
     *
     * @var \K3n\Tonictypes\Domain\Repository\VariableRepository
     */
    protected $variableRepository;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
        $this->variableRepository = GeneralUtility::makeInstance(\K3n\Tonictypes\Domain\Repository\VariableRepository::class);
    }

    /**
     * Populate variables
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return void
     */
    public function populateOverrideVariables(array &$config, &$parentObject): void
    {
        $types = [
            \K3n\Tonictypes\Domain\Model\Variable::VARIBALE_TYPE_TYPOSCRIPT,
            \K3n\Tonictypes\Domain\Model\Variable::VARIABLE_TYPE_TYPOSCRIPT_VAR,
            \K3n\Tonictypes\Domain\Model\Variable::VARIABLE_TYPE_GET,
            \K3n\Tonictypes\Domain\Model\Variable::VARIABLE_TYPE_POST,
            \K3n\Tonictypes\Domain\Model\Variable::VARIABLE_TYPE_FIXED,
        ];

        $variables = $this->variableRepository->findByTypes($types);

        $options = [];
        foreach($variables as $_variable) {
            /* @var \K3n\Tonictypes\Domain\Model\Variable $_variable */
            $label = "[u:{$_variable->getUid()}|p:{$_variable->getPid()}] " . LocalizationUtility::translate("variable_type.{$_variable->getType()}") . " {{$_variable->getVariableName()}}";
            $value = $_variable->getVariableName();
            $options[] = [
                'label' => $label,
                'value' => $value
            ];
        }

        $config['items'] = array_merge($config['items'], $options);
    }

    /**
     * Populate get/post variables
     * @param array $config
     * @param $parentObject
     */
    public function populateGetPostVariables(array &$config, &$parentObject): void
    {
        $row = (isset($config['flexParentDatabaseRow']))?$config['flexParentDatabaseRow']:$config['row'];
        $flex = $this->flexFormService->walkFlexFormNode($row['pi_flexform'], 'vDEF');
        $flex = $this->flexFormService->walkFlexFormNode($flex, 'lDEF');

        $variableIds = [];
        if(isset($flex['data']['template_settings']['settings']['variables'])) {
            $vs = $flex['data']['template_settings']['settings']['variables'];
            if(is_array($vs)) {
                $variableIds = $vs;
            } else {
                $variableIds = GeneralUtility::intExplode(',', $vs, true);
            }
        }

        $variables = [];
        if(!empty($variableIds)) {
            $variables = $this->variableRepository->findByUids($variableIds);
        }

        /* @var \K3n\Tonictypes\Domain\Model\Variable $_variable */
        $options = [];
        foreach ($variables as $_variable) {
            if($_variable->getType() == \K3n\Tonictypes\Domain\Model\Variable::VARIABLE_TYPE_GET ||
                $_variable->getType() == \K3n\Tonictypes\Domain\Model\Variable::VARIABLE_TYPE_POST) {

                $options[] = [
                    'label' => '{'.$_variable->getVariableName().'}',
                    'value' => $_variable->getUid()
                ];
            }
        }

        $config['items'] = array_merge($config['items'], $options);
    }
}
