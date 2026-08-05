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

namespace K3n\Tonictypes\Tca;

use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Rebuilds TcaSchema only when $GLOBALS['TCA'] is not yet reflected in the factory.
 *
 * TYPO3 builds the schema during bootstrap before tonictypes middleware adds dynamic
 * tables. Unconditional rebuild() on every request is expensive; synchronize() skips
 * the hot path when the structure token is unchanged and all tables are present.
 *
 * Note: rebuild() updates in-memory schemata only — it does not write the PHP cache file.
 */
final class TcaSchemaSynchronizer
{
    private static ?string $lastSyncToken = null;

    /**
     * @return bool True when rebuild() was executed
     */
    public function synchronize(TcaSchemaFactory $factory): bool
    {
        $tca = $GLOBALS['TCA'] ?? null;
        if (!is_array($tca) || $tca === []) {
            return false;
        }

        $token = $this->buildToken($tca);
        if (self::$lastSyncToken === $token && !$this->hasMissingTables($factory, $tca)) {
            return false;
        }

        $factory->rebuild($tca);
        self::$lastSyncToken = $token;

        return true;
    }

    /**
     * @internal Exposed for tests
     */
    public function reset(): void
    {
        self::$lastSyncToken = null;
    }

    /**
     * @param array<string, mixed> $tca
     */
    private function hasMissingTables(TcaSchemaFactory $factory, array $tca): bool
    {
        foreach (array_keys($tca) as $tableName) {
            if (!is_string($tableName) || $tableName === '') {
                continue;
            }
            if (!$factory->has($tableName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lightweight structure fingerprint: tables, column/type counts, flex DS hashes.
     *
     * @param array<string, mixed> $tca
     */
    private function buildToken(array $tca): string
    {
        $parts = [];
        $tables = array_keys($tca);
        sort($tables);

        foreach ($tables as $table) {
            if (!is_string($table) || $table === '' || !is_array($tca[$table] ?? null)) {
                continue;
            }

            $columns = is_array($tca[$table]['columns'] ?? null) ? count($tca[$table]['columns']) : 0;
            $types = is_array($tca[$table]['types'] ?? null) ? count($tca[$table]['types']) : 0;
            $parts[] = $table . ':' . $columns . ':' . $types;

            $ds = $tca[$table]['columns']['field_conf']['config']['ds'] ?? null;
            if (is_string($ds) && $ds !== '') {
                $parts[] = $table . ':ds:' . md5($ds);
            } elseif (is_array($ds)) {
                $parts[] = $table . ':ds:' . md5(serialize($ds));
            }

            if (is_array($tca[$table]['types'] ?? null)) {
                foreach ($tca[$table]['types'] as $typeKey => $typeConf) {
                    if (!is_array($typeConf)) {
                        continue;
                    }
                    $typeDs = $typeConf['columnsOverrides']['field_conf']['config']['ds'] ?? null;
                    if (is_string($typeDs) && $typeDs !== '') {
                        $parts[] = $table . ':t:' . (string)$typeKey . ':' . md5($typeDs);
                    }
                }
            }
        }

        return hash('sha256', implode('|', $parts));
    }
}
