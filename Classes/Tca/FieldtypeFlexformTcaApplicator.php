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

namespace K3n\Tonictypes\Tca;

use K3n\Tonictypes\Configuration\ExtensionConfiguration;

/**
 * Merges dynamic field-type flexform DS into {@see $GLOBALS['TCA']} for the field table.
 *
 * Must run when TypoScript is resolvable (request middleware), not at BootCompletedEvent.
 */
final class FieldtypeFlexformTcaApplicator
{
    private const DEFAULT_SHOWITEM = '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    logo, type, field_conf,
                --div--;LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:frontend_settings,
                    --palette--;;label, frontend_type, is_object_storage,
                --div--;LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:backend_settings,
                  --palette--;;titles, backend_searchable, --palette--;;excludings, palette, description,
                --div--;LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:database_settings,
                  database_type, is_index,
                --div--;LLL:EXT:tonictypes/Resources/Private/Language/locallang_db.xlf:tx_tonictypes_domain_model_field.field_values,
                    field_values,
                --div--;LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:flexform.validation,
                    validation,
                --div--;LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:flexform.display_cond,
                     request_update, --palette--;;displaycond,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,--palette--;;timeRestriction,
                --div--;LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:advanced_settings,
                    cache_tca
            ';

    /**
     * @param array<string, string> $fieldFlexformConfig field type => FILE:... flex DS path
     */
    public function apply(array $fieldFlexformConfig): void
    {
        $fieldTable = ExtensionConfiguration::EXTENSION_FIELD_TABLE;
        $existingDs = $GLOBALS['TCA'][$fieldTable]['columns']['field_conf']['config']['ds'] ?? [];
        if (is_string($existingDs)) {
            $existingDs = ['default' => $existingDs];
        } elseif (!is_array($existingDs)) {
            $existingDs = [];
        }

        $resolvedDs = $fieldFlexformConfig + $existingDs;
        if (!isset($resolvedDs['default']) || $resolvedDs['default'] === '') {
            $resolvedDs['default'] = 'FILE:EXT:tonictypes/Configuration/FlexForms/Field/Empty.xml';
        }
        $defaultDs = (string)$resolvedDs['default'];
        $GLOBALS['TCA'][$fieldTable]['columns']['field_conf']['config']['ds'] = $defaultDs;

        $types = &$GLOBALS['TCA'][$fieldTable]['types'];
        if (!is_array($types)) {
            $types = [];
        }
        $fallbackTypeConfiguration = is_array($types['1'] ?? null) ? $types['1'] : [];

        // Always keep the static fallback type with a valid string DS (v14 requirement).
        foreach (['0', '1'] as $staticType) {
            if (!isset($types[$staticType]) || !is_array($types[$staticType])) {
                continue;
            }
            $types[$staticType] = array_replace_recursive(
                $types[$staticType],
                [
                    'columnsOverrides' => [
                        'field_conf' => [
                            'config' => [
                                'ds' => $defaultDs,
                            ],
                        ],
                    ],
                ]
            );
        }

        foreach ($resolvedDs as $fieldType => $ds) {
            if ($fieldType === 'default' || !is_string($fieldType) || $fieldType === '' || !is_string($ds) || $ds === '') {
                continue;
            }
            $types[$fieldType] = array_replace_recursive(
                $fallbackTypeConfiguration,
                $types[$fieldType] ?? [],
                [
                    'showitem' => self::DEFAULT_SHOWITEM,
                    'columnsOverrides' => [
                        'field_conf' => [
                            'config' => [
                                'ds' => $ds,
                            ],
                        ],
                    ],
                ]
            );
        }
    }
}
