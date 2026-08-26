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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * URL slug field generated from one or more source fields.
 */
class Slug extends Input
{
    public function getSqlCreateStatement(): string
    {
        if ($this->getField()->getDatabaseType() != '') {
            return $this->getField()->getDatabaseType();
        }

        return 'varchar(2048) DEFAULT \'\' NOT NULL';
    }

    public function getVariableType(): string
    {
        return 'string';
    }

    public function getTca(): array
    {
        $sourceFields = $this->resolveSourceFields();
        $separator = trim((string)$this->getField()->getConfig('fieldSeparator'));
        if ($separator === '') {
            $separator = '-';
        }

        $fallbackCharacter = trim((string)$this->getField()->getConfig('fallbackCharacter'));
        if ($fallbackCharacter === '') {
            $fallbackCharacter = '-';
        }

        $eval = trim((string)$this->getField()->getConfig('slugEval'));
        if ($eval === '') {
            $eval = 'uniqueInPid';
        }

        $tca = [
            'exclude' => (int)$this->getField()->isExclude(),
            'label' => $this->getField()->getFrontendLabel(),
            'config' => [
                'type' => 'slug',
                'size' => (int)($this->getField()->getConfig('size') ?: 50),
                'generatorOptions' => [
                    'fields' => $sourceFields,
                    'fieldSeparator' => $separator,
                    'prefixParentPageSlug' => false,
                    'replacements' => [
                        '/' => '',
                    ],
                ],
                'fallbackCharacter' => $fallbackCharacter,
                'eval' => $eval,
                'default' => '',
            ],
        ];

        return $this->mergeConfigurationToTca($tca);
    }

    /**
     * @return list<string>
     */
    protected function resolveSourceFields(): array
    {
        $raw = trim((string)$this->getField()->getConfig('sourceFields'));
        if ($raw === '') {
            return ['title'];
        }

        $fields = GeneralUtility::trimExplode(',', $raw, true);

        return $fields !== [] ? $fields : ['title'];
    }

    /**
     * Skip Textarea/Input merge so slug generator options / eval stay intact.
     */
    public function mergeConfigurationToTca(array $tca): array
    {
        return AbstractField::mergeConfigurationToTca($tca);
    }
}
