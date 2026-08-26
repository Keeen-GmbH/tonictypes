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

namespace K3n\Tonictypes\DataProcessing;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Converts FlexForm XML into a simple array.
 * Usable from Fluid ViewHelpers, PHP, and TypoScript dataProcessing.
 */
final class FlexFormProcessor implements DataProcessorInterface
{
    /**
     * Convert FlexForm XML to a simple array.
     *
     * @return array<string, mixed>
     */
    public function convert(string $flexFormXml): array
    {
        $flexFormXml = trim($flexFormXml);
        if ($flexFormXml === '') {
            return [];
        }

        // @extensionScannerIgnoreLine
        $flexFormAsArray = GeneralUtility::xml2array($flexFormXml);
        if (!is_array($flexFormAsArray) || empty($flexFormAsArray['data']) || !is_array($flexFormAsArray['data'])) {
            return [];
        }

        $options = [];
        foreach ($flexFormAsArray['data'] as $base) {
            if (empty($base['lDEF']) || !is_array($base['lDEF'])) {
                continue;
            }

            foreach ($base['lDEF'] as $optionKey => $optionValue) {
                $parts = explode('.', (string)$optionKey);
                $optionKey = (string)array_pop($parts);

                if (isset($optionValue['el']) && is_array($optionValue['el'])) {
                    foreach ($optionValue['el'] as $itemId => $subArrayItem) {
                        if (!is_array($subArrayItem)) {
                            continue;
                        }
                        foreach ($subArrayItem as $subsubArrayItem) {
                            if (!isset($subsubArrayItem['el']) || !is_array($subsubArrayItem['el'])) {
                                continue;
                            }
                            foreach ($subsubArrayItem['el'] as $subKey => $value) {
                                $options[$optionKey][$itemId][$subKey] = $value['vDEF'] ?? null;
                            }
                        }
                    }
                    // Normalize section items to a numeric list
                    if (isset($options[$optionKey]) && is_array($options[$optionKey])) {
                        $options[$optionKey] = array_values($options[$optionKey]);
                    }
                } else {
                    $vDef = $optionValue['vDEF'] ?? null;
                    $options[$optionKey] = $vDef === '1' ? true : $vDef;
                }
            }
        }

        return $options;
    }

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        if (!empty($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        $fieldName = (string)($cObj->stdWrapValue('field', $processorConfiguration, 'pi_flexform'));
        $as = (string)($cObj->stdWrapValue('as', $processorConfiguration, $fieldName === 'pi_flexform' ? 'flexform' : $fieldName));

        $xml = '';
        if (isset($processedData['data'][$fieldName]) && is_scalar($processedData['data'][$fieldName])) {
            $xml = (string)$processedData['data'][$fieldName];
        }

        $processedData[$as] = $this->convert($xml);

        return $processedData;
    }
}
