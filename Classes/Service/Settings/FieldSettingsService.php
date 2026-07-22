<?php
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

declare(strict_types=1);
namespace K3n\Tonictypes\Service\Settings;

use K3n\Tonictypes\Domain\Model\Field;
use K3n\Tonictypes\Service\Transfer\DatatypeTransferImportService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FieldSettingsService extends AbstractSettingsService implements SingletonInterface
{
    private const UNSUPPORTED_FIELDTYPE_FLEXFORM = 'FILE:EXT:tonictypes/Configuration/FlexForms/Field/Unsupported.xml';

    public function getFieldConfiguration(int $pid = 0): array
    {
        $fieldConfiguration = $this->getConfiguration('plugin.tx_tonictypes.fieldtypes', $pid);
        return is_array($fieldConfiguration) ? $fieldConfiguration : [];
    }

    public function getFieldTypeConfiguration(string $type, int $pid = 0): array
    {
        $fieldConfig = $this->getFieldConfiguration($pid);
        return array_key_exists($type, $fieldConfig) ? $fieldConfig[$type] : [];
    }

    /**
     * @return array<string, string>
     */
    public function getTcaFlexFormConfiguration(int $pid = 0): array
    {
        $emptyDs = 'FILE:EXT:tonictypes/Configuration/FlexForms/Field/Empty.xml';
        $dsConfig = ['default' => $emptyDs];
        $typesConfiguration = $this->getFieldConfiguration($pid);
        $fieldtypesLoaded = $typesConfiguration !== [];

        foreach ($typesConfiguration as $typeId => $typeConfig) {
            if (!is_string($typeId) || $typeId === '') {
                continue;
            }
            $dsConfig[$typeId] = isset($typeConfig['flexform']) && $typeConfig['flexform'] !== ''
                ? 'FILE:' . $typeConfig['flexform']
                : $emptyDs;
        }

        $premiumWithoutPro = !ExtensionManagementUtility::isLoaded('tonictypes_pro');
        $premiumTypes = array_fill_keys(DatatypeTransferImportService::PREMIUM_FIELD_TYPES, true);

        foreach ($this->getUsedFieldTypesFromDatabase() as $usedType) {
            if ($usedType === '' || isset($dsConfig[$usedType])) {
                continue;
            }
            // Pro-only types without Pro, or unknown types after successful TS load.
            // Never flag free types as unsupported when fieldtypes failed to load.
            if (($premiumWithoutPro && isset($premiumTypes[$usedType])) || $fieldtypesLoaded) {
                $dsConfig[$usedType] = self::UNSUPPORTED_FIELDTYPE_FLEXFORM;
            }
        }

        return $dsConfig;
    }

    /**
     * @return list<string>
     */
    public function getUsedFieldTypesFromDatabase(): array
    {
        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_tonictypes_domain_model_field');
            $queryBuilder->getRestrictions()->removeAll();
            $rows = $queryBuilder
                ->select('type')
                ->from('tx_tonictypes_domain_model_field')
                ->where(
                    $queryBuilder->expr()->eq(
                        'deleted',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                )
                ->groupBy('type')
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (\Throwable $exception) {
            return [];
        }

        $types = [];
        foreach ($rows as $row) {
            $type = trim((string)($row['type'] ?? ''));
            if ($type !== '') {
                $types[] = $type;
            }
        }

        return array_values(array_unique($types));
    }

    public function getValueGeneratorClass(Field $field, int $pid = 0): ?string
    {
        $valueClass = $this->getFieldConfiguration($pid)[$field->getType()]['value'] ?? '';
        return $valueClass !== '' ? $valueClass : null;
    }

    public function getFieldTypesWithValueGenerator(int $pid = 0): array
    {
        $fieldTypes = [];
        foreach ($this->getFieldConfiguration($pid) as $fieldType => $config) {
            if (!empty($config['value']) && class_exists((string)$config['value'])) {
                $fieldTypes[] = $fieldType;
            }
        }
        return $fieldTypes;
    }
}
