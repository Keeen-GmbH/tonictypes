<?php

declare(strict_types=1);

namespace K3n\Tonictypes\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

/**
 * TYPO3 v14 CodeEditorElement / RteHtmlParser require string content.
 * Dynamic columns that were previously int/passthrough can still return int from Doctrine.
 */
final class EnsureTextFieldStringValues implements FormDataProviderInterface
{
    public function addData(array $result): array
    {
        $columns = $result['processedTca']['columns'] ?? [];
        if ($columns === [] || !is_array($result['databaseRow'] ?? null)) {
            return $result;
        }

        foreach ($columns as $fieldName => $fieldConfig) {
            if (!$this->needsStringCast($fieldConfig['config'] ?? [])) {
                continue;
            }

            if (!array_key_exists($fieldName, $result['databaseRow'])) {
                continue;
            }

            $value = $result['databaseRow'][$fieldName];
            if (is_string($value) || $value === null) {
                continue;
            }

            if (is_array($value)) {
                // Language overlay arrays: cast each language value when scalar.
                foreach ($value as $langKey => $langValue) {
                    if (is_scalar($langValue) || $langValue === null) {
                        $value[$langKey] = (string)($langValue ?? '');
                    }
                }
                $result['databaseRow'][$fieldName] = $value;
                continue;
            }

            if (is_scalar($value)) {
                $result['databaseRow'][$fieldName] = (string)$value;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function needsStringCast(array $config): bool
    {
        $type = (string)($config['type'] ?? '');
        if ($type !== 'text') {
            return false;
        }

        $renderType = (string)($config['renderType'] ?? '');
        return $renderType === 'codeEditor'
            || $renderType === 't3editor'
            || !empty($config['enableRichtext']);
    }
}
