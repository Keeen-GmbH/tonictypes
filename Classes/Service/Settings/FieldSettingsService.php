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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FieldSettingsService extends AbstractSettingsService implements SingletonInterface
{
    private const UNSUPPORTED_FIELDTYPE_FLEXFORM = 'FILE:EXT:tonictypes/Configuration/FlexForms/Field/Unsupported.xml';

	/**
	 * Field Configuration
	 *
	 * @var array
	 */
	protected $fieldConfig = [];

	/**
	 * Gets the complete field configuration from
	 * the plugin settings in typoscript
	 * @return array
	 */
	public function getFieldConfiguration(int $pid = 0): array
	{
	    $fieldConfiguration = $this->getConfiguration('plugin.tx_tonictypes.fieldtypes', $pid);
        return GeneralUtility::removeDotsFromTS($fieldConfiguration);
	}

	/**
	 * Gets the according field configuration by
	 * a given field type identifier
	 *
	 * @param string $type
	 * @return array
	 */
	public function getFieldTypeConfiguration(string $type, int $pid = 0): array
	{
	    $fieldConfig = $this->getFieldConfiguration($pid);
	    if (array_key_exists($type, $fieldConfig)) {
	        return $fieldConfig[$type];
        }
	    return [];
	}

    /**
     * Gets the tca flexform configuration
     * @return array
     */
	public function getTcaFlexFormConfiguration(int $pid = 0): array
    {
        $emptyDs = 'FILE:EXT:tonictypes/Configuration/FlexForms/Field/Empty.xml';
        $dsConfig = [
            'default' => $emptyDs,
        ];
        $typesConfiguration = $this->getFieldConfiguration($pid);
        foreach ($typesConfiguration as $_id => $_config) {
            if (!is_string($_id) || $_id === '') {
                continue;
            }
            // Always register a DS key so v12/v13 ds_pointerField never misses.
            $dsConfig[$_id] = isset($_config['flexform']) && $_config['flexform'] !== ''
                ? 'FILE:' . $_config['flexform']
                : $emptyDs;
        }

        // Keep editing records whose type was removed / requires Pro.
        foreach ($this->getUsedFieldTypesFromDatabase() as $usedType) {
            if ($usedType === '' || isset($dsConfig[$usedType])) {
                continue;
            }
            $dsConfig[$usedType] = self::UNSUPPORTED_FIELDTYPE_FLEXFORM;
        }

        return $dsConfig;
    }

    /**
     * Distinct field.type values currently stored in the database.
     *
     * @return list<string>
     */
    public function getUsedFieldTypesFromDatabase(): array
    {
        try {
            $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                ->getQueryBuilderForTable('tx_tonictypes_domain_model_field');
            $queryBuilder->getRestrictions()->removeAll();
            $rows = $queryBuilder
                ->select('type')
                ->from('tx_tonictypes_domain_model_field')
                ->where(
                    $queryBuilder->expr()->eq(
                        'deleted',
                        $queryBuilder->createNamedParameter(0, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)
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

    /**
     * @param Field $field
     * @return string|null
     */
    public function getValueGeneratorClass(Field $field, int $pid = 0): ?string
    {
        $fieldConfiguration = $this->getFieldConfiguration($pid);
        if(isset($fieldConfiguration[$field->getType()]['value']) && $fieldConfiguration[$field->getType()]['value'] != '') {
            return $fieldConfiguration[$field->getType()]['value'];
        }
        return null;
    }

    /**
     * Gets an array with all declared field types that
     * are configured with a value generator class
     * @return array
     */
    public function getFieldTypesWithValueGenerator(int $pid = 0): array
    {
        $fieldTypes = [];
        $fieldConfiguration = $this->getFieldConfiguration($pid);

        foreach($fieldConfiguration as $fT=>$_fc) {
            if(isset($_fc['value'])) {
                if(class_exists($_fc['value'])) {
                    $fieldTypes[] = $fT;
                }
            }
        }

        return $fieldTypes;
    }
}
