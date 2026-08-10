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

use K3n\Tonictypes\Tca;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Textarea extends Tca\AbstractField implements Tca\FieldInterface
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
        return 'mediumtext';
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
                'type' => 'text',
                'size' => 30,
                'wrap' => $this->getField()->getConfig('wrap'),
                'cols' => $this->getField()->getConfig('cols'),
                'rows' => $this->getField()->getConfig('rows'),
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
        $tca = parent::mergeConfigurationToTca($tca);

        // max
        if ($max = $this->getField()->getConfig('max')) {
            $tca['config']['max'] = $max;
        }

        // is_in
        if ($isIn = $this->getField()->hasEval('is_in')) {
            $tca['config']['is_in'] = $isIn;
        }

        // range
        if ($rangeLower = $this->getField()->hasEval('range')) {
            $tca['config']['range'] = ['lower' => $this->getField()->getConfig('range_lower'),
                'upper' => $this->getField()->getConfig('range_upper')];
        }

        // renderType
        if ($renderType = $this->getField()->getConfig('renderType')) {
            // t3editor was renamed to codeEditor in TYPO3 v13+.
            if (
                $renderType === 't3editor'
                && GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() >= 13
            ) {
                $renderType = 'codeEditor';
            }
            $tca['config']['renderType'] = $renderType;

            if ($renderType === 't3editor' || $renderType === 'codeEditor') {
                $format = $this->getField()->getConfig('format');
                $format = ($format === null || $format === '') ? 'html' : (string)$format;

                // TYPO3 v13+ codeEditor does not support "mixed" format.
                if (
                    $renderType === 'codeEditor'
                    && GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() >= 13
                    && $format === 'mixed'
                ) {
                    $format = 'html';
                }

                $tca['config']['format'] = $format;
            }
        }

        // placeholder
        if ($placeholder = $this->getField()->getConfig('placeholder')) {
            $tca['config']['placeholder'] = $placeholder;
        }

        // autocomplete
        if ($autocomplete = $this->getField()->getConfig('autocomplete')) {
            $tca['config']['autocomplete'] = true;
        }

        return $tca;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        $value = parent::getDefaultValue();

        if (is_array($value)) {
            return implode(', ', $value);
        }

        return '';
    }
}
