<?php

declare(strict_types=1);
/*
 * This file is part of the package k3n/tonictypes.
 */

namespace K3n\Tonictypes\Controller;

use K3n\Tonictypes\Service\Transfer\DatatypeTransferExportService;
use K3n\Tonictypes\Service\Transfer\DatatypeTransferImportService;
use K3n\Tonictypes\Service\Transfer\DatatypeTransferStatusService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class DatatypeTransferController extends ActionController
{
    private const MODULE_IDENTIFIER = 'k3n_tonictypes_transfer';
    private const MODULE_ICON_IDENTIFIER = 'extensions-tonictypes-transfer';

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly PageRenderer $pageRenderer,
        private readonly BackendUriBuilder $backendUriBuilder,
        private readonly DatatypeTransferStatusService $statusService,
        private readonly DatatypeTransferExportService $exportService,
        private readonly DatatypeTransferImportService $importService,
    ) {
    }

    public function indexAction(): ResponseInterface
    {
        $this->pageRenderer->addCssFile('EXT:tonictypes/Resources/Public/Css/transfer-dashboard.css');
        $this->pageRenderer->loadJavaScriptModule('@k3n/tonictypes/transfer-dashboard.js');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:tonictypes/Resources/Private/Language/locallang_mod_transfer.xlf');

        $moduleTitle = $this->getLanguageService()->sL(
            'LLL:EXT:tonictypes/Resources/Private/Language/locallang_mod_transfer.xlf:module.title'
        );

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate
            ->setModuleClass('tonictypes-transfer-module')
            ->setTitle($moduleTitle)
            ->assignMultiple([
                'docHeaderTitle' => $moduleTitle,
                'docHeaderIconIdentifier' => self::MODULE_ICON_IDENTIFIER,
                'datatypes' => $this->statusService->getDatatypeOverview(),
                'storagePages' => $this->statusService->getStoragePageOptions(),
                'exportUri' => $this->buildActionUri('export'),
                'importPreviewUri' => $this->buildActionUri('importPreview'),
                'importExecuteUri' => $this->buildActionUri('importExecute'),
                'loaderIconUri' => $this->resolvePublicResourceUri('EXT:tonictypes/Resources/Public/Icons/Extension.svg'),
            ]);
        if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() >= 14
            && method_exists($moduleTemplate->getDocHeaderComponent(), 'disableAutomaticReloadButton')
        ) {
            $moduleTemplate->getDocHeaderComponent()->disableAutomaticReloadButton();
        }

        return $moduleTemplate->renderResponse('Backend/Transfer/Index');
    }

    public function exportAction(): ResponseInterface
    {
        $parsedBody = $this->request->getParsedBody();
        $datatypeUids = GeneralUtility::intExplode(',', (string)($parsedBody['datatypeUids'] ?? ''), true);

        try {
            $tempFile = $this->exportService->createArchive($datatypeUids);
            $content = (string)file_get_contents($tempFile);
            @unlink($tempFile);

            $filename = 'tonictypes-export-' . date('Y-m-d-His') . '.t3tt.zip';
            $response = $this->responseFactory->createResponse()
                ->withHeader('Content-Type', 'application/zip')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Content-Length', (string)strlen($content));
            $response->getBody()->write($content);

            return $response;
        } catch (\Throwable $exception) {
            return new JsonResponse(['success' => false, 'message' => $exception->getMessage()], 400);
        }
    }

    public function importPreviewAction(): ResponseInterface
    {
        try {
            $archivePath = $this->resolveUploadedArchive();
            $bundle = $this->importService->parseArchive($archivePath);
            @unlink($archivePath);

            $this->importService->assertNoUnavailablePremiumFields($bundle['datatypes']);

            return new JsonResponse([
                'success' => true,
                'preview' => $this->importService->buildPreview($bundle['datatypes']),
                'storagePages' => $this->statusService->getStoragePageOptions(),
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse(['success' => false, 'message' => $exception->getMessage()], 400);
        }
    }

    public function importExecuteAction(): ResponseInterface
    {
        try {
            $parsedBody = $this->request->getParsedBody();
            $pidMapping = json_decode((string)($parsedBody['pidMapping'] ?? ''), true);
            if (!is_array($pidMapping)) {
                $pidMapping = [];
            }

            $archivePath = $this->resolveUploadedArchive();
            $bundle = $this->importService->parseArchive($archivePath);
            @unlink($archivePath);

            $this->importService->assertNoUnavailablePremiumFields($bundle['datatypes']);

            $normalizedMapping = [];
            foreach ($pidMapping as $exportKey => $pid) {
                $normalizedMapping[(string)$exportKey] = (int)$pid;
            }

            return new JsonResponse($this->importService->importBundle($bundle['datatypes'], $normalizedMapping));
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'log' => [['status' => 'error', 'message' => $exception->getMessage()]],
            ], 400);
        }
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function buildActionUri(string $action): string
    {
        $routeName = self::MODULE_IDENTIFIER . '.DatatypeTransfer_' . $action;

        return (string)$this->backendUriBuilder->buildUriFromRoute($routeName);
    }

    private function resolvePublicResourceUri(string $extPath): string
    {
        if (method_exists(PathUtility::class, 'getPublicResourceWebPath')) {
            return PathUtility::getPublicResourceWebPath($extPath);
        }

        $absolutePath = GeneralUtility::getFileAbsFileName($extPath);
        if ($absolutePath === '') {
            return '';
        }

        return PathUtility::getAbsoluteWebPath($absolutePath);
    }

    private function resolveUploadedArchive(): string
    {
        $file = $this->request->getUploadedFiles()['importFile'] ?? null;
        if (!$file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Please upload a valid .t3tt.zip archive.');
        }

        $tempFile = GeneralUtility::tempnam('tonictypes_import_', '.zip');
        $file->moveTo($tempFile);

        return $tempFile;
    }
}
