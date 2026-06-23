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

use K3n\Tonictypes\Domain\Repository\DatatypeRepository;
use K3n\Tonictypes\Domain\Repository\FieldRepository;
use K3n\Tonictypes\Factory\TableFactory;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use K3n\Tonictypes\Form\Element\UserElement;
use K3n\Tonictypes\Service\FlexForm\FlexFormService;
use K3n\Tonictypes\Service\Settings\FieldSettingsService;
use K3n\Tonictypes\Service\Settings\Plugin\PluginSettingsService;
use K3n\Tonictypes\Utility\LocalizationUtility;
use K3n\Tonictypes\Utility\StringUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;

class Field
{
	/**
	 * Field Repository
	 *
	 * @var FieldRepository
	 */
	protected $fieldRepository;

    /**
     * Record Repository
     *
     * @var DatatypeRepository
     */
    protected $datatypeRepository;

	/**
	 * FlexForm Service
	 *
	 * @var FlexFormService
	 */
	protected $flexFormService;

    /**
     * Plugin Settings Service
     *
     * @var PluginSettingsService
     */
    protected $pluginSettingsService;

    /**
     * Field Settings Service
     *
     * @var FieldSettingsService
     */
    protected $fieldSettingsService;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->fieldRepository = GeneralUtility::makeInstance(FieldRepository::class);
        $this->datatypeRepository = GeneralUtility::makeInstance(DatatypeRepository::class);
		$this->flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
		$this->pluginSettingsService = GeneralUtility::makeInstance(PluginSettingsService::class);
        $this->fieldSettingsService = GeneralUtility::makeInstance(FieldSettingsService::class);
	}

	/**
	 * Displays the generated field identifier for frontend identification
	 *
	 * @param array $config
	 * @param array $parentObject
	 * @return string
	 */
	public function displayGeneratedFieldIdentifier(array &$config, &$parentObject)
	{
		$row = $config['row'];
		$text = ($row['variable_name'] != '')?$row['variable_name']:$row['frontend_label'];
		$code = StringUtility::createCodeFromString($text);
		$title = LocalizationUtility::translate('formvalue_access_to_hidden_field');
		$recordName = $this->pluginSettingsService->getRecordVarName();

		if (!$code) {
            $code = '<em>generated on save</em>';
        }

        $table = $config['table'];
        $uid = $row['uid'];

        /* @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->assignMultiple([
            'row' => $row,
            'text' => $text,
            'table' => $table,
            'uid' => $uid,
            'frontendLabelFieldName' => "input[data-formengine-input-name=\"data[{$table}][{$uid}][frontend_label]\"]",
            'variableNameFieldName' => "input[data-formengine-input-name=\"data[{$table}][{$uid}][variable_name]\"]",
            'frontendLabelValueFieldName' => "input[name=\"data[{$table}][{$uid}][frontend_label]\"]",
            'variableValueFieldName' => "input[name=\"data[{$table}][{$uid}][variable_name]\"]",
            'title' => $title,
            'recordName' => $recordName,
            'code' => $code,
        ]);

        $template = 'EXT:tonictypes/Resources/Private/Templates/UserFunc/Field/GeneratedFieldIdentifier.html';
        $template = GeneralUtility::getFileAbsFileName($template);
        $view->setTemplatePathAndFilename($template);

		return $view->render();
	}

	/**
	 * Displays all available field ids for helping while
	 * using display conditions
	 *
	 * @param array $config
	 * @param array $parentObject
	 * @return string
	 */
	public function displayAvailableFieldIds(array &$config, &$parentObject)
	{
	    /* @var Template $templateUserFunc */
        $templateUserFunc = GeneralUtility::makeInstance(Template::class);
        $row = $config['row'];
        $pid = $row['pid'];
        $fields = $this->fieldRepository->findAllOnPid($pid);
        if(count($fields) > 0) {
            $config['parameters']['fields'] = $fields;
        } else {
            return '<br />'.LocalizationUtility::translate('flexform.available_field_ids.no_fields_found');
        }

        return $templateUserFunc->displayTemplate($config, $parentObject);
	}

	/**
	 * Populate fields
	 *
	 * @param array $config Configuration Array
	 * @param array $parentObject Parent Object
	 * @return array
	 */
	public function populateFields(array &$config, &$parentObject)
	{
		$options = [];

		$fields = $this->fieldRepository->findAll(false);

		$sorted = [];
		foreach ($fields as $_field)
		{
			$pid = $_field->getPid();
			$sorted[$pid][] = $_field;
		}

		ksort($sorted);

		foreach ($sorted as $pid=>$fields) {
		    $page = BackendUtility::getRecord('pages',$pid);
		    $title = "[PID:{$pid}]"." ".$page['title'];
		    $options[] = [
                'label' => $title,
                'value' => '--div--'
            ];
			foreach ($fields as $_field) {
			    /* @var \K3n\Tonictypes\Domain\Model\Field $_field */
				$label = $this->_getFieldLabel($_field);
				$options[] = [
                    'label' => $label,
                    'value' => $_field->getVariableName()
                ];
			}
		}

		$config['items'] = array_merge($config['items'], $options);
	}

    /**
     * Populate fields
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return array
     */
    public function populateFieldIds(array &$config, &$parentObject)
    {
        $options = [];

        $fields = $this->fieldRepository->findAll(false);

        $sorted = [];
        foreach ($fields as $_field)
        {
            $pid = $_field->getPid();
            $sorted[$pid][] = $_field;
        }

        ksort($sorted);

        foreach ($sorted as $pid=>$fields) {
            $page = BackendUtility::getRecord('pages',$pid);
            $title = "[PID:{$pid}]"." ".$page['title'];
            $options[] = [$title, '--div--'];
            foreach ($fields as $_field) {
                /* @var \K3n\Tonictypes\Domain\Model\Field $_field */
                $label = $this->_getFieldLabel($_field);
                $options[] = [$label, $_field->getUid()];
            }
        }

        $config['items'] = array_merge($config['items'], $options);
    }

    /**
     * Populate fields
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return array
     */
    public function populateFieldsAndPalettesOnCurrentPid(array &$config, &$parentObject)
    {
        $this->populateFieldsOnCurrentPid($config, $parentObject);

        // Retrieve possible palettes
        $datatypeId = $config['row']['uid'];
        /* @var \K3n\Tonictypes\Domain\Model\Datatype $datatype */
        $datatype = $this->datatypeRepository->findByUid($datatypeId,false);

        if ($datatype instanceof \K3n\Tonictypes\Domain\Model\Datatype) {
            $palettes = $datatype->getPalettes();

            $options = [];
            foreach ($palettes as $_paletteName=>$_fields) {
                $paletteString = implode(', ', $_fields);
                $options[] = ["PALETTE: {$paletteString}", $_paletteName];
            }
            $config['items'] = array_merge($config['items'], $options);
        }
    }

	/**
	 * Populate fields
	 *
	 * @param array $config Configuration Array
	 * @param array $parentObject Parent Object
	 * @return array
	 */
	public function populateFieldsOnCurrentPid(array &$config, &$parentObject)
	{
		$pid = $config['flexParentDatabaseRow']['pid'];

		$options = [];
		$fields = $this->fieldRepository->findAllOnPids([$pid], true);

		$sortedFields = [];

		foreach ($fields as $_field)
		{
			$type = $_field->getType();
			$sortedFields[$type][] = $_field;
		}

		ksort($sortedFields);

		foreach ($sortedFields as $_type=>$_fields)
		{
			foreach ($_fields as $_field)
			{
				$label = $this->_getFieldLabel($_field);
				$options[] = [$label, $_field->getUid()];
			}
		}

        $config['items'] = array_merge($config['items'], $options);
	}

	/**
	 * Populate fields
	 *
	 * @param array $config Configuration Array
	 * @param array $parentObject Parent Object
	 * @return array
	 */
	public function populateFieldsOnStoragePages(array &$config, &$parentObject)
	{
		$pages = $config['flexParentDatabaseRow']['pages'];

		if (!is_array($pages))
			$pages = GeneralUtility::intExplode(',', $pages);

		$pids = [];
		foreach ($pages as $_page)
		{
			if (is_array($_page)) {
				$pids[] = $_page['uid'];
			}
			else {
				preg_match('/(?<table>.*)_(?<uid>[0-9]{0,11})|.*/', $_page, $match);

				if (is_array($match))
				{
					if (isset($match['uid']))
						$pids[] = $match['uid'];
					else
						$pids[] = $match[0];
				}
			}

		}

		$options = [];
		$fields = $this->fieldRepository->findAllOnPids($pids);

		foreach ($fields as $_field)
		{
			$label = $this->_getFieldLabel($_field);
			$options[] = [$label, $_field->getUid()];
		}

		$config['items'] = array_merge($config['items'], $options);
	}

    /**
     * Populate fields by a table name setting
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return void
     */
    public function populateFieldsFromDatatype(array &$config, &$parentObject)
    {
        $datatypeId = null;
        if (array_key_exists('datatype', $config['row'])) {
            if(is_array($config['row']['datatype'])) {
                $datatypeId = reset($config['row']['datatype']);
            } else {
                $datatypeId = $config['row']['datatype'];
            }
        }
        if(!null === $datatypeId && $datatypeId > 0) {
            $datatype = $this->datatypeRepository->findByUid($datatypeId);

            if ($datatype instanceof \K3n\Tonictypes\Domain\Model\Datatype) {
                $config['row']['foreign_table'] = $datatype->getTablename();
            }

            $this->populateFieldsFromTable($config, $parentObject);
        }

    }

    /**
     * Populate fields by a table name setting
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return void
     */
    public function populateFieldsFromTtContent(array &$config, &$parentObject)
    {
        $config['row']['foreign_table'] = 'tt_content';
        $this->populateFieldsFromTable($config, $parentObject);
    }

    /**
     * Populate fields by a table name setting
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return void
     */
    public function populateFieldsFromTable(array &$config, &$parentObject)
    {
        $row = $config['row'];
        $table = (is_array($row['foreign_table'])) ? reset($row['foreign_table']) : $row['foreign_table'];
        $options = [];

        if ($table == '' || !$table) return;

        // Retrieve all fields of the selected table
        /* @var Connection $query */
        $query = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);

        $columns = $query->fetchAllAssociative("SHOW COLUMNS FROM {$table}");

        if (is_array($columns) && count($columns) > 0) {
            foreach ($columns as $_column) {
                $field = $_column['Field'];
                $options[] = [
                    'label' => $field,
                    'value' => $field
                ];
            }
        }

        $config['items'] = array_merge($config['items'], $options);
    }

    /**
     * Populate fieldtypes
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @throws \Exception
     */
    public function populateFieldtypes(array &$config, &$parentObject)
    {
        $pid = $config['effectivePid'] ?? 0;
        $fieldConfiguration = $this->fieldSettingsService->getFieldConfiguration($pid);

        $options = [];
        foreach ($fieldConfiguration as $_id=>$_config) {
            $options[] = [
                'label' => $_config['label'],
                'value' => $_id,
                'icon' => $_config['icon'],
            ];
        }

        if (count($options) <= 0) {
            $exceptionMessage = LocalizationUtility::translate('message.no_fieldtypes_found_please_check_ts');
            throw new \Exception($exceptionMessage);
        }

        if (is_array($config['items']))
            $config['items'] = array_merge($config['items'], $options);
        else
            $config['items'] = $options;
    }

    /**
     * Populates a list of subfields when
     * selected field is a model
     *
     * @param array $config
     * @param UserElement $parentObject
     * @return string
     */
    public function displayFlexFormSubFields(array &$config, UserElement &$parentObject)
    {
        $html = '';
        $flexFormRowData = $parentObject->getData('flexFormRowData');
        $fieldUid = (int)$flexFormRowData['field']['vDEF'][0];
        /** @var \K3n\Tonictypes\Domain\Model\Field $field */
        $field = $this->fieldRepository->findOneBy(['uid' => (int)$fieldUid]);

        if($field instanceof \K3n\Tonictypes\Domain\Model\Field) {

            $frontendType = $field->getFrontendType();

            if(class_exists($frontendType)) {
                // Selected field is a domain/model or object storage field
                // We get the related information
                if($datatypeUid = $field->getConfig('datatype')) {
                    $datatype = $this->datatypeRepository->findOneBy(['uid' => (int)$datatypeUid]);
                    if($datatype instanceof \K3n\Tonictypes\Domain\Model\Datatype) {
                        $tableName = $datatype->getTablename();
                    }
                } else {
                    try {
                        /** @var DataMapFactory $dataMapFactory */
                        $dataMapFactory = GeneralUtility::makeInstance(DataMapFactory::class);
                        $dataMap = $dataMapFactory->buildDataMap($frontendType);
                        $tableName = $dataMap->getTableName();
                    } catch (\Throwable $exception) {
                        $tableName = '';
                    }
                }

                if($tableName != '') {
                    /** @var TableFactory $tableFactory */
                    $tableFactory = GeneralUtility::makeInstance(TableFactory::class);
                    if($tableFactory->tableExists($tableName)) {
                        $configFt = $config;
                        $configFt['row']['foreign_table'] = $tableName;
                        $configFt['items'] = [
                            ['',LocalizationUtility::translate('LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:flexform.default_sort')],
                        ];
                        $this->populateFieldsFromTable($configFt, $parentObject);

                        if(!empty($configFt['items'])) {
                            $options = '';
                            foreach($configFt['items'] as $_item) {
                                $selected = ($_item[0] == $config['itemFormElValue'])?'selected':'';
                                $options.="<option value=\"{$_item[0]}\" {$selected}>{$_item[1]}</option>";
                            }

                            $label = LocalizationUtility::translate('LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:flexform.sub_field');
                            $description = LocalizationUtility::translate('LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:flexform.sub_field.description');
                            $fieldId = $this->resolveItemFormElementId($config);
                            $html .= "
                                <label class=\"t3js-formengine-label\">{$label}</label>
                                <br />
                                <div class=\"alert alert-info\">{$description}</div>
                                <div class=\"formengine-field-item t3js-formengine-field-item\">
                                    <div class=\"form-control-wrap\">
                                        <select id=\"{$fieldId}\" name=\"{$config['itemFormElName']}\" class=\"form-control form-control-adapt\">".$options."</select>
                                    </div>
                                </div>
                            ";
                        }
                    }
                }
            }
        }

        return $html;
    }

    protected function resolveItemFormElementId(array $config): string
    {
        $itemFormElId = (string)($config['itemFormElID'] ?? $config['itemFormElId'] ?? '');
        if ($itemFormElId !== '') {
            return $itemFormElId;
        }

        $itemFormElName = (string)($config['itemFormElName'] ?? $config['itemFormElname'] ?? '');
        $fieldId = str_replace(['][', '[', ']'], ['_', '_', ''], $itemFormElName);
        $fieldId = (string)preg_replace('/[^a-zA-Z0-9_:-]/', '_', $fieldId);
        $fieldId = (string)preg_replace('/_+/', '_', $fieldId);
        $fieldId = trim($fieldId, '_');
        if ($fieldId === '') {
            return 'x_tonictypes_field';
        }

        return (string)preg_replace('/^[^a-zA-Z]/', 'x', $fieldId);
    }

	/**
	 * Transforms a label for a field
	 *
	 * @param \K3n\Tonictypes\Domain\Model\Field $field
	 * @return string
	 */
	protected function _getFieldLabel(\K3n\Tonictypes\Domain\Model\Field $field)
	{
		return "[{$field->getPid()}] " . strtoupper($field->getType()) . ": " . $field->getFrontendLabel() . " {".$field->getCode()."}";
	}
}
