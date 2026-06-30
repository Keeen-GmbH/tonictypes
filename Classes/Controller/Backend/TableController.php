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

namespace K3n\Tonictypes\Controller\Backend;

use K3n\Tonictypes\Domain\Model\Datatype;
use K3n\Tonictypes\Domain\Repository\DatatypeRepository;
use K3n\Tonictypes\Factory\TableFactory;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use K3n\Tonictypes\Service\Settings\Plugin\PluginSettingsService;
use K3n\Tonictypes\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class TableController extends AbstractBackendController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected function writeTcaPhpFile(string $tableName, array $tca): string
    {
        if (!str_starts_with($tableName, 'tx_tonictypes_domain_model_record_')) {
            return 'skipped';
        }

        $relative = 'EXT:tonictypes/Configuration/TCA/' . $tableName . '.php';
        $absFile = GeneralUtility::getFileAbsFileName($relative);
        $fileExisted = file_exists($absFile);
        $absDir = dirname($absFile);
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0777, true);
        }

        $contents = "<?php\n"
            . "declare(strict_types=1);\n"
            . "defined('TYPO3') or die();\n\n"
            . "return " . var_export($tca, true) . ";\n";

        $old = @file_get_contents($absFile);
        if (is_string($old) && md5($old) === md5($contents)) {
            return 'unchanged';
        }

        $written = GeneralUtility::writeFile($absFile, $contents);
        if ($written !== true) {
            return 'failed';
        }

        return $fileExisted ? 'updated' : 'created';
    }

    protected function buildDatatypeTcaFromDefaultYaml(Datatype $datatype, string $tableName): array
    {
        $tcaDefaultFile = GeneralUtility::getFileAbsFileName('EXT:tonictypes/Resources/Private/Init/tx_tonictypes_domain_model_default.yaml');
        $tcaDefaultYaml = @file_get_contents($tcaDefaultFile) ?: '';
        if ($tcaDefaultYaml === '') {
            return [];
        }

        /** @var \K3n\Tonictypes\Fluid\View\StandaloneView $standaloneView */
        $standaloneView = GeneralUtility::makeInstance(\K3n\Tonictypes\Fluid\View\StandaloneView::class);
        $standaloneView->setTemplateSource($tcaDefaultYaml);

        /** @var \K3n\Tonictypes\Icon\TonictypesIconRegistry $iconRegistry */
        $iconRegistry = GeneralUtility::makeInstance(\K3n\Tonictypes\Icon\TonictypesIconRegistry::class);
        $typeiconClasses = $iconRegistry->getIcons(['EXT:tonictypes/Resources/Public/Icons/Datatype'], 'extensions-tonictypes-', true, false);
        $keys = array_keys($typeiconClasses);
        $values = array_map(static function ($value) {
            return 'extensions-tonictypes-' . $value;
        }, $keys);
        $icons = array_combine($keys, $values);
        $icons['default'] = 'extensions-tonictypes-' . $datatype->getIcon();

        $standaloneView->assignMultiple([
            'datatype' => $datatype,
            'tableName' => $tableName,
            'typeiconClasses' => $icons,
            'fields' => implode(',', array_keys($datatype->getApproachableFields())),
            'iconFile' => $typeiconClasses['extensions-tonictypes-' . $datatype->getIcon()] ?? '',
        ]);

        $tca = \Symfony\Component\Yaml\Yaml::parse($standaloneView->render()) ?: [];
        foreach ($datatype->getFields() as $_field) {
            if ($_field instanceof \K3n\Tonictypes\Domain\Model\Field) {
                $tcaModel = $_field->getTca();
                if (is_object($tcaModel) && method_exists($tcaModel, 'setDatatype') && method_exists($tcaModel, 'getTca')) {
                    $tcaModel->setDatatype($datatype);
                    $tca['columns'][$_field->getCode()] = call_user_func([$tcaModel, 'getTca']);
                }
            }
        }

        return is_array($tca) ? $tca : [];
    }

    /**
     * @var TableFactory
     */
    protected $tableFactory;

    /**
     * @var DatatypeRepository
     */
    protected $datatypeRepository;

    /**
     * @var PluginSettingsService
     */
    protected $pluginSettingsService;

    /**
     * @param TableFactory $tableFactory
     */
    public function injectTableFactory(TableFactory $tableFactory)
    {
        $this->tableFactory = $tableFactory;
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
     * Get a view instance
     * @return StandaloneView
     */
    public function getView(int $pid = 0): StandaloneView
    {
        /* @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        /** @var array $layoutRootPaths */
        $layoutRootPaths = $this->pluginSettingsService->getLayoutPaths($pid);
        /** @var array $templateRootPaths */
        $templateRootPaths = $this->pluginSettingsService->getTemplatePaths($pid);
        /** @var array $partialRootPaths */
        $partialRootPaths = $this->pluginSettingsService->getPartialPaths($pid);

        $view->setLayoutRootPaths($layoutRootPaths);
        $view->setTemplateRootPaths($templateRootPaths);
        $view->setPartialRootPaths($partialRootPaths);

        return $view;
    }

    /**
     * Shows or hides a record through an AJAX call
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     */
    public function tableStatusAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $tableName = strip_tags($parsedBody['tableName']);
        $datatypeId = $parsedBody['datatypeId'];
        $tableExists = false;
        $tcaFileExists = false;

        if(!is_null($tableName) && $tableName != '') {
            $tableExists = $this->tableFactory->tableExists($tableName);
            $tcaFile = GeneralUtility::getFileAbsFileName('EXT:tonictypes/Configuration/TCA/' . $tableName . '.php');
            $tcaFileExists = file_exists($tcaFile);
        }

        $datatype = null;
        $createStatement = null;
        $updateStatements = [];
        $missingColumns = [];
        $tableLayout = null;
        if (is_numeric($datatypeId)) {
            /* @var Datatype $datatype */
            $datatype = $this->datatypeRepository->findByUid((int)$datatypeId);
            if ($datatype instanceof Datatype && $tableExists) {
                try {
                    $createStatement = $this->tableFactory->getCreateTableStatementByDatatype($datatype, $tableName);
                    $sqlStatements = $this->tableFactory->getSqlStatements($createStatement);
                    $updateStatements = $this->tableFactory->getUpdateStatements($sqlStatements, $tableName);
                    $missingColumns = $this->tableFactory->getMissingColumns($tableName, $datatype);
                    $tableLayout = $this->tableFactory->getTableLayout($tableName);
                } catch (\Exception $e) {
                    $response = GeneralUtility::makeInstance(Response::class);
                    $response->getBody()->write(json_encode(['success' => false,'html' => $e->getMessage()]));
                    return $response;
                }
            }
        }
        $pid = $this->resolveBackendPageId($request);
        $view = $this->getView($pid);
        $templateFile = GeneralUtility::getFileAbsFileName('EXT:tonictypes/Resources/Private/Templates/UserFunc/Table/Status.html');
        $view->setTemplatePathAndFilename($templateFile);

        $tableNeedsUpdate = (count($missingColumns)>0);
        if(is_array($updateStatements) && array_key_exists('change', $updateStatements) && is_array($updateStatements['change']) && !empty($updateStatements['change'])) {
            $tableNeedsUpdate = true;
        }

        $variables = [
            'tableName' => $tableName,
            'tableExists' => $tableExists,
            'tcaFileExists' => $tcaFileExists,
            'datatype'  => $datatype,
            'createStatement' => $createStatement,
            'updateStatements' => $updateStatements,
            'tableNeedsUpdate' => $tableNeedsUpdate,
            'missingColumns' => $missingColumns,
            'tableLayout' => $tableLayout,
            'tableNameWrong' => !$this->tableFactory->isAllowedTablename($tableName),
        ];

        $view->assignMultiple($variables);
        $output = $view->render();

        $response = GeneralUtility::makeInstance(Response::class);
        $response->getBody()->write(json_encode(['success' => true,'html' => $output]));
        return $response;
    }

    /**
     * Deletes a table
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     */
    public function tableDeleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $tableName = strip_tags($parsedBody['tableName']);
        $tableExists = false;

        if(!is_null($tableName) && $tableName != '') {
            $tableExists = $this->tableFactory->tableExists($tableName);
        }

        if($tableExists) {
            try {
                $this->tableFactory->dropTable($tableName);
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage(), ['exception' => $e]);
            }
        }

        if (str_starts_with($tableName, 'tx_tonictypes_domain_model_record_')) {
            $tcaFile = GeneralUtility::getFileAbsFileName('EXT:tonictypes/Configuration/TCA/' . $tableName . '.php');
            if (is_string($tcaFile) && $tcaFile !== '' && file_exists($tcaFile)) {
                @unlink($tcaFile);
            }
        }

        $this->clearAutoloadAndCache();

        return $this->tableStatusAction($request);
    }

    public function tableGenerateTcaAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $tableName = strip_tags($parsedBody['tableName'] ?? '');
        $datatypeId = (int)($parsedBody['datatypeId'] ?? 0);

        $success = false;
        try {
            $datatype = $this->datatypeRepository->findByUid($datatypeId);
            if (!$datatype instanceof Datatype) {
                throw new \RuntimeException('Datatype not found');
            }

            if ($tableName === '' || !$this->tableFactory->tableExists($tableName)) {
                throw new \RuntimeException('Table does not exist');
            }

            $tca = $this->buildDatatypeTcaFromDefaultYaml($datatype, $tableName);
            if ($tca === []) {
                throw new \RuntimeException('Could not build TCA');
            }

            $status = $this->writeTcaPhpFile($tableName, $tca);
            $success = in_array($status, ['created', 'updated', 'unchanged'], true);
        } catch (\Throwable $e) {
            $success = false;
        }

        $response = GeneralUtility::makeInstance(Response::class);
        $response->getBody()->write(json_encode([
            'success' => $success,
        ]));
        return $response;
    }

    /**
     * Migrates a table
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function tableMigrateAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->clearAutoloadAndCache();

        $parsedBody = $request->getParsedBody();
        $tableName = strip_tags($parsedBody['tableName']);
        $datatypeId = (int)$parsedBody['datatypeId'];
        $datatype = $this->datatypeRepository->findByUid($datatypeId);

        if(!($datatype instanceof Datatype)) {
            $response = GeneralUtility::makeInstance(Response::class);
            $response->getBody()->write(json_encode([
                'success' => false,
                'html' => LocalizationUtility::translate('LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:table.migrate.message.records_not_created_yet')
            ]));
            return $response;
        }

        $pid = $this->resolveBackendPageId($request);
        $view = $this->getView($pid);
        $templateFile = 'EXT:tonictypes/Resources/Private/Templates/UserFunc/Table/Migrate.html';
        $templateFile = GeneralUtility::getFileAbsFileName($templateFile);
        $view->setTemplatePathAndFilename($templateFile);

        try {
            $createStatement    = $this->tableFactory->getCreateTableStatementByDatatype($datatype, $tableName);
            $sqlStatements      = $this->tableFactory->getSqlStatements($createStatement);
            $updateStatements   = $this->tableFactory->getUpdateStatements($sqlStatements, $tableName);
            $selectedStatements = $this->tableFactory->getSelectedStatements($updateStatements);
        } catch (\Exception $e) {
            $response = GeneralUtility::makeInstance(Response::class);
            $response->getBody()->write(json_encode(['success' => false, 'html' => $e->getMessage()]));
            return $response;
        }

        ////////////////////////////////////////////////////////////
        // MIGRATION ROUTINE
        ////////////////////////////////////////////////////////////
        try {
            $result = $this->tableFactory->migrate($sqlStatements, $selectedStatements);
        } catch (\Exception $e) {
            $response = GeneralUtility::makeInstance(Response::class);
            $response->getBody()->write(json_encode(['success' => false, 'html' => $e->getMessage()]));
            return $response;
        }
        ////////////////////////////////////////////////////////////

        // Generate TCA file as part of the "Create/Update table" click flow.
        $tcaFileStatus = 'failed';
        $tcaFilePath = 'Configuration/TCA/' . $tableName . '.php';
        try {
            $tca = $this->buildDatatypeTcaFromDefaultYaml($datatype, $tableName);
            if (is_array($tca) && $tca !== []) {
                $tcaFileStatus = $this->writeTcaPhpFile($tableName, $tca);
            } else {
                $tcaFileStatus = 'failed';
            }
        } catch (\Throwable $e) {
            $tcaFileStatus = 'failed';
        }

        $this->clearAutoloadAndCache();

        $variables = [
            'tableName' => $tableName,
            'datatype'  => $datatype,
            'createStatement' => $createStatement,
            'updateStatements' => $updateStatements,
            'result' => $result,
            'tcaFileStatus' => $tcaFileStatus,
            'tcaFilePath' => $tcaFilePath,
        ];

        $view->assignMultiple($variables);
        $output = $view->render();

        $response = GeneralUtility::makeInstance(Response::class);
        $response->getBody()->write(json_encode(['success' => true,'html' => $output]));
        return $response;
    }

    protected function resolveBackendPageId(ServerRequestInterface $request): int
    {
        $referer = $request->getHeaderLine('referer');
        if ($referer === '') {
            return 0;
        }
        $refererQuery = parse_url($referer, PHP_URL_QUERY);
        if (!is_string($refererQuery) || $refererQuery === '') {
            return 0;
        }
        parse_str($refererQuery, $refererQueryParams);
        if (isset($refererQueryParams['id'])) {
            $pageId = (int)$refererQueryParams['id'];
            if ($pageId > 0) {
                return $pageId;
            }
        }
        if (isset($refererQueryParams['returnUrl']) && is_string($refererQueryParams['returnUrl'])) {
            $returnUrlQuery = parse_url($refererQueryParams['returnUrl'], PHP_URL_QUERY);
            if (is_string($returnUrlQuery) && $returnUrlQuery !== '') {
                parse_str($returnUrlQuery, $returnUrlQueryParams);
                $pageId = (int)($returnUrlQueryParams['id'] ?? 0);
                if ($pageId > 0) {
                    return $pageId;
                }
            }
        }

        return 0;
    }
}
