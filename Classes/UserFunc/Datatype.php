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
use K3n\Tonictypes\Factory\TableFactory;
use K3n\Tonictypes\Icon\TonictypesIconRegistry;
use K3n\Tonictypes\Utility\LocalizationUtility as Locale;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Datatype
{
	/**
	 * Datatype Repository
	 * @var DatatypeRepository
	 */
	protected $datatypeRepository;

    /**
     * Table Factory
     * @var TableFactory
     */
	protected $tableFactory;

    /**
     * Tonictypes Icon Registry
     * @var TonictypesIconRegistry
     */
	protected $dvIconRegistry;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->datatypeRepository	= GeneralUtility::makeInstance(DatatypeRepository::class);
	    $this->tableFactory         = GeneralUtility::makeInstance(TableFactory::class);
	    $this->dvIconRegistry       = GeneralUtility::makeInstance(TonictypesIconRegistry::class);
	}

	/**
	 * Populate datatypes
	 * @param array $config Configuration Array
	 * @param mixed $parentObject Parent Object
	 * @return void
	 */
	public function populateDatatypesAction(array &$config, &$parentObject): void
	{
		$pageId = (int)$config['flexParentDatabaseRow']['pid'];

		$options = [];
		$usedIds = [];

        $request = $GLOBALS['TYPO3_REQUEST'];
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams['datatype'])) {
			$dId = (int)$queryParams['datatype'];
			$datatype = $this->datatypeRepository->findByUid($dId, false);
			$icon = IconUtility::getIconByHash($datatype->getIcon());
			$options[] = [
                'label' => $datatype->getInfo(),
                'value' => $datatype->getUid(),
                $icon
            ];
		} else {
			$options[] = [
                'label' => '',
                'value' => '',
            ];
		}

		$datatypesLocalPage = $this->datatypeRepository->findAllOnPid($pageId);
		if ($datatypesLocalPage->count()) {
			$options[] = [
                'label' => Locale::translate('on_this_page'),
                'value' => '--div--'
            ];

			foreach ($datatypesLocalPage as $_datatype) {
				/* @var \K3n\Tonictypes\Domain\Model\Datatype $_datatype */
				$icon = $this->dvIconRegistry->getIconByHash($_datatype->getIcon());
				$options[] = [
                    'label' => $_datatype->getInfo(),
                    'value' => $_datatype->getUid(),
                    $icon
                ];
				$usedIds[] = $_datatype->getUid();
			}
		}

		$datatypesOtherPages = $this->datatypeRepository->findAll(false);

		if ($datatypesOtherPages->count()) {
			$headerSet = false;
			foreach ($datatypesOtherPages as $_datatype) {
                if ($_datatype->getPid() !== $pageId && !in_array($_datatype->getUid(), $usedIds)) {
                    if ($headerSet === false) {
                        $options[] = [
                            'label' => Locale::translate('on_other_pages'),
                            'value' => '--div--'
                        ];
                        $headerSet = true;
                    }

                    /* @var \K3n\Tonictypes\Domain\Model\Datatype $_datatype */
                    $icon = $this->dvIconRegistry->getIconByHash($_datatype->getIcon());
                    $options[] = [
                        'label' => $_datatype->getInfo(),
                        'value' => $_datatype->getUid(),
                        $icon
                    ];
                }
			}
		}

		if (is_array($config['items'])) {
            $config['items'] = array_merge($config['items'], $options);
        } else {
            $config['items'] = $options;
        }
	}

    /**
     * Assume tablename
     * @param array $config Configuration Array
     * @param mixed $parentObject Parent Object
     * @return string
     */
    public function getTableNameField(array &$config, &$parentObject): string
    {
        $templateFile = 'EXT:tonictypes/Resources/Private/Templates/UserFunc/Datatype/TableName.html';
        $templateFile = GeneralUtility::getFileAbsFileName($templateFile);

        /* @var \K3n\Tonictypes\Fluid\View\StandaloneView $view */
        $standaloneView = GeneralUtility::makeInstance(\K3n\Tonictypes\Fluid\View\StandaloneView::class);
        $standaloneView->setTemplatePathAndFilename($templateFile);
        $standaloneView->assign('config', $config);

        $canChangeTableName = 'false';
        $tableName = $config['row']['tablename'];
        if ($tableName === '' || ($this->tableFactory->tableExists($tableName) === false)) {
            $canChangeTableName = 'true';
        }
        $standaloneView->assign('canChangeTableName', $canChangeTableName);

        // We check if the table exists
        $tableExists = $this->tableFactory->tableExists($tableName);
        $standaloneView->assign('tableExists', $tableExists);
        $standaloneView->assign('tableName', $tableName);

        return $standaloneView->render();
    }

    /**
     * Get the class field to show on a datatype
     * @param array $config Configuration Array
     * @param mixed $parentObject Parent Object
     * @return string
     */
    public function getClassField(array &$config, &$parentObject): string
    {
        $templateFile = 'EXT:tonictypes/Resources/Private/Templates/UserFunc/Datatype/Class.html';
        $templateFile = GeneralUtility::getFileAbsFileName($templateFile);

        /* @var \K3n\Tonictypes\Fluid\View\StandaloneView $view */
        $standaloneView = GeneralUtility::makeInstance(\K3n\Tonictypes\Fluid\View\StandaloneView::class);
        $standaloneView->setTemplatePathAndFilename($templateFile);
        $standaloneView->assign('config', $config);

        return $standaloneView->render();
    }
}
