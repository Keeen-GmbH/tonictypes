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

use K3n\Tonictypes\Controller\RecordController;
use K3n\Tonictypes\Domain\Model\AbstractRecordModel;
use K3n\Tonictypes\Domain\Model\Variable;
use K3n\Tonictypes\Domain\Repository\DatatypeRepository;
use K3n\Tonictypes\Domain\Repository\VariableRepository;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use K3n\Tonictypes\Service\FlexForm\FlexFormService;
use K3n\Tonictypes\Service\Settings\Plugin\PluginSettingsService;
use K3n\Tonictypes\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class Template
{
    /**
     * @var VariableRepository
     */
    protected $variableRepository;

    /**
     * @var DatatypeRepository
     */
    protected $datatypeRepository;

    /**
     * @var PluginSettingsService
     */
    protected $pluginSettingsService;

    /**
     * @var FlexFormService
     */
    protected $flexFormService;

    /**
     * @param VariableRepository $variableRepository
     */
    public function injectVariableRepository(VariableRepository $variableRepository)
    {
        $this->variableRepository = $variableRepository;
    }

    /**
     * @param DatatypeRepository $datatypeRepository
     */
    public function injectDatatypeRepository(DatatypeRepository $datatypeRepository)
    {
        $this->datatypeRepository = $datatypeRepository;
    }

    /**
     * @param PluginSettingsService $pluginSettingsService
     */
    public function injectPluginSettingsService(PluginSettingsService $pluginSettingsService)
    {
        $this->pluginSettingsService = $pluginSettingsService;
    }

    /**
     * @param FlexFormService $flexFormService
     */
    public function injectFlexFormService(FlexFormService $flexFormService)
    {
        $this->flexFormService = $flexFormService;
    }

    /**
	 * Populate flexform predefined templates
	 *
	 * @param array $config Configuration Array
	 * @param mixed $parentObject Parent Object
	 * @return void
	 */
	public function populateTemplates(array &$config, &$parentObject): void
	{
        $pid = $config['effectivePid'] ?? 0;
		$configuration = $this->pluginSettingsService->getPredefinedTemplates($pid);
		$options = [];
        $grouped = [];

        if (is_array($configuration)) {
			$options[] = [
                'label' => LocalizationUtility::translate('LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:flexform.predefined_templates'),
                'value' => '--div--'
            ];

			foreach ($configuration as $_id=>$_templateInfo) {

                $hidden = (isset($_templateInfo['hidden']))?(bool)$_templateInfo['hidden']:false;
                if($hidden === true) {
                    continue;
                }

			    if (is_array($_templateInfo)) {

			        // New template information
                    $group = (isset($_templateInfo['group']))?$_templateInfo['group']:'ALL';
                    $icon = (isset($_templateInfo['icon']))?$_templateInfo['icon']:null;
                    $label = (isset($_templateInfo['name']))?$_templateInfo['name']:$_id;

                    if (is_null($icon)) {
                        $grouped[$group][] = [
                            'label' => $label,
                            'value' => $_id
                        ];
                    } else {
                        $grouped[$group][] = [
                            'label' => $label,
                            'value' => $_id,
                            'icon' => $icon
                        ];
                    }

                } else {

                    if ($_templateInfo == '--div--')
                    {
                        $str = (LocalizationUtility::translate($_id))?LocalizationUtility::translate($_id):$_id;
                        $options[] = [
                            'label' => $str,
                            'value' => '--div--'
                        ];
                    }
                    else
                    {
                        $filePath = GeneralUtility::getFileAbsFileName($_templateInfo);
                        if (file_exists($filePath))
                        {
                            $label = '{$_id}';
                            $options[] = [
                                'label' => $label,
                                'value' => $_id
                            ];
                        }
                    }
                }
			}

			foreach ($grouped as $_group=>$_templates) {
                $options[] = [
                    'label' => $_group,
                    'value' => '--div--'
                ];
                foreach ($_templates as $_template) {
                    $options[] = $_template;
                }
            }
		}

		$config['items'] = array_merge($config['items'], $options);
	}

    /**
     * Display available markers for field filter value
     *
     * @param array $config Configuration Array
     * @param mixed $parentObject Parent Object
     * @return string
     */
    public function displayAvailableMarkers(array &$config, &$parentObject): string
    {
        $row = $config['row'];
        $pages = (isset($row['pages']))?$row['pages']:$row['pid'];
        $parameters = (isset($config['parameters']))?$config['parameters']:[];

        $variableIds = [];
        $variables = [];
        if(isset($row['pi_flexform'])) {
            $flex = $this->walkFlexFormNode($row['pi_flexform'], 'vDEF');
            $flex = $this->walkFlexFormNode($flex, 'lDEF');

             if(is_array($flex['data']['template_settings']['settings']['variables'])) {
                 $variableIds = array_column($flex['data']['template_settings']['settings']['variables'],'uid');
             }
             if(!empty($variableIds)) {
                 $variables = $this->variableRepository->findByUids($variableIds);
             }
        }


        $pluginType = '';
        if(array_key_exists('CType', $row)) {
            // Get the current plugin type or action
            $pluginType = reset($row['CType']);
        }

        if (!is_array($pages)) {
            $pages = GeneralUtility::trimExplode(',', (string)$pages, true);
        }

        $pids = [];
        foreach ($pages as $_page) {
            if (is_array($_page)) {
                $pids[] = $_page['uid'];
            } else {
                preg_match('/(?<table>.*)_(?<uid>[0-9]{0,11})|.*/', $_page, $match);
                if (is_array($match)) {
                    if (isset($match['uid']))
                        $pids[] = $match['uid'];
                    else
                        $pids[] = $match[0];
                }
            }
        }

        // Adding the pid of the current content element for possible inline variables
        $pids[] = $row['pid'];
        $markers = [];

        /* @var Variable $_variable */
        foreach ($variables as $_variable) {
            $markers[] = [
                'name' => $_variable->getVariableName(),
                'type' => LocalizationUtility::translate("LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:variable_type.{$_variable->getType()}"),
                'description' => '',
            ];
        }

        if(isset($parameters['envVariables']) && (bool)$parameters['envVariables'] === true) {
            // Default Markers
            $markers[] = [
                'name'        => RecordController::DEFAULT_VAR_COBJ,
                'type'        => 'array',
                'description' => LocalizationUtility::translate('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.elements.ContentElement.label'),
            ];
            $markers[] = [
                'name'        => RecordController::DEFAULT_VAR_SETTINGS,
                'type'        => 'array',
                'description' => LocalizationUtility::translate("LLL:EXT:frontend/Resources/Private/Language/locallang_csh_ttcontent.xlf:pi_flexform.description"),
            ];
            $markers[] = [
                'name'        => RecordController::DEFAULT_VAR_DETAILPID,
                'type'        => 'integer',
                'description' => LocalizationUtility::translate("LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:flexform.detail_page_id"),
            ];
        }

        if (isset($parameters['contextVariables']) && (bool)$parameters['contextVariables'] === true) {
            switch ($pluginType) {
                // Record Listing (records)
                case 'tonictypes_list':
                    $markers[] = [
                        'name'        => $this->pluginSettingsService->getRecordsVarName(),
                        'type'        => '\\' . QueryResultInterface::class,
                        'description' => LocalizationUtility::translate("LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:flexform.records_selection"),
                    ];
                    break;
                // Record Detail (record)
                // Record Dynamic Detail (record)
                case 'tonictypes_detail':
                case 'tonictypes_dynamic':
                    $markers[] = [
                        'name'        => $this->pluginSettingsService->getRecordVarName(),
                        'type'        => '\\' . AbstractRecordModel::class,
                        'description' => LocalizationUtility::translate("LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:tx_tonictypes_domain_model_record"),
                    ];
                    break;
                // Raw Fluid
                case 'tonictypes_plain':
                    break;
                default:
                    break;
            }
        }

        // Check for selected datatype
        $datatypeSelection = ($flex['data']['general_settings']['settings']['datatype_selection']??false);
        $datatypeId = ($parameters['datatype']??0);
        if($datatypeSelection && $datatypeId != 0) {
            if($datatypeUid = (int)reset($flex['data']['general_settings']['settings']['datatype_selection'])) {
                /* @var \K3n\Tonictypes\Domain\Model\Datatype $datatype */
                $datatype = $this->datatypeRepository->findByUid($datatypeUid, false);

                if($datatype instanceof \K3n\Tonictypes\Domain\Model\Datatype) {
                    $markers[] = [
                        'name' => RecordController::DEFAULT_VAR_DATATYPE,
                        'type' => '\\'. $datatype->getFullyQualifiedClassName(),
                        'description' => $datatype->getDescription(),
                    ];
                }
            }
        }

        $pMarkers = ($config['parameters']['markers']??false);
        if (!$pMarkers || !is_array($pMarkers)) {
            $config['parameters']['markers'] = [];
        }

        $config['parameters']['markers'] = array_merge($markers,$config['parameters']['markers']);
        return $this->displayTemplate($config, $parentObject);
    }

    /**
     * Display a query preview from Field/Value Filter Settings
     *
     * @param array $config Configuration Array
     * @param mixed $parentObject Parent Object
     * @return string
     */
    public function displayQueryPreview(array &$config, &$parentObject): string
    {
        $row = $config['row'];
        $flexform = $row['pi_flexform'];

        $flex = $this->walkFlexFormNode($flexform);
        $path = 'data/field_value_filter_setting/lDEF/settings/field_value_filter';
        $filters = \K3n\Tonictypes\Utility\ArrayUtility::getArrayValueByPath($flex, $path);

        $preparedFilters = [];
        foreach ($filters as $_id=>$_filter)
        {
            $preparedFilters[] = [
                'filter_combination'    => reset($_filter['filters']['filter_combination']),
                'field_id'              => reset($_filter['filters']['field_id']),
                'filter_condition'      => reset($_filter['filters']['filter_condition']),
                'field_value'           => $_filter['filters']['field_value'],
                'filter_field'          => reset($_filter['filters']['filter_field']),
            ];

        }

        $statement = $this->recordRepository->getStatementByAdvancedConditions($preparedFilters);

        preg_match_all('/\(.*\)/Usi', $statement, $matches);

        // Predefined different colors for highlighting query parts
        $colors = [
            '#5295E6',
            '#059D54',
            '#9D9105',
            '#DF3907',
            '#DB0EFF',
            '#BB0EFF',
            '#FF4242',
            '#FF4288',
            '#4268FF',
            '#A7FF42',
        ];

        if (isset($matches[0]))
        {
            $i = 0;
            foreach ($matches[0] as $_match)
            {
                $color = $colors[$i];
                $colored = '<div style=\'display:inline;color:{$color};\'>{$_match}</div>';
                $statement = str_replace($_match, $colored, $statement);
                $i++;

                if ($i > count($colors)) $i = 0;
            }
        }

        $config['parameters']['statement'] = $statement;
        return $this->displayTemplate($config, $parentObject);
    }

    /**
     * Display a rendered template from a
     * given path by parameters -> template
     *
     * @param array $config Configuration Array
     * @param mixed $parentObject Parent Object
     * @return string
     */
    public function displayTemplate(array &$config, &$parentObject): string
    {
        $parameters = $config['parameters'];
        $template = (isset($parameters['template']))?$parameters['template']:null;
        $source = (isset($parameters['source']))?$parameters['source']:null;

        /* @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);

        if (!is_null($template)) {
            $templateFile = GeneralUtility::getFileAbsFileName($template);
            if (!file_exists($templateFile)) {
                $template = null;
                $view->setTemplateSource($source);
            }
            $view->setTemplatePathAndFilename($templateFile);
        } else {
            $view->setTemplateSource($source);
        }

        $view->assignMultiple($parameters);
        $view->assign('config', $config);
        return (string)$view->render();
    }
    public function walkFlexFormNode($nodeArray, $valuePointer = 'vDEF')
    {
        if (is_array($nodeArray)) {
            $return = [];
            foreach ($nodeArray as $nodeKey => $nodeValue) {
                if ($nodeKey === $valuePointer) {
                    return $nodeValue;
                }
                if (in_array($nodeKey, ['el', '_arrayContainer'])) {
                    return $this->walkFlexFormNode($nodeValue, $valuePointer);
                }
                if (($nodeKey[0] ?? '') === '_') {
                    continue;
                }
                if (strpos($nodeKey, '.')) {
                    $nodeKeyParts = explode('.', $nodeKey);
                    $currentNode = &$return;
                    $nodeKeyPartsCount = count($nodeKeyParts);
                    for ($i = 0; $i < $nodeKeyPartsCount - 1; $i++) {
                        $currentNode = &$currentNode[$nodeKeyParts[$i]];
                    }
                    $newNode = [next($nodeKeyParts) => $nodeValue];
                    $subVal = $this->walkFlexFormNode($newNode, $valuePointer);
                    $currentNode[key($subVal)] = current($subVal);
                } elseif (is_array($nodeValue)) {
                    if (array_key_exists($valuePointer, $nodeValue)) {
                        $return[$nodeKey] = $nodeValue[$valuePointer];
                    } else {
                        $return[$nodeKey] = $this->walkFlexFormNode($nodeValue, $valuePointer);
                    }
                } else {
                    $return[$nodeKey] = $nodeValue;
                }
            }
            return $return;
        }
        return $nodeArray;
    }

}
