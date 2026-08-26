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

use K3n\Tonictypes\Evaluation\PhoneNumberEvaluation;

/**
 * Phone number input with basic formatting and validation.
 */
class Phone extends Input
{
    public function getTca(): array
    {
        $tca = [
            'exclude' => (int)$this->getField()->isExclude(),
            'label' => $this->getField()->getFrontendLabel(),
            'config' => [
                'type' => 'input',
                'size' => $this->getField()->getConfig('size') ?: 30,
                'max' => 30,
                'eval' => 'trim,' . PhoneNumberEvaluation::class,
                'autocomplete' => true,
            ],
        ];

        $placeholder = trim((string)$this->getField()->getConfig('placeholder'));
        $tca['config']['placeholder'] = $placeholder !== '' ? $placeholder : '+49 123 456789';

        return $this->mergeConfigurationToTca($tca);
    }
}
