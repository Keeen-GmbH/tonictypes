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

namespace K3n\Tonictypes\Tca\Field;

use K3n\Tonictypes\Domain\Model\FieldValue as FieldValue;
use K3n\Tonictypes\Tca;
use K3n\Tonictypes\Utility\CheckboxUtility;

class Checkbox extends Radio implements Tca\FieldInterface
{
    /**
     * Gets the sql create statement
     *
     * @return string
     */
    public function getSqlCreateStatement(): string
    {
        if ($this->getField()->getDatabaseType() != '') {
            return $this->getField()->getDatabaseType();
        }
        return 'int(11) unsigned DEFAULT \'0\' NOT NULL';
    }

    /**
     * @return string
     */
    public function getVariableType(): string
    {
        return 'string';
    }

    /**
     * Gets built tca array
     *
     * @return array
     */
    public function getTca(): array
    {
        $tca = [
            'exclude' => (int)$this->getField()->isExclude(),
            'label' => $this->getField()->getFrontendLabel(),
            'config' => [
                'type' => 'check',
                'items' => $this->getItems(),
            ],
        ];

        return $this->mergeConfigurationToTca($tca);
    }

    /**
     * Merges the field configuration to the tca
     *
     * @param array $tca
     * @return array
     */
    public function mergeConfigurationToTca(array $tca): array
    {
        // Is maybe too much here, but we let this do the parent routine
        $tca = parent::mergeConfigurationToTca($tca);

        // cols
        if ($cols = $this->getField()->getConfig('cols')) {
            $tca['config']['cols'] = $cols;
        }

        // readOnly
        if ($readOnly = $this->getField()->getConfig('readOnly')) {
            $tca['config']['readOnly'] = (int)$readOnly;
        }

        if ($maximumRecordsChecked = $this->getField()->getConfig('maximumRecordsChecked')) {
            $tca['config']['eval'] = 'maximumRecordsChecked';
            $tca['config']['validation'] = [
                'maximumRecordsChecked' => $maximumRecordsChecked,
            ];
        }

        $tca['config']['default'] = $this->getDefaultValue();

        return $tca;
    }

    /**
     * Gets the default value of the field
     *
     * @return int
     */
    public function getDefaultValue()
    {
        // We need to retrieve the default value content for the field
        $fieldValues = $this->getField()->getFieldValues();
        $arrayToInt = [];
        foreach ($fieldValues as $_fieldValue) {
            /* @var FieldValue $_fieldValue */
            $fieldValueItems = $this->getFieldValueItems($_fieldValue);

            foreach ($fieldValueItems as $_fVi) {
                if ($_fieldValue->isDefault()) {
                    $arrayToInt[] = 1;
                } else {
                    $arrayToInt[] = 0;
                }
            }
        }

        return (int)CheckboxUtility::getIntForSelectionArray($arrayToInt);
    }
}
