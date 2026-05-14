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
        $dsConfig = [];
        $typesConfiguration = $this->getFieldConfiguration($pid);
        foreach ($typesConfiguration as $_id=>$_config) {
            if (isset($_config['flexform'])) {
                $dsConfig[$_id] = 'FILE:'.$_config['flexform'];
            }
        }

        return $dsConfig;
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
