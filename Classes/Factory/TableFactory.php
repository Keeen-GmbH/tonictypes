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

namespace K3n\Tonictypes\Factory;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\SchemaException;
use InvalidArgumentException;
use K3n\Tonictypes\Configuration\ExtensionConfiguration;
use K3n\Tonictypes\Domain\Model\Datatype;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use RuntimeException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TableFactory implements SingletonInterface
{
    /**
     * SQL Reader
     *
     * @var SqlReader
     */
    protected $sqlReader;

    /**
     * Schema Migrator
     *
     * @var SchemaMigrator
     */
    protected $schemaMigrator;

    /**
     * Connection
     *
     * @var Connection
     */
    protected $connection;

    /**
     * @param SqlReader $sqlReader
     */
    public function injectSqlReader(SqlReader $sqlReader): void
    {
        $this->sqlReader = $sqlReader;
    }

    /**
     * @param SchemaMigrator $schemaMigrator
     */
    public function injectSchemaMigrator(SchemaMigrator $schemaMigrator): void
    {
        $this->schemaMigrator = $schemaMigrator;
    }

    /**
     * Gets the database connection model
     *
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        if (!$this->connection) {
            /* @var ConnectionPool $connectionPool */
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $this->connection = $connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        }

        return $this->connection;
    }

    /**
     * Checks if a table exists
     *
     * @param string $tableName
     * @return bool
     * @throws Exception
     */
    public function tableExists(string $tableName): bool
    {

        return $this->getConnection()->createSchemaManager()->tablesExist([$tableName]);
    }

    /**
     * Suggest a table name by a given datatype name
     *
     * @param string $datatypeName
     * @return string
     */
    public function suggestTableNameByDatatypeName(string $datatypeName): string
    {
        $normalized = strtolower(trim($datatypeName));
        // Keep only safe SQL identifier characters derived from the human name.
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');
        $normalized = preg_replace('/_+/', '_', $normalized) ?? '';

        if ($normalized === '') {
            $normalized = 'record';
        }

        $tableName = 'tx_tonictypes_domain_model_record_' . $normalized;
        // MySQL identifier limit is 64 characters.
        if (strlen($tableName) > 64) {
            $tableName = substr($tableName, 0, 64);
            $tableName = rtrim($tableName, '_');
        }

        return $tableName;
    }

    /**
     * Checks if a tablename is allowed for a Tonictypes record table.
     *
     * @param string $tableName
     * @return bool
     */
    public function isAllowedTablename(string $tableName): bool
    {
        if ($tableName === '' || strlen($tableName) > 64) {
            return false;
        }

        // Must be a Tonictypes record table with a safe SQL identifier suffix.
        return (bool)preg_match(
            '/^tx_tonictypes_domain_model_record_[a-z][a-z0-9_]*$/',
            $tableName
        );
    }

    /**
     * Checks if a table is a record table, that
     * was set in a datatype
     *
     * @param string $tableName
     * @return bool
     * @throws Exception
     */
    public function isRecordTable(string $tableName): bool
    {
        // We need to check all datatypes, if the according tablename is set somewhere
        return ($this->getConnection()->select(['uid'], ExtensionConfiguration::EXTENSION_DATATYPE_TABLE, ['tablename' => $tableName])->rowCount() > 0);
    }

    /**
     * Gets all fields of a table
     *
     * @param string $tableName
     * @return array
     * @throws Exception
     */
    public function getTableColumns(string $tableName): array
    {
        $columns = [];

        if ($this->tableExists($tableName)) {
            $columnsList = $this->getConnection()->createSchemaManager()->listTableColumns($tableName);
            if (is_array($columnsList) && count($columnsList) > 0) {
                $columns = array_keys($columnsList);
            }
        }

        return $columns;
    }

    /**
     * Gets all missing columns
     *
     * @param string $tableName
     * @param Datatype $datatype
     * @return array
     * @throws Exception
     */
    public function getMissingColumns(string $tableName, Datatype $datatype): array
    {
        $columns = $this->getTableColumns($tableName);
        $missingColumns = [];
        foreach ($datatype->getFields() as $_field) {
            if (!in_array($_field->getCode(), $columns)) {
                $missingColumns[] = $_field;
            }
        }

        return $missingColumns;
    }

    /**
     * System / reserved columns that belong to every tonictypes record table.
     *
     * @return list<string>
     */
    public function getSystemColumnNames(): array
    {
        return [
            'uid',
            'pid',
            'title',
            'datatype',
            'icon',
            'parent',
            'path_segment',
            'tstamp',
            'crdate',
            'cruser_id',
            'deleted',
            'hidden',
            'starttime',
            'endtime',
            't3ver_oid',
            't3ver_id',
            't3ver_wsid',
            't3ver_label',
            't3ver_state',
            't3ver_stage',
            't3ver_count',
            't3ver_tstamp',
            't3ver_move_id',
            't3_origuid',
            'sorting',
            'sys_language_uid',
            'l10n_parent',
            'l10n_source',
            'l10n_state',
            'l10n_diffsource',
        ];
    }

    /**
     * Columns that exist in the DB table but are no longer mapped to a field on the datatype.
     *
     * @return list<string>
     */
    public function getOrphanColumns(string $tableName, Datatype $datatype): array
    {
        if (!$this->tableExists($tableName)) {
            return [];
        }

        $expected = [];
        foreach ($this->getSystemColumnNames() as $systemColumn) {
            $expected[strtolower($systemColumn)] = true;
        }
        foreach ($datatype->getFields() as $field) {
            $code = trim((string)$field->getCode());
            if ($code !== '') {
                $expected[strtolower($code)] = true;
            }
        }

        $orphans = [];
        foreach ($this->getTableColumns($tableName) as $columnName) {
            $name = (string)$columnName;
            if ($name === '') {
                continue;
            }
            if (isset($expected[strtolower($name)])) {
                continue;
            }
            $orphans[] = $name;
        }

        sort($orphans);

        return $orphans;
    }

    /**
     * Widen numeric leftover columns to text when a field type was changed
     * (e.g. passthrough int → editor/text). Safe for existing data.
     *
     * @return list<string> Altered column names
     */
    public function widenTextColumns(string $tableName, Datatype $datatype): array
    {
        if ($tableName === '' || !$this->tableExists($tableName)) {
            return [];
        }

        $altered = [];
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $existingColumns = $schemaManager->listTableColumns($tableName);

        foreach ($datatype->getFields() as $field) {
            $code = trim((string)$field->getCode());
            if ($code === '' || (!isset($existingColumns[$code]) && !isset($existingColumns[strtolower($code)]))) {
                continue;
            }

            $column = $existingColumns[$code] ?? $existingColumns[strtolower($code)] ?? null;
            if ($column === null) {
                continue;
            }

            $tcaModel = $field->getTca();
            if (!is_object($tcaModel) || !method_exists($tcaModel, 'getSqlCreateStatement')) {
                continue;
            }

            $targetSql = trim((string)$tcaModel->getSqlCreateStatement());
            if ($targetSql === '' || !$this->isTextLikeSqlDefinition($targetSql)) {
                continue;
            }

            $currentType = strtolower((string)$column->getType()->getName());
            if (!$this->isNumericColumnType($currentType)) {
                continue;
            }

            try {
                $connection->executeStatement(sprintf(
                    'ALTER TABLE %s MODIFY %s %s',
                    $connection->quoteIdentifier($tableName),
                    $connection->quoteIdentifier($code),
                    $targetSql
                ));
                $altered[] = $code;
            } catch (\Throwable) {
                // Leave column as-is; FormDataProvider still protects FormEngine.
            }
        }

        return $altered;
    }

    private function isTextLikeSqlDefinition(string $sql): bool
    {
        $normalized = strtolower($sql);
        return str_contains($normalized, 'text')
            || str_contains($normalized, 'varchar')
            || str_contains($normalized, 'char(')
            || str_contains($normalized, 'blob');
    }

    private function isNumericColumnType(string $typeName): bool
    {
        return in_array($typeName, [
            'integer',
            'bigint',
            'smallint',
            'tinyint',
            'int',
            'float',
            'double',
            'decimal',
            'boolean',
            'bool',
        ], true);
    }

    /**
     * Drop unused (orphan) columns from a record table.
     *
     * @param list<string> $columnNames
     * @return array{dropped: list<string>, errors: array<string, string>}
     */
    public function dropColumns(string $tableName, array $columnNames): array
    {
        $dropped = [];
        $errors = [];
        if ($tableName === '' || $columnNames === [] || !$this->tableExists($tableName)) {
            return ['dropped' => $dropped, 'errors' => $errors];
        }

        $connection = $this->getConnection();
        $quotedTable = $connection->quoteIdentifier($tableName);
        foreach ($columnNames as $columnName) {
            $columnName = trim((string)$columnName);
            if ($columnName === '') {
                continue;
            }
            // Never allow dropping reserved system columns.
            if (in_array(strtolower($columnName), array_map('strtolower', $this->getSystemColumnNames()), true)) {
                $errors[$columnName] = 'System column cannot be dropped.';
                continue;
            }
            try {
                $connection->executeStatement(
                    sprintf(
                        'ALTER TABLE %s DROP COLUMN %s',
                        $quotedTable,
                        $connection->quoteIdentifier($columnName)
                    )
                );
                $dropped[] = $columnName;
            } catch (\Throwable $exception) {
                $errors[$columnName] = $exception->getMessage();
            }
        }

        return ['dropped' => $dropped, 'errors' => $errors];
    }

    /**
     * Checks if a table needs an update
     *
     * @param string $tableName
     * @param Datatype $datatype
     * @return bool
     * @throws Exception
     */
    public function tableNeedsUpdate(string $tableName, Datatype $datatype): bool
    {
        $missingColumns = $this->getMissingColumns($tableName, $datatype);
        return (count($missingColumns) > 0);
    }

    /**
     * Gets an array with update statements for
     * a given CREATE TABLE statement
     *
     * @param string $createStatement
     * @return array
     */
    public function getSqlStatements(string $createStatement): array
    {
        return $this->sqlReader->getCreateTableStatementArray($createStatement);
    }

    /**
     * @param string $tableName
     * @return Column[]
     * @throws Exception
     */
    public function getTableLayout(string $tableName): array
    {
        return $this->getConnection()->createSchemaManager()->listTableColumns($tableName);
    }

    /**
     * Gets an array with update statements for
     * a given CREATE TABLE statement
     *
     * @param array $sqlStatements
     * @return array
     * @throws Exception
     * @throws StatementException
     * @throws SchemaException
     * @throws UnexpectedSignalReturnValueTypeException
     */
    public function getUpdateStatements(array $sqlStatements, ?string $restrictToTableName = null): array
    {
        $updateSuggestionsPerConnection = $this->schemaMigrator->getUpdateSuggestions($sqlStatements);
        if ($updateSuggestionsPerConnection === []) {
            return [];
        }

        // We only support the default connection here (same as core DB analyzer UI).
        $updateSuggestions = reset($updateSuggestionsPerConnection);
        if (!is_array($updateSuggestions)) {
            return [];
        }

        unset($updateSuggestions['tables_count'], $updateSuggestions['change_currentValue']);

        if ($restrictToTableName === null || $restrictToTableName === '') {
            return $updateSuggestions;
        }

        $needle = '`' . $restrictToTableName . '`';
        foreach ($updateSuggestions as $operation => $statements) {
            if (!is_array($statements)) {
                continue;
            }
            $updateSuggestions[$operation] = array_filter(
                $statements,
                static fn ($sql): bool => is_string($sql) && str_contains($sql, $needle)
            );
        }

        return $updateSuggestions;
    }

    /**
     * Gets selected statements from
     * update statements
     *
     * @param array $updateStatements
     * @return array
     */
    public function getSelectedStatements(array $updateStatements): array
    {
        $selectedStatements = [];
        foreach (['add', 'change', 'create_table', 'change_table'] as $action) {
            if (empty($updateStatements[$action])) {
                continue;
            }
            $selectedStatements = array_merge(
                $selectedStatements,
                array_combine(array_keys($updateStatements[$action]), array_fill(0, count($updateStatements[$action]), true))
            );
        }

        return $selectedStatements;
    }

    /**
     * Gets a CREATE TABLE statement for a given
     * datatype
     *
     * @param Datatype $datatype
     * @param string $tableName Target Table Name
     * @return string
     */
    public function getCreateTableStatementByDatatype(Datatype $datatype, string $tableName): string
    {
        /* @var StandaloneView $standaloneView */
        $standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
        $templateFile = 'EXT:tonictypes/Resources/Private/Init/CREATE_STATEMENT.sql';
        $templateFile = GeneralUtility::getFileAbsFileName($templateFile);
        $standaloneView->setTemplatePathAndFilename($templateFile);
        $standaloneView->assign('datatype', $datatype);
        $standaloneView->assign('tableName', $tableName);
        return $standaloneView->render();
    }

    /**
     * Migrates table information
     *
     * @param array $updateStatements
     * @param array $selectedStatements
     * @return array
     * @throws DBALException
     * @throws SchemaException
     * @throws StatementException
     * @throws UnexpectedSignalReturnValueTypeException
     */
    public function migrate(array $updateStatements, array $selectedStatements): array
    {
        return $this->schemaMigrator->migrate($updateStatements, $selectedStatements);
    }

    /**
     * Perform add/change/create operations on tables and fields in an optimized,
     * non-interactive, mode using the original doctrine SchemaManager ->toSaveSql()
     * method.
     *
     * @param string[] $statements The CREATE TABLE statements
     * @param bool $createOnly Only perform changes that add fields or create tables
     * @return array[] Error messages for statements that occurred during the installation procedure.
     * @throws DBALException
     * @throws SchemaException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws UnexpectedSignalReturnValueTypeException
     * @throws StatementException
     */
    public function install(array $statements, bool $createOnly): array
    {
        return $this->schemaMigrator->install($statements, $createOnly);
    }

    /**
     * @param string $tableName
     * @return void
     * @throws Exception
     */
    public function dropTable(string $tableName): void
    {
        $this->getConnection()->createSchemaManager()->dropTable($tableName);
    }
}
