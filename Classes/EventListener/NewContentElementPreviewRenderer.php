<?php
declare(strict_types=1);

namespace K3n\Tonictypes\EventListener;

use Doctrine\DBAL\ParameterType;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class NewContentElementPreviewRenderer
{
    public function __invoke(PageContentPreviewRenderingEvent $event): void
    {
        $record = $event->getRecord();
        $row = $this->resolveRecordData($record);
        $cType = (string)($row['CType'] ?? '');

        $templatePathAndFilename = $this->resolveTemplatePathFromCType($cType);
        if ($templatePathAndFilename === null) {
            return;
        }
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename($templatePathAndFilename);
        $view->assignMultiple([
            'record' => $row,
            'flexform' => $this->resolveFlexFormArray($this->fetchRawFlexFormByUid((int)($row['uid'] ?? 0))),
        ]);
        $event->setPreviewContent((string)$view->render());
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveRecordData(mixed $record): array
    {
        if (is_array($record)) {
            return $record;
        }
        if ($record instanceof RecordInterface) {
            return $record->toArray();
        }
        if (is_object($record) && method_exists($record, 'toArray')) {
            return (array)$record->toArray();
        }

        return [];
    }

    private function resolveTemplatePathFromCType(string $cType): ?string
    {
        $allowedCTypes = [
            'tonictypes_list',
            'tonictypes_detail',
            'tonictypes_dynamic',
            'tonictypes_plain',
        ];
        if (!in_array($cType, $allowedCTypes, true)) {
            return null;
        }

        return GeneralUtility::getFileAbsFileName(
            'EXT:tonictypes/Resources/Private/Templates/Backend/Preview/' . $cType . '.html'
        );
    }

    private function fetchRawFlexFormByUid(int $uid): string
    {
        if ($uid <= 0) {
            return '';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $rawFlexForm = $queryBuilder
            ->select('pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)),
            )
            ->executeQuery()
            ->fetchOne();

        return is_string($rawFlexForm) ? $rawFlexForm : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFlexFormArray(mixed $flexFormValue): array
    {
        if ($flexFormValue instanceof FlexFormFieldValues) {
            return $flexFormValue->toArray();
        }
        if (is_array($flexFormValue)) {
            return $flexFormValue;
        }
        if (!is_string($flexFormValue) || trim($flexFormValue) === '') {
            return [];
        }

        // @extensionScannerIgnoreLine
        $flexFormAsArray = GeneralUtility::xml2array($flexFormValue);
        if (!is_array($flexFormAsArray['data'] ?? null)) {
            return [];
        }

        $options = [];
        foreach ($flexFormAsArray['data'] as $sheetKey => $sheetData) {
            if (!is_array($sheetData['lDEF'] ?? null)) {
                continue;
            }
            $sheetOptions = [];
            foreach ($sheetData['lDEF'] as $optionKey => $optionValue) {
                $optionParts = explode('.', (string)$optionKey);
                $normalizedOptionKey = (string)array_pop($optionParts);
                if ($normalizedOptionKey === '') {
                    continue;
                }

                if (is_array($optionValue['el'] ?? null)) {
                    foreach ($optionValue['el'] as $subPreKey => $subArrayItem) {
                        if (!is_array($subArrayItem)) {
                            continue;
                        }
                        foreach ($subArrayItem as $subSubArrayItem) {
                            if (!is_array($subSubArrayItem['el'] ?? null)) {
                                continue;
                            }
                            foreach ($subSubArrayItem['el'] as $subKey => $value) {
                                $sheetOptions[$normalizedOptionKey][$subPreKey][$subKey] = $value['vDEF'] ?? null;
                            }
                        }
                    }
                } else {
                    $rawValue = $optionValue['vDEF'] ?? null;
                    $sheetOptions[$normalizedOptionKey] = $rawValue === '1' ? true : $rawValue;
                }
            }
            $options[(string)$sheetKey] = $sheetOptions;
        }

        return $options;
    }
}
