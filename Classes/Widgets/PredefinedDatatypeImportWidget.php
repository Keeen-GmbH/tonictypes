<?php
declare(strict_types=1);
/*
 * This file is part of the package k3n/tonictypes.
 */

namespace K3n\Tonictypes\Widgets;

use K3n\Tonictypes\Service\Import\PredefinedDatatypeImportService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class PredefinedDatatypeImportWidget implements
    WidgetInterface,
    RequestAwareWidgetInterface,
    JavaScriptInterface,
    AdditionalCssInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly PredefinedDatatypeImportService $importService,
    ) {
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-dashboard', 'k3n/tonictypes']);
        $view->assignMultiple([
            'configuration' => $this->configuration,
            'storagePages' => $this->importService->getStoragePageOptions(),
            'importAvailable' => $this->importService->isImportAvailable(),
            'alreadyImported' => $this->importService->isPredefinedArchiveAlreadyImported(),
        ]);

        return $view->render('Dashboard/PredefinedDatatypeImportWidget');
    }

    public function getOptions(): array
    {
        return [];
    }

    /**
     * @return list<JavaScriptModuleInstruction>
     */
    public function getJavaScriptModuleInstructions(): array
    {
        return [
            JavaScriptModuleInstruction::create('@k3n/tonictypes/predefined-datatype-import-widget.js'),
        ];
    }

    /**
     * @return list<string>
     */
    public function getCssFiles(): array
    {
        return [
            'EXT:tonictypes/Resources/Public/Css/predefined-datatype-import-widget.css',
        ];
    }
}
