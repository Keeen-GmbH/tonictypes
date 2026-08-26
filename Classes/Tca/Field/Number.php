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

use K3n\Tonictypes\Tca\AbstractField;

/**
 * Number field (integer or decimal) with optional min/max range.
 */
class Number extends Input
{
    public function getSqlCreateStatement(): string
    {
        if ($this->getField()->getDatabaseType() != '') {
            return $this->getField()->getDatabaseType();
        }

        if ($this->getFormat() === 'decimal') {
            return 'double(11,2) DEFAULT \'0.00\' NOT NULL';
        }

        return 'int(11) DEFAULT \'0\' NOT NULL';
    }

    public function getVariableType(): string
    {
        return $this->getFormat() === 'decimal' ? 'float' : 'int';
    }

    public function getTca(): array
    {
        $format = $this->getFormat();
        $tca = [
            'exclude' => (int)$this->getField()->isExclude(),
            'label' => $this->getField()->getFrontendLabel(),
            'config' => [
                'type' => 'number',
                'format' => $format,
                'size' => (int)($this->getField()->getConfig('size') ?: 20),
            ],
        ];

        $range = [];
        $lower = $this->getField()->getConfig('range_lower');
        $upper = $this->getField()->getConfig('range_upper');
        if ($lower !== null && $lower !== '') {
            $range['lower'] = $format === 'decimal' ? (float)$lower : (int)$lower;
        }
        if ($upper !== null && $upper !== '') {
            $range['upper'] = $format === 'decimal' ? (float)$upper : (int)$upper;
        }
        if ($range !== []) {
            $tca['config']['range'] = $range;
        }

        if ($this->getField()->getConfig('slider')) {
            $step = $this->getField()->getConfig('slider_step');
            $tca['config']['slider'] = [
                'step' => $step !== null && $step !== ''
                    ? ($format === 'decimal' ? (float)$step : (int)$step)
                    : ($format === 'decimal' ? 0.1 : 1),
            ];
        }

        $default = $this->getField()->getConfig('default');
        if ($default !== null && $default !== '') {
            $tca['config']['default'] = $format === 'decimal' ? (float)$default : (int)$default;
        }

        return $this->mergeConfigurationToTca($tca);
    }

    protected function getFormat(): string
    {
        $format = (string)$this->getField()->getConfig('format');

        return $format === 'decimal' ? 'decimal' : 'integer';
    }

    /**
     * Skip Textarea/Input merge (format/range/eval) so number TCA stays intact.
     */
    public function mergeConfigurationToTca(array $tca): array
    {
        return AbstractField::mergeConfigurationToTca($tca);
    }
}
