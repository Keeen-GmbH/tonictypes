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

namespace K3n\Tonictypes\Service\Tca;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles generated datatype TCA PHP files under Configuration/TCA/.
 */
final class DatatypeTcaFileService
{
    private const RECORD_TABLE_PREFIX = 'tx_tonictypes_domain_model_record_';
    private const BACKUP_DIR = 'uploads/tx_tonictypes/tca_backup';

    /**
     * Copy the generated TCA file to uploads/tx_tonictypes/tca_backup/, then delete the original.
     * The original is only removed when the backup was written successfully.
     */
    public function backupAndDelete(string $tableName): bool
    {
        $tcaFile = $this->resolveGeneratedTcaFile($tableName);
        if ($tcaFile === null) {
            return false;
        }

        if (!$this->backupFile($tcaFile, $tableName)) {
            return false;
        }

        return @unlink($tcaFile);
    }

    public function resolveGeneratedTcaFile(string $tableName): ?string
    {
        if (!$this->isGeneratedRecordTable($tableName)) {
            return null;
        }

        $tcaFile = GeneralUtility::getFileAbsFileName('EXT:tonictypes/Configuration/TCA/' . $tableName . '.php');
        if (!is_string($tcaFile) || $tcaFile === '' || !is_file($tcaFile)) {
            return null;
        }

        return $tcaFile;
    }

    public function isGeneratedRecordTable(string $tableName): bool
    {
        return $tableName !== ''
            && str_starts_with($tableName, self::RECORD_TABLE_PREFIX)
            && (bool)preg_match('/^[a-z0-9_]+$/', $tableName);
    }

    private function backupFile(string $tcaFile, string $tableName): bool
    {
        $backupDir = Environment::getPublicPath() . '/' . self::BACKUP_DIR;
        if (!is_dir($backupDir)) {
            GeneralUtility::mkdir_deep($backupDir);
            $this->protectBackupDirectory($backupDir);
        }
        if (!is_dir($backupDir) || !is_writable($backupDir)) {
            return false;
        }

        // .php.bak avoids accidental execution if uploads/ is web-served.
        $backupPath = $backupDir . '/' . $tableName . '_' . date('YmdHis') . '.php.bak';
        return @copy($tcaFile, $backupPath) && is_file($backupPath);
    }

    private function protectBackupDirectory(string $backupDir): void
    {
        $htaccess = $backupDir . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }
        $index = $backupDir . '/index.html';
        if (!is_file($index)) {
            @file_put_contents($index, '');
        }
    }
}
