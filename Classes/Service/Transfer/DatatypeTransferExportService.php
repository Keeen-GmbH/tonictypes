<?php
declare(strict_types=1);
/*
 * This file is part of the package k3n/tonictypes.
 */

namespace K3n\Tonictypes\Service\Transfer;

use K3n\Tonictypes\Configuration\ExtensionConfiguration;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use ZipArchive;

class DatatypeTransferExportService
{
    public const FORMAT = 'tonictypes-datatype-bundle';
    public const FORMAT_VERSION = 4;
    public const EXPORT_SCOPE_STRUCTURE = 'structure';

    private const MM_TABLE = 'tx_tonictypes_datatype_field_mm';

    /** @var list<string> */
    private const CONFIG_SYSTEM_COLUMNS = [
        'uid', 'pid', 'tstamp', 'crdate', 'cruser_id', 'deleted', 'hidden',
        'starttime', 'endtime', 't3ver_oid', 't3ver_id', 't3ver_wsid', 't3ver_label',
        't3ver_state', 't3ver_stage', 't3ver_count', 't3ver_tstamp', 't3ver_move_id',
        'sorting', 'sys_language_uid', 'l10n_parent', 'l10n_diffsource',
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    /**
     * @param list<int> $datatypeUids
     */
    public function createArchive(array $datatypeUids): string
    {
        TonictypesProGuard::assertAvailable();

        $datatypeUids = array_values(array_unique(array_map('intval', $datatypeUids)));
        if ($datatypeUids === []) {
            throw new \InvalidArgumentException('No datatypes selected for export.');
        }

        $bundle = $this->buildBundle($datatypeUids);
        $tempFile = GeneralUtility::tempnam('tonictypes_export_', '.zip');

        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create export archive.');
        }

        $zip->addFromString(
            'manifest.yaml',
            Yaml::dump($bundle['manifest'], 4, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE)
        );

        foreach ($bundle['datatypes'] as $exportKey => $payload) {
            $folder = 'datatypes/' . $this->sanitizeFilename($exportKey) . '/';
            $zip->addFromString(
                $folder . 'datatype.yaml',
                Yaml::dump($payload['datatype'], 6, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE)
            );

            $fields = $payload['fields'] ?? [];
            usort(
                $fields,
                static fn(array $a, array $b): int => ((int)($a['sorting'] ?? 0)) <=> ((int)($b['sorting'] ?? 0))
            );
            foreach ($fields as $fieldPayload) {
                if (!is_array($fieldPayload)) {
                    continue;
                }
                $sorting = (int)($fieldPayload['sorting'] ?? 0);
                $stableId = (string)($fieldPayload['stableId'] ?? 'field');
                $fieldFile = sprintf(
                    'fields/%03d_%s.yaml',
                    $sorting > 0 ? $sorting : 1,
                    $this->sanitizeFilename($stableId)
                );
                $fieldExport = $fieldPayload;
                $fieldExport['values'] = $fieldExport['fieldValues'] ?? [];
                unset($fieldExport['fieldValues']);
                $zip->addFromString(
                    $folder . $fieldFile,
                    Yaml::dump($fieldExport, 6, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE)
                );
            }

            foreach ($payload['variables'] ?? [] as $index => $variablePayload) {
                if (!is_array($variablePayload)) {
                    continue;
                }
                $variableName = (string)($variablePayload['variable_name'] ?? ('variable_' . ($index + 1)));
                $zip->addFromString(
                    $folder . 'variables/' . $this->sanitizeFilename($variableName) . '.yaml',
                    Yaml::dump($variablePayload, 6, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE)
                );
            }
        }

        $zip->close();

        return $tempFile;
    }

    /**
     * @param list<int> $datatypeUids
     * @return array{
     *     manifest: array<string, mixed>,
     *     datatypes: array<string, array<string, mixed>>
     * }
     */
    private function buildBundle(array $datatypeUids): array
    {
        $datatypeTable = ExtensionConfiguration::EXTENSION_DATATYPE_TABLE;
        $fieldTable = ExtensionConfiguration::EXTENSION_FIELD_TABLE;
        $datatypeRows = $this->fetchRowsByUids($datatypeTable, $datatypeUids);
        if ($datatypeRows === []) {
            throw new \InvalidArgumentException('Selected datatypes could not be found.');
        }

        $datatypeKeyByUid = [];
        foreach ($datatypeRows as $row) {
            $datatypeKeyByUid[(int)$row['uid']] = (string)($row['tablename'] ?: ('datatype_' . $row['uid']));
        }

        $fieldUidMap = [];
        $allFieldUids = [];
        foreach ($datatypeRows as $datatypeUid => $datatypeRow) {
            foreach ($this->fetchFieldUidsForDatatype((int)$datatypeUid) as $fieldUid) {
                $allFieldUids[] = $fieldUid;
            }
        }
        $allFieldUids = array_values(array_unique($allFieldUids));
        $fieldRows = $this->fetchRowsByUids($fieldTable, $allFieldUids);

        foreach ($fieldRows as $fieldRow) {
            $fieldUid = (int)$fieldRow['uid'];
            $stableId = (string)($fieldRow['id'] ?: ('field_' . $fieldUid));
            $fieldUidMap[$fieldUid] = $stableId;
        }

        $manifestDatatypes = [];
        $exportDatatypes = [];

        foreach ($datatypeRows as $datatypeUid => $datatypeRow) {
            $exportKey = $datatypeKeyByUid[$datatypeUid];
            $tablename = (string)($datatypeRow['tablename'] ?? '');

            $fieldValueTable = ExtensionConfiguration::EXTENSION_FIELD_VALUE_TABLE;
            $variableTable = 'tx_tonictypes_domain_model_variable';
            $fieldUids = $this->fetchFieldUidsForDatatype($datatypeUid);
            $fields = [];
            $sorting = 0;

            foreach ($fieldUids as $fieldUid) {
                if (!isset($fieldRows[$fieldUid])) {
                    continue;
                }
                $sorting++;
                $fieldPayload = $this->sanitizeConfigRow($fieldRows[$fieldUid]);
                $fieldPayload['stableId'] = $fieldUidMap[$fieldUid];
                $fieldPayload['sorting'] = $sorting;
                $fieldPayload['fieldValues'] = $this->exportFieldValues($fieldValueTable, $fieldUid);
                $fieldPayload['field_conf'] = $this->remapFieldConfForExport(
                    (string)($fieldRows[$fieldUid]['field_conf'] ?? ''),
                    $datatypeKeyByUid
                );
                $fields[] = $fieldPayload;
            }

            $datatypePayload = $this->sanitizeConfigRow($datatypeRow);
            $datatypePayload['exportKey'] = $exportKey;
            $datatypePayload['sourcePid'] = (int)($datatypeRow['pid'] ?? 0);
            $thumbnailFieldUid = (int)($datatypeRow['thumbnail_field'] ?? 0);
            if ($thumbnailFieldUid > 0 && isset($fieldUidMap[$thumbnailFieldUid])) {
                $datatypePayload['thumbnailFieldStableId'] = $fieldUidMap[$thumbnailFieldUid];
            }
            unset($datatypePayload['thumbnail_field'], $datatypePayload['fields']);

            $variables = [];
            foreach ($this->fetchVariablesForDatatype($variableTable, $datatypeUid) as $variableRow) {
                $variablePayload = $this->sanitizeConfigRow($variableRow);
                unset($variablePayload['datatype']);
                $variables[] = $variablePayload;
            }

            $exportDatatypes[$exportKey] = [
                'datatype' => $datatypePayload,
                'fields' => $fields,
                'variables' => $variables,
            ];

            $manifestDatatypes[] = [
                'key' => $exportKey,
                'name' => (string)($datatypeRow['name'] ?? ''),
                'tablename' => $tablename,
                'sourcePid' => (int)($datatypeRow['pid'] ?? 0),
                'fieldCount' => count($exportDatatypes[$exportKey]['fields'] ?? []),
                'variableCount' => count($variables),
                'checksum' => hash(
                    'sha256',
                    Yaml::dump($exportDatatypes[$exportKey] ?? [], 6, 2)
                ),
            ];
        }

        return [
            'manifest' => [
                'format' => self::FORMAT,
                'formatVersion' => self::FORMAT_VERSION,
                'exportScope' => self::EXPORT_SCOPE_STRUCTURE,
                'exportedAt' => gmdate('c'),
                'source' => [
                    'typo3Version' => GeneralUtility::makeInstance(Typo3Version::class)->getVersion(),
                    'tonictypesVersion' => ExtensionManagementUtility::getExtensionVersion('tonictypes'),
                    'tonictypesProVersion' => ExtensionManagementUtility::getExtensionVersion('tonictypes_pro'),
                ],
                'datatypes' => $manifestDatatypes,
            ],
            'datatypes' => $exportDatatypes,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRowsByUids(string $table, array $uids): array
    {
        $uids = array_values(array_filter(array_map('intval', $uids)));
        if ($uids === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $rows = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['uid']] = $row;
        }

        return $result;
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

    /**
     * @return list<array<string, mixed>>
     */
    private function exportFieldValues(string $table, int $fieldUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $rows = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'field',
                    $queryBuilder->createNamedParameter($fieldUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchAllAssociative();

        $values = [];
        foreach ($rows as $index => $row) {
            $payload = $this->sanitizeConfigRow($row);
            $dbSorting = (int)($row['sorting'] ?? 0);
            $payload['sorting'] = $dbSorting > 0 ? $dbSorting : (($index + 1) * 256);
            $payload['exportIndex'] = $index;
            unset($payload['field']);
            $values[] = $payload;
        }

        return $values;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchVariablesForDatatype(string $table, int $datatypeUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);

        return $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'datatype',
                    $queryBuilder->createNamedParameter($datatypeUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function sanitizeConfigRow(array $row): array
    {
        foreach (self::CONFIG_SYSTEM_COLUMNS as $column) {
            unset($row[$column]);
        }

        return $row;
    }

    /**
     * @param array<int, string> $datatypeKeyByUid
     */
    private function remapFieldConfForExport(string $fieldConf, array $datatypeKeyByUid): string
    {
        if ($fieldConf === '') {
            return '';
        }

        foreach ($datatypeKeyByUid as $uid => $exportKey) {
            $fieldConf = preg_replace(
                '/(<field index="datatype">\s*<value index="vDEF">)' . $uid . '(<\/value>)/',
                '$1@datatype:' . $exportKey . '$2',
                $fieldConf
            ) ?? $fieldConf;
        }

        return $fieldConf;
    }

    private function sanitizeFilename(string $exportKey): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $exportKey) ?? $exportKey;

        return $filename !== '' ? $filename : 'datatype';
    }
}
