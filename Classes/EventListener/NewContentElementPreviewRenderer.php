<?php
declare(strict_types=1);

namespace K3n\Tonictypes\EventListener;

use Doctrine\DBAL\ParameterType;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class NewContentElementPreviewRenderer
{
    public function __invoke(PageContentPreviewRenderingEvent $event): void
    {
        if ((new Typo3Version())->getMajorVersion() < 13) {
            return;
        }

        $cType = $event->getRecordType();
        if ($cType === '') {
            return;
        }

        $templatePathAndFilename = $this->resolveTemplatePathFromCType($cType);
        if ($templatePathAndFilename === null) {
            return;
        }

        $record = $event->getRecord();
        $uid = $this->resolveRecordUid($record);
        $rawFlexForm = $this->fetchRawFlexFormByUid($uid);
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename($templatePathAndFilename);
        $view->assignMultiple([
            'record' => $record,
            'flexform' => $flexFormTools->convertFlexFormContentToSheetsArray($rawFlexForm),
            'pi_flexform_transformed' => $flexFormTools->convertFlexFormContentToArray($rawFlexForm),
        ]);
        $event->setPreviewContent((string)$view->render());
    }

    private function resolveRecordUid(mixed $record): int
    {
        if ($record instanceof RecordInterface) {
            return $record->getUid();
        }
        if (is_array($record)) {
            return (int)($record['uid'] ?? 0);
        }
        if (is_object($record) && method_exists($record, 'getUid')) {
            return (int)$record->getUid();
        }

        return 0;
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

        $templateDirectory = 'EXT:tonictypes/Resources/Private/Templates/Backend/Preview/';
        if ((new Typo3Version())->getMajorVersion() >= 14) {
            $templateDirectory .= 'v14/';
        }

        return GeneralUtility::getFileAbsFileName($templateDirectory . $cType . '.html');
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
}
