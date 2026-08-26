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
 * Yes/No toggle switch (TCA check + checkboxToggle / checkboxLabeledToggle).
 */
class Toggle extends Checkbox
{
    public function getSqlCreateStatement(): string
    {
        if ($this->getField()->getDatabaseType() != '') {
            return $this->getField()->getDatabaseType();
        }

        return 'tinyint(1) unsigned DEFAULT \'0\' NOT NULL';
    }

    public function getVariableType(): string
    {
        return 'bool';
    }

    public function getTca(): array
    {
        $useLabeled = (bool)$this->getField()->getConfig('labeled');
        $item = [
            'label' => '',
        ];

        if ($useLabeled) {
            $labelChecked = trim((string)$this->getField()->getConfig('labelChecked'));
            $labelUnchecked = trim((string)$this->getField()->getConfig('labelUnchecked'));
            $item['labelChecked'] = $labelChecked !== '' ? $labelChecked : 'Yes';
            $item['labelUnchecked'] = $labelUnchecked !== '' ? $labelUnchecked : 'No';
        }

        if ($this->getField()->getConfig('invertStateDisplay')) {
            $item['invertStateDisplay'] = true;
        }

        $tca = [
            'exclude' => (int)$this->getField()->isExclude(),
            'label' => $this->getField()->getFrontendLabel(),
            'config' => [
                'type' => 'check',
                'renderType' => $useLabeled ? 'checkboxLabeledToggle' : 'checkboxToggle',
                'items' => [$item],
                'default' => $this->getDefaultValue(),
            ],
        ];

        return $this->mergeConfigurationToTca($tca);
    }

    public function mergeConfigurationToTca(array $tca): array
    {
        $tca = AbstractField::mergeConfigurationToTca($tca);
        $tca['config']['default'] = $this->getDefaultValue();

        return $tca;
    }

    public function getDefaultValue(): int
    {
        return $this->getField()->getConfig('defaultOn') ? 1 : 0;
    }
}
