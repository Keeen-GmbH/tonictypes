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

use K3n\Tonictypes\Domain\Model\Datatype;
use K3n\Tonictypes\Domain\Model\Field;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use K3n\Tonictypes\Icon\TonictypesIconRegistry;
use Symfony\Component\Yaml\Yaml;
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
     * @return string One of: skipped, failed, unchanged, updated, created
     */
    public function writeFromDatatype(Datatype $datatype): string
    {
        $tableName = trim((string)$datatype->getTablename());
        if ($tableName === '' || !$this->isGeneratedRecordTable($tableName)) {
            return 'skipped';
        }

        $tca = $this->buildDatatypeTcaFromDefaultYaml($datatype, $tableName);
        if ($tca === []) {
            return 'failed';
        }

        $relative = 'EXT:tonictypes/Configuration/TCA/' . $tableName . '.php';
        $absFile = GeneralUtility::getFileAbsFileName($relative);
        $fileExisted = is_file($absFile);
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

    /**
     * Remove generated TCA files for record tables that are no longer referenced
     * by any active datatype (typical after a tablename rename + republish).
     *
     * @param list<string> $activeTablenames
     * @return list<string> Removed table names
     */
    public function cleanupOrphanGeneratedTcaFiles(array $activeTablenames): array
    {
        $keep = [];
        foreach ($activeTablenames as $tablename) {
            $tablename = trim((string)$tablename);
            if ($this->isGeneratedRecordTable($tablename)) {
                $keep[$tablename] = true;
            }
        }

        $tcaDir = GeneralUtility::getFileAbsFileName('EXT:tonictypes/Configuration/TCA');
        if (!is_string($tcaDir) || $tcaDir === '' || !is_dir($tcaDir)) {
            return [];
        }

        $removed = [];
        $pattern = $tcaDir . '/' . self::RECORD_TABLE_PREFIX . '*.php';
        foreach (glob($pattern) ?: [] as $tcaFile) {
            $basename = basename((string)$tcaFile, '.php');
            if (!$this->isGeneratedRecordTable($basename)) {
                continue;
            }
            if (isset($keep[$basename])) {
                continue;
            }
            if ($this->backupAndDelete($basename)) {
                $removed[] = $basename;
            }
        }

        return $removed;
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
            static fn (string $value): string => 'extensions-tonictypes-' . $value,
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
