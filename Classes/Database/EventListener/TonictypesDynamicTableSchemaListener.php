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

namespace K3n\Tonictypes\Database\EventListener;

use K3n\Tonictypes\Configuration\ExtensionConfiguration;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Registers CREATE TABLE statements for *active* Tonictypes record tables only.
 *
 * Purpose for Analyze Database Structure:
 * - Active datatype tables are mirrored from the live DB so the Install Tool does not
 *   propose create/alter/drop for schema that Tonictypes publish owns.
 * - Orphan tables (prefix still matches, but no non-deleted datatype points at them
 *   after a rename/delete) are intentionally NOT registered here, so they can surface
 *   as drop candidates when they are also absent from TCA.
 */
final class TonictypesDynamicTableSchemaListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const RECORD_TABLE_PREFIX = 'tx_tonictypes_domain_model_record_';

    public function __invoke(AlterTableDefinitionStatementsEvent $event): void
    {
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
            $schemaManager = $connection->createSchemaManager();
            $tableNames = $schemaManager->listTableNames();
        } catch (\Throwable $exception) {
            $this->logger?->warning($exception->getMessage(), ['exception' => $exception]);
            return;
        }

        $activeTablenames = $this->resolveActiveDatatypeTablenames($connection);
        if ($activeTablenames === []) {
            return;
        }

        foreach ($tableNames as $tableName) {
            $tableName = (string)$tableName;
            if (!$this->isTonictypesRecordTable($tableName)) {
                continue;
            }
            // Ignore orphans from schema registration — leave them visible to Analyze.
            if (!isset($activeTablenames[$tableName])) {
                continue;
            }

            try {
                $createStatement = $this->resolveCreateTableStatement($connection, $tableName);
                if ($createStatement !== '') {
                    $event->addSqlData($createStatement);
                }
            } catch (\Throwable $exception) {
                $this->logger?->warning($exception->getMessage(), ['exception' => $exception]);
            }
        }
    }

    /**
     * Prefer MySQL SHOW CREATE TABLE (best fidelity for Analyze mirroring).
     * Fall back to DBAL platform SQL for PostgreSQL/SQLite (TYPO3 12–14 CI).
     */
    private function resolveCreateTableStatement(Connection $connection, string $tableName): string
    {
        try {
            $result = $connection->executeQuery(
                'SHOW CREATE TABLE ' . $connection->quoteIdentifier($tableName)
            )->fetchAssociative();
            $createStatement = (string)($result['Create Table'] ?? '');
            if ($createStatement !== '') {
                return $this->normalizeCreateStatement($createStatement);
            }
        } catch (\Throwable) {
            // Non-MySQL platforms (or restricted grants) — use DBAL introspection.
        }

        $table = $connection->createSchemaManager()->introspectTable($tableName);
        $sql = $connection->getDatabasePlatform()->getCreateTableSQL($table);
        if (is_array($sql)) {
            $sql = implode(";\n", $sql);
        }

        return $this->normalizeCreateStatement((string)$sql);
    }

    /**
     * @return array<string, true> tablename => true
     */
    private function resolveActiveDatatypeTablenames(Connection $connection): array
    {
        $datatypeTable = ExtensionConfiguration::EXTENSION_DATATYPE_TABLE;
        try {
            if (!$connection->createSchemaManager()->tablesExist([$datatypeTable])) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        try {
            $queryBuilder = $connection->createQueryBuilder();
            $rows = $queryBuilder
                ->select('tablename')
                ->from($datatypeTable)
                ->where(
                    $queryBuilder->expr()->eq(
                        'deleted',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq('tablename', $queryBuilder->createNamedParameter(''))
                )
                ->executeQuery()
                ->fetchFirstColumn();
        } catch (\Throwable $exception) {
            $this->logger?->warning($exception->getMessage(), ['exception' => $exception]);
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

    private function isTonictypesRecordTable(string $tableName): bool
    {
        return $tableName !== ''
            && str_starts_with($tableName, self::RECORD_TABLE_PREFIX)
            && (bool)preg_match('/^[a-z0-9_]+$/', $tableName);
    }

    protected function normalizeCreateStatement(string $statement): string
    {
        // TYPO3 SQL parser expects "CHARACTER SET", not MySQL's "CHARSET" shorthand.
        $statement = preg_replace('/\bDEFAULT\s+CHARSET\s*=\s*/i', 'DEFAULT CHARACTER SET ', $statement) ?? $statement;
        $statement = preg_replace('/\bCHARSET\s*=\s*/i', 'CHARACTER SET ', $statement) ?? $statement;
        // MySQL 8.0.13+ returns DEFAULT (NULL) for nullable text/blob columns in SHOW CREATE TABLE.
        // TYPO3's SQL parser only understands DEFAULT NULL (without parentheses).
        $statement = preg_replace('/\bDEFAULT\s+\(NULL\)/i', 'DEFAULT NULL', $statement) ?? $statement;
        // MySQL 8.4 returns charset introducers in string defaults, e.g. DEFAULT (_utf8mb4'').
        // TYPO3's SQL parser does not understand this syntax; strip the introducer and parens.
        $statement = preg_replace('/\bDEFAULT\s+\(_[a-zA-Z0-9]+\'((?:[^\']|\'\')*)\'\)/i', "DEFAULT '$1'", $statement) ?? $statement;
        // Strip MySQL table options after the column/index definition block.
        // This avoids parser issues with DB vendor specific tail clauses.
        $lastClosingParenthesisPos = strrpos($statement, ')');
        if ($lastClosingParenthesisPos !== false) {
            $statement = substr($statement, 0, $lastClosingParenthesisPos + 1);
        }
        $statement = rtrim($statement, " \t\n\r\0\x0B;") . ';';

        return $statement;
    }
}
