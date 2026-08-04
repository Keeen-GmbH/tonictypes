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

namespace K3n\Tonictypes\Database\Schema;

use Doctrine\DBAL\Schema\Table;
use K3n\Tonictypes\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\ConnectionMigrator as CoreConnectionMigrator;
use TYPO3\CMS\Core\Database\Schema\SchemaDiff as Typo3SchemaDiff;
use TYPO3\CMS\Core\Database\Schema\TableDiff as Typo3TableDiff;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Filters Tonictypes dynamic record tables out of Install Tool schema *suggestions*.
 *
 * Important: filtering must NOT run during SchemaMigrator::install()/publish, otherwise
 * CREATE TABLE for tx_tonictypes_domain_model_record_* is suppressed and tables never appear.
 *
 * - Analyze (renameUnused=true): hide create/alter for record tables; protect active drops;
 *   surface orphan drops.
 * - Install/publish (renameUnused=false): leave diff untouched so Tonictypes can create/alter.
 */
class ConnectionMigrator extends CoreConnectionMigrator
{
    private const RECORD_TABLE_PREFIX = 'tx_tonictypes_domain_model_record_';

    protected function buildSchemaDiff(bool $renameUnused = true): Typo3SchemaDiff
    {
        $schemaDiff = parent::buildSchemaDiff($renameUnused);

        // install()/publish uses renameUnused=false — never filter in that path.
        if (!$renameUnused) {
            return $schemaDiff;
        }

        $activeTablenames = $this->resolveActiveDatatypeTablenames();

        $schemaDiff->createdTables = $this->removeTonictypesRecordTables($schemaDiff->createdTables);
        $schemaDiff->alteredTables = $this->removeTonictypesRecordTables($schemaDiff->alteredTables);
        $schemaDiff->droppedTables = $this->removeActiveTonictypesRecordTables(
            $schemaDiff->droppedTables,
            $activeTablenames
        );
        $schemaDiff->droppedTables = $this->appendOrphanTonictypesRecordTables(
            $schemaDiff->droppedTables,
            $activeTablenames
        );

        return $schemaDiff;
    }

    /**
     * Force-suggest DROP for orphan record tables even when leftover TCA still
     * keeps them in the expected schema (common after rename without cleanup).
     *
     * @param array<string, Typo3TableDiff|Table> $droppedTables
     * @param array<string, true> $activeTablenames
     * @return array<string, Typo3TableDiff|Table>
     */
    private function appendOrphanTonictypesRecordTables(array $droppedTables, array $activeTablenames): array
    {
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
            $schemaManager = $connection->createSchemaManager();
            $tableNames = $schemaManager->listTableNames();
        } catch (\Throwable) {
            return $droppedTables;
        }

        foreach ($tableNames as $tableName) {
            $tableName = (string)$tableName;
            if (!$this->isTonictypesRecordTable($tableName)) {
                continue;
            }
            if (isset($activeTablenames[$tableName])) {
                continue;
            }
            if (isset($droppedTables[$tableName])) {
                continue;
            }

            try {
                $droppedTables[$tableName] = $schemaManager->introspectTable($tableName);
            } catch (\Throwable) {
                // Skip tables that cannot be introspected.
            }
        }

        return $droppedTables;
    }

    /**
     * @param array<string, Typo3TableDiff|Table> $tables
     * @return array<string, Typo3TableDiff|Table>
     */
    private function removeTonictypesRecordTables(array $tables): array
    {
        return array_filter(
            $tables,
            fn(Typo3TableDiff|Table $table): bool => !$this->isTonictypesRecordTable($this->resolveTableName($table))
        );
    }

    /**
     * @param array<string, Typo3TableDiff|Table> $tables
     * @param array<string, true> $activeTablenames
     * @return array<string, Typo3TableDiff|Table>
     */
    private function removeActiveTonictypesRecordTables(array $tables, array $activeTablenames): array
    {
        return array_filter(
            $tables,
            function (Typo3TableDiff|Table $table) use ($activeTablenames): bool {
                $tableName = $this->resolveTableName($table);
                $normalized = $this->stripDeletedPrefix($tableName);

                // Non-tonictypes tables stay untouched.
                if (!$this->isTonictypesRecordTable($normalized)) {
                    return true;
                }

                // Active datatype tables must never be dropped via Analyze.
                if (isset($activeTablenames[$normalized])) {
                    return false;
                }

                // Orphans remain visible as drop candidates.
                return true;
            }
        );
    }

    private function resolveTableName(Typo3TableDiff|Table $table): string
    {
        if ($table instanceof Table) {
            return $table->getName();
        }

        return $table->getNewName() ?? $table->getOldTable()->getName();
    }

    private function stripDeletedPrefix(string $tableName): string
    {
        $tableName = $this->trimIdentifierQuotes($tableName);
        if (str_starts_with($tableName, $this->deletedPrefix)) {
            return substr($tableName, strlen($this->deletedPrefix));
        }

        return $tableName;
    }

    private function isTonictypesRecordTable(string $tableName): bool
    {
        $tableName = $this->trimIdentifierQuotes($tableName);

        return $tableName !== ''
            && str_starts_with($tableName, self::RECORD_TABLE_PREFIX)
            && (bool)preg_match('/^[a-z0-9_]+$/', $tableName);
    }

    /**
     * @return array<string, true>
     */
    private function resolveActiveDatatypeTablenames(): array
    {
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(ExtensionConfiguration::EXTENSION_DATATYPE_TABLE);
            if (!$connection->createSchemaManager()->tablesExist([ExtensionConfiguration::EXTENSION_DATATYPE_TABLE])) {
                return [];
            }

            $queryBuilder = $connection->createQueryBuilder();
            $rows = $queryBuilder
                ->select('tablename')
                ->from(ExtensionConfiguration::EXTENSION_DATATYPE_TABLE)
                ->where(
                    $queryBuilder->expr()->eq(
                        'deleted',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq('tablename', $queryBuilder->createNamedParameter(''))
                )
                ->executeQuery()
                ->fetchFirstColumn();
        } catch (\Throwable) {
            return [];
        }

        $active = [];
        foreach ($rows as $tablename) {
            $tablename = trim((string)$tablename);
            if ($this->isTonictypesRecordTable($tablename)) {
                $active[$tablename] = true;
            }
        }

        return $active;
    }

    private function trimIdentifierQuotes(string $identifier): string
    {
        return str_replace(['`', '"', '[', ']'], '', $identifier);
    }
}
