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

class Editor extends Textarea implements Tca\FieldInterface
{
    /**
     * Gets built tca array
     *
     * @return array
     */
    public function getTca(): array
    {
        // t3editor was renamed to codeEditor in TYPO3 v13+.
        $renderType = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() >= 13
            ? 'codeEditor'
            : 't3editor';

        $format = $this->getField()->getConfig('format');
        if ($format === null || $format === '') {
            $format = $renderType === 'codeEditor' ? 'html' : 'mixed';
        }
        // TYPO3 v13/v14 codeEditor does not support "mixed" format mode.
        if ($renderType === 'codeEditor' && $format === 'mixed') {
            $format = 'html';
        }

        $tca = [
            'exclude' => (int)$this->getField()->isExclude(),
            'label' => $this->getField()->getFrontendLabel(),
            'config' => [
                'type' => 'text',
                'renderType' => $renderType,
                'format' => (string)$format,
                'default' => '',
                'rows' => (int)($this->getField()->getConfig('rows') ?: 10),
            ],
        ];

        $tca = $this->mergeConfigurationToTca($tca);

        // TYPO3 v14: codeEditor/t3editor expect a string default, not int/array.
        if (isset($tca['config']['default']) && !is_string($tca['config']['default'])) {
            $default = $tca['config']['default'];
            $tca['config']['default'] = is_array($default) ? (string)(reset($default) ?? '') : (string)$default;
        }

        return $tca;
    }

    public function getDefaultValue(): string
    {
        $value = parent::getDefaultValue();

        if (is_array($value)) {
            $value = reset($value);
        }

        return is_scalar($value) ? (string)$value : '';
    }
}
