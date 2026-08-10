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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Shared Analyze-DB filtering for Tonictypes dynamic record tables.
 *
 * TYPO3 12 SchemaDiff: newTables / changedTables / removedTables
 * TYPO3 13+ SchemaDiff: createdTables / alteredTables / droppedTables
 *
 * @internal
 */
trait TonictypesSchemaDiffFilterTrait
{
    private const RECORD_TABLE_PREFIX = 'tx_tonictypes_domain_model_record_';

    /**
     * @param object $schemaDiff Doctrine or TYPO3 SchemaDiff
     * @return object
     */
    private function applyTonictypesAnalyzeFilters(object $schemaDiff, bool $renameUnused): object
    {
        // install()/publish uses renameUnused=false — never filter in that path.
        if (!$renameUnused) {
            return $schemaDiff;
        }

        $activeTablenames = $this->resolveActiveDatatypeTablenames();
        $createdKey = property_exists($schemaDiff, 'createdTables') ? 'createdTables' : 'newTables';
        $alteredKey = property_exists($schemaDiff, 'alteredTables') ? 'alteredTables' : 'changedTables';
        $droppedKey = property_exists($schemaDiff, 'droppedTables') ? 'droppedTables' : 'removedTables';

        $schemaDiff->{$createdKey} = $this->removeTonictypesRecordTables((array)$schemaDiff->{$createdKey});
        $schemaDiff->{$alteredKey} = $this->removeTonictypesRecordTables((array)$schemaDiff->{$alteredKey});
        $schemaDiff->{$droppedKey} = $this->removeActiveTonictypesRecordTables(
            (array)$schemaDiff->{$droppedKey},
            $activeTablenames
        );
        $schemaDiff->{$droppedKey} = $this->appendOrphanTonictypesRecordTables(
            (array)$schemaDiff->{$droppedKey},
            $activeTablenames
        );

        return $schemaDiff;
    }

    /**
     * @param array<int|string, object> $droppedTables
     * @param array<string, true> $activeTablenames
     * @return array<int|string, object>
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
            if ($this->collectionContainsTableName($droppedTables, $tableName)) {
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
     * @param array<int|string, object> $tables
     * @return array<int|string, object>
     */
    private function removeTonictypesRecordTables(array $tables): array
    {
        return array_filter(
            $tables,
            fn (object $table): bool => !$this->isTonictypesRecordTable($this->resolveTableName($table))
        );
    }

    /**
     * @param array<int|string, object> $tables
     * @param array<string, true> $activeTablenames
     * @return array<int|string, object>
     */
    private function removeActiveTonictypesRecordTables(array $tables, array $activeTablenames): array
    {
        return array_filter(
            $tables,
            function (object $table) use ($activeTablenames): bool {
                $tableName = $this->resolveTableName($table);
                $normalized = $this->stripDeletedPrefix($tableName);

                if (!$this->isTonictypesRecordTable($normalized)) {
                    return true;
                }

                if (isset($activeTablenames[$normalized])) {
                    return false;
                }

                return true;
            }
        );
    }

    private function resolveTableName(object $table): string
    {
        if ($table instanceof Table) {
            return $table->getName();
        }

        if (method_exists($table, 'getOldTable')) {
            $newName = method_exists($table, 'getNewName') ? $table->getNewName() : null;
            // TYPO3 13+: ?string. TYPO3 12 / DBAL 3: Identifier|false.
            if (is_string($newName) && $newName !== '') {
                return $newName;
            }
            if (is_object($newName) && method_exists($newName, 'getName')) {
                $identifierName = trim((string)$newName->getName());
                if ($identifierName !== '') {
                    return $identifierName;
                }
            }
            $oldTable = $table->getOldTable();
            if ($oldTable instanceof Table) {
                return $oldTable->getName();
            }
        }

        if (isset($table->name) && is_string($table->name) && $table->name !== '') {
            return $table->name;
        }

        return '';
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

    /**
     * @param array<int|string, object> $tables
     */
    private function collectionContainsTableName(array $tables, string $tableName): bool
    {
        if (isset($tables[$tableName])) {
            return true;
        }

        foreach ($tables as $table) {
            if ($this->resolveTableName($table) === $tableName) {
                return true;
            }
        }

        return false;
    }
}
