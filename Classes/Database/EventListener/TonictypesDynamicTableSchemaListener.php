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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class TonictypesDynamicTableSchemaListener
{
    public function __invoke(AlterTableDefinitionStatementsEvent $event): void
    {
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
            $schemaManager = $connection->createSchemaManager();
            $tableNames = $schemaManager->listTableNames();
        } catch (\Throwable $exception) {
            // Database may not be ready during early install.
            return;
        }

        foreach ($tableNames as $tableName) {
            if (!str_starts_with((string)$tableName, 'tx_tonictypes_domain_model_record_')) {
                continue;
            }

            try {
                $result = $connection->executeQuery('SHOW CREATE TABLE `' . $tableName . '`')->fetchAssociative();
                $createStatement = (string)($result['Create Table'] ?? '');
                if ($createStatement !== '') {
                    $event->addSqlData($this->normalizeCreateStatement($createStatement));
                }
            } catch (\Throwable $exception) {
                // Skip tables we cannot inspect and continue.
            }
        }
    }

    protected function normalizeCreateStatement(string $statement): string
    {
        // TYPO3 SQL parser expects "CHARACTER SET", not MySQL's "CHARSET" shorthand.
        $statement = preg_replace('/\bDEFAULT\s+CHARSET\s*=\s*/i', 'DEFAULT CHARACTER SET ', $statement) ?? $statement;
        $statement = preg_replace('/\bCHARSET\s*=\s*/i', 'CHARACTER SET ', $statement) ?? $statement;
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
