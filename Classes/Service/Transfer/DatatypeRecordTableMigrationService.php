<?php
declare(strict_types=1);
/*
 * This file is part of the package k3n/tonictypes.
 */

namespace K3n\Tonictypes\Service\Transfer;

use K3n\Tonictypes\Configuration\ExtensionConfiguration;
use K3n\Tonictypes\Domain\Model\Datatype;
use K3n\Tonictypes\Domain\Model\Field;
use K3n\Tonictypes\Domain\Repository\DatatypeRepository;
use K3n\Tonictypes\Domain\Repository\FieldRepository;
use K3n\Tonictypes\Factory\ClassFactory;
use K3n\Tonictypes\Factory\TableFactory;
use K3n\Tonictypes\Icon\TonictypesIconRegistry;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Install\Service\ClearCacheService;

class DatatypeRecordTableMigrationService
{
    private const MM_TABLE = 'tx_tonictypes_datatype_field_mm';

    /** @var list<string> */
    private const SAFE_MIGRATION_ACTIONS = ['add', 'create_table'];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly DatatypeRepository $datatypeRepository,
        private readonly FieldRepository $fieldRepository,
        private readonly TableFactory $tableFactory,
        private readonly ClassFactory $classFactory,
        private readonly ClearCacheService $clearCacheService,
        private readonly PersistenceManager $persistenceManager,
    ) {
    }

    /**
     * Creates or updates the dynamic record table and TCA for a datatype.
     *
     * @return array{
     *     tableName: string,
     *     created: bool,
     *     updated: bool,
     *     tcaStatus: string,
     *     notes: list<string>
     * }
     */
    public function ensureRecordTable(int $datatypeUid, bool $preferFreshSchema = false): array
    {
        TonictypesProGuard::assertAvailable();

        $datatype = $this->loadDatatypeWithFields($datatypeUid);
        if (!$datatype instanceof Datatype) {
            throw new \RuntimeException(sprintf('Datatype uid %d could not be loaded.', $datatypeUid));
        }

        $tableName = $datatype->getTablename();
        if ($tableName === '' || !$this->tableFactory->isAllowedTablename($tableName)) {
            throw new \RuntimeException(sprintf('Invalid record table name for datatype uid %d.', $datatypeUid));
        }

        if (!str_starts_with($tableName, 'tx_tonictypes_domain_model_record_')) {
            throw new \RuntimeException(sprintf('Table "%s" is not a Tonictypes record table.', $tableName));
        }

        $notes = [];
        $tableExisted = $this->tableFactory->tableExists($tableName);
        $wasCreated = false;
        $wasUpdated = false;

        $createStatement = $this->tableFactory->getCreateTableStatementByDatatype($datatype, $tableName);
        $sqlStatements = $this->tableFactory->getSqlStatements($createStatement);
        $updateStatements = $this->tableFactory->getUpdateStatements($sqlStatements, $tableName);
        $safeSelectedStatements = $this->getSafeSelectedStatements($updateStatements);
        $destructiveChangeCount = $this->countDestructiveChanges($updateStatements);

        if ($tableExisted) {
            $rowCount = $this->countTableRows($tableName);
            if ($rowCount === 0) {
                $this->dropTableSilently($tableName);
                $notes[] = sprintf(
                    'Removed empty existing table "%s" before applying imported schema.',
                    $tableName
                );
                $tableExisted = false;
            } elseif ($destructiveChangeCount > 0) {
                $notes[] = sprintf(
                    'Table "%s" contains %d record(s). Skipped %d destructive schema change(s); only new columns were added.',
                    $tableName,
                    $rowCount,
                    $destructiveChangeCount
                );
            }
        }

        if (!$tableExisted) {
            $this->assertSchemaInstallationSucceeded(
                $this->tableFactory->install($sqlStatements, true),
                sprintf('Could not create table "%s"', $tableName)
            );
            $wasCreated = true;
        } elseif ($safeSelectedStatements !== []) {
            $this->assertSchemaInstallationSucceeded(
                $this->tableFactory->migrate($sqlStatements, $safeSelectedStatements),
                sprintf('Could not update table "%s"', $tableName)
            );
            $wasUpdated = true;
        } elseif ($this->tableFactory->tableNeedsUpdate($tableName, $datatype)) {
            if ($destructiveChangeCount > 0 && $this->countTableRows($tableName) > 0) {
                $notes[] = sprintf(
                    'Table "%s" still has schema differences that must be migrated manually in the Tonictypes table wizard.',
                    $tableName
                );
            } else {
                $this->assertSchemaInstallationSucceeded(
                    $this->tableFactory->install($sqlStatements, true),
                    sprintf('Could not update table "%s"', $tableName)
                );
                $wasUpdated = true;
            }
        }

        if ($preferFreshSchema && $wasUpdated && $destructiveChangeCount > 0) {
            $notes[] = sprintf(
                'Imported datatype "%s" was merged into an existing record table. Review the table schema in the Tonictypes backend if fields are missing.',
                $tableName
            );
        }

        $tcaStatus = $this->writeTcaPhpFile($datatype, $tableName);
        $this->clearAutoloadAndCache();

        return [
            'tableName' => $tableName,
            'created' => $wasCreated,
            'updated' => $wasUpdated && !$wasCreated,
            'tcaStatus' => $tcaStatus,
            'notes' => $notes,
        ];
    }

    /**
     * @param array<string, mixed> $updateStatements
     */
    private function getSafeSelectedStatements(array $updateStatements): array
    {
        $selectedStatements = [];
        foreach (self::SAFE_MIGRATION_ACTIONS as $action) {
            if (empty($updateStatements[$action]) || !is_array($updateStatements[$action])) {
                continue;
            }
            $selectedStatements = array_merge(
                $selectedStatements,
                array_combine(
                    array_keys($updateStatements[$action]),
                    array_fill(0, count($updateStatements[$action]), true)
                )
            );
        }

        return $selectedStatements;
    }

    /**
     * @param array<string, mixed> $updateStatements
     */
    private function countDestructiveChanges(array $updateStatements): int
    {
        $count = 0;
        foreach (['change', 'change_table'] as $action) {
            if (!empty($updateStatements[$action]) && is_array($updateStatements[$action])) {
                $count += count($updateStatements[$action]);
            }
        }

        return $count;
    }

    private function countTableRows(string $tableName): int
    {
        if (!$this->tableFactory->tableExists($tableName)) {
            return 0;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);

        return (int)$queryBuilder
            ->count('uid')
            ->from($tableName)
            ->executeQuery()
            ->fetchOne();
    }

    private function dropTableSilently(string $tableName): void
    {
        try {
            $this->tableFactory->dropTable($tableName);
        } catch (\Throwable) {
            // Ignore drop failures and let the subsequent install report the real issue.
        }
    }

    private function loadDatatypeWithFields(int $datatypeUid): ?Datatype
    {
        $this->persistenceManager->clearState();
        $datatype = $this->datatypeRepository->findByUid($datatypeUid, false);
        if (!$datatype instanceof Datatype) {
            return null;
        }

        $fieldUids = $this->fetchFieldUidsForDatatype($datatypeUid);
        if ($fieldUids === []) {
            $datatype->setFields(new ObjectStorage());
            return $datatype;
        }

        $fieldsByUid = [];
        foreach ($this->fieldRepository->findByUids($fieldUids) as $field) {
            if ($field instanceof Field) {
                $fieldsByUid[(int)$field->getUid()] = $field;
            }
        }

        $storage = new ObjectStorage();
        foreach ($fieldUids as $fieldUid) {
            if (isset($fieldsByUid[$fieldUid])) {
                $storage->attach($fieldsByUid[$fieldUid]);
            }
        }
        $datatype->setFields($storage);

        return $datatype;
    }

    /**
     * @return list<int>
     */
    private function fetchFieldUidsForDatatype(int $datatypeUid): array
    {
        $fieldTable = ExtensionConfiguration::EXTENSION_FIELD_TABLE;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::MM_TABLE);
        $rows = $queryBuilder
            ->select('mm.uid_foreign', 'mm.sorting')
            ->from(self::MM_TABLE, 'mm')
            ->innerJoin(
                'mm',
                $fieldTable,
                'field',
                $queryBuilder->expr()->eq('field.uid', $queryBuilder->quoteIdentifier('mm.uid_foreign'))
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'mm.uid_local',
                    $queryBuilder->createNamedParameter($datatypeUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('field.deleted', 0)
            )
            ->orderBy('mm.sorting')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(static fn(array $row): int => (int)$row['uid_foreign'], $rows);
    }

    private function writeTcaPhpFile(Datatype $datatype, string $tableName): string
    {
        $tca = $this->buildDatatypeTcaFromDefaultYaml($datatype, $tableName);
        if ($tca === []) {
            return 'failed';
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
            . 'return ' . var_export($tca, true) . ";\n";

        $old = @file_get_contents($absFile);
        if (is_string($old) && md5($old) === md5($contents)) {
            return 'unchanged';
        }

        if (GeneralUtility::writeFile($absFile, $contents) !== true) {
            return 'failed';
        }

        return $fileExisted ? 'updated' : 'created';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDatatypeTcaFromDefaultYaml(Datatype $datatype, string $tableName): array
    {
        $tcaDefaultFile = GeneralUtility::getFileAbsFileName(
            'EXT:tonictypes/Resources/Private/Init/tx_tonictypes_domain_model_default.yaml'
        );
        $tcaDefaultYaml = @file_get_contents($tcaDefaultFile) ?: '';
        if ($tcaDefaultYaml === '') {
            return [];
        }

        $standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
        $standaloneView->setTemplateSource($tcaDefaultYaml);

        $iconRegistry = GeneralUtility::makeInstance(TonictypesIconRegistry::class);
        $typeiconClasses = $iconRegistry->getIcons(
            ['EXT:tonictypes/Resources/Public/Icons/Datatype'],
            'extensions-tonictypes-',
            true,
            false
        );
        $keys = array_keys($typeiconClasses);
        $values = array_map(
            static fn(string $value): string => 'extensions-tonictypes-' . $value,
            $keys
        );
        $icons = array_combine($keys, $values) ?: [];
        $icons['default'] = 'extensions-tonictypes-' . $datatype->getIcon();

        $standaloneView->assignMultiple([
            'datatype' => $datatype,
            'tableName' => $tableName,
            'typeiconClasses' => $icons,
            'fields' => implode(',', array_keys($datatype->getApproachableFields())),
            'iconFile' => $typeiconClasses['extensions-tonictypes-' . $datatype->getIcon()] ?? '',
        ]);

        $tca = Yaml::parse($standaloneView->render());
        if (!is_array($tca)) {
            return [];
        }

        foreach ($datatype->getFields() as $field) {
            if (!$field instanceof Field) {
                continue;
            }
            $tcaModel = $field->getTca();
            if (is_object($tcaModel) && method_exists($tcaModel, 'setDatatype') && method_exists($tcaModel, 'getTca')) {
                $tcaModel->setDatatype($datatype);
                $tca['columns'][$field->getCode()] = $tcaModel->getTca();
            }
        }

        return $tca;
    }

    /**
     * @param array<int|string, string> $errors
     */
    private function assertSchemaInstallationSucceeded(array $errors, string $context): void
    {
        $messages = $this->extractSchemaErrorMessages($errors);
        if ($messages === []) {
            return;
        }

        throw new \RuntimeException(sprintf('%s: %s', $context, implode('; ', $messages)));
    }

    /**
     * @param array<int|string, string> $errors
     * @return list<string>
     */
    private function extractSchemaErrorMessages(array $errors): array
    {
        $messages = [];
        foreach ($errors as $error) {
            if (is_string($error) && $error !== '') {
                $messages[] = $error;
            }
        }

        return $messages;
    }

    private function clearAutoloadAndCache(): void
    {
        if (!defined('TYPO3_COMPOSER_MODE')) {
            $this->classFactory->dumpAutoload();
        }

        $this->clearCacheService->clearAll();
    }
}
