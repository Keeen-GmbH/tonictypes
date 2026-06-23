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

namespace K3n\Tonictypes\Controller\Backend;

use K3n\Tonictypes\Domain\Model\Datatype;
use K3n\Tonictypes\Domain\Repository\DatatypeRepository;
use K3n\Tonictypes\Factory\TableFactory;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use K3n\Tonictypes\Service\Backend\BackendAccessService;
use K3n\Tonictypes\Service\Settings\Plugin\PluginSettingsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

class EditDocumentController extends \TYPO3\CMS\Backend\Controller\EditDocumentController
{
    /**
     * @var StandaloneView
     */
    protected $standaloneView;

    /**
     * @var DatatypeRepository
     */
    protected $datatypeRepository;

    /**
     * @var PluginSettingsService
     */
    protected $pluginSettingsService;

    /**
     * @var BackendAccessService
     */
    protected $backendAccessService;

    /**
     * @var TableFactory
     */
    protected $tableFactory;

    /**
     * @param StandaloneView $standaloneView
     */
    public function injectStandaloneView(StandaloneView $standaloneView)
    {
        $this->standaloneView = $standaloneView;
    }

    /**
     * @param DatatypeRepository $datatypeRepository
     */
    public function injectDatatypeRepository(DatatypeRepository $datatypeRepository)
    {
        $this->datatypeRepository = $datatypeRepository;
    }

    /**
     * @param PluginSettingsService $pluginSettingsService
     */
    public function injectPluginSettingsService(PluginSettingsService $pluginSettingsService)
    {
        $this->pluginSettingsService = $pluginSettingsService;
    }

    /**
     * @param BackendAccessService $backendAccessService
     */
    public function injectBackendAccessService(BackendAccessService $backendAccessService)
    {
        $this->backendAccessService = $backendAccessService;
    }

    /**
     * @param TableFactory $tableFactory
     */
    public function injectTableFactory(TableFactory $tableFactory)
    {
        $this->tableFactory = $tableFactory;
    }

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        // TYPO3 v14+ uses the refactored controller flow from core.
        if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() > 12) {
            if (method_exists($this, 'resolveDefaultReturnUrl')) {
                return parent::mainAction($request);
            }
        }

        // TYPO3 v12 compatibility flow.
        $view = $this->moduleTemplateFactory->create($request);
        $view->setUiBlock(true);
        if (method_exists($this, 'getShortcutTitle')) {
            $view->setTitle($this->{'getShortcutTitle'}($request));
        }

        BackendUtility::lockRecords();
        if (method_exists($this, 'preInit')) {
            $preInitResponse = $this->{'preInit'}($request);
            if ($preInitResponse instanceof ResponseInterface) {
                return $preInitResponse;
            }
        }

        $parsedBody = $request->getParsedBody();
        $doSave = property_exists($this, 'doSave') ? (bool)$this->{'doSave'} : false;
        if ((
                $doSave
                || isset($parsedBody['_savedok'])
                || isset($parsedBody['_saveandclosedok'])
                || isset($parsedBody['_savedokview'])
                || isset($parsedBody['_savedoknew'])
                || isset($parsedBody['_duplicatedoc'])
            )
            && $request->getMethod() === 'POST'
        ) {
            $processDataResponse = (new \ReflectionMethod($this, 'processData'))->invoke($this, $view, $request);
            if ($processDataResponse instanceof ResponseInterface) {
                return $processDataResponse;
            }
        }

        if (method_exists($this, 'init')) {
            $this->{'init'}($request);
        }

        $queryParams = $request->getQueryParams();
        $tableName = (string)key($queryParams['edit'] ?? []);
        if ($tableName !== '') {
            $recordId = key($queryParams['edit'][$tableName] ?? []);
            $recordId = (($queryParams['edit'][$tableName][$recordId] ?? '') !== 'new') ? (int)$recordId : null;
            $this->applyTonictypesContext($view, $tableName, $recordId);
        } else {
            $view->assign('tonictypesContext', false);
        }

        if ($request->getMethod() === 'POST') {
            if (isset($parsedBody['_savedokview'])) {
                $legacyRedirectUri = property_exists($this, 'R_URI') ? (string)$this->{'R_URI'} : '';
                $legacyRedirectUri = rtrim($legacyRedirectUri, '&') .
                    HttpUtility::buildQueryString([
                        'showPreview' => true,
                        'popViewId' => $parsedBody['popViewId']
                            ?? (method_exists($this, 'getPreviewPageId') ? $this->{'getPreviewPageId'}() : 0),
                    ], (empty($this->R_URL_getvars) ? '?' : '&'));
                if (property_exists($this, 'R_URI')) {
                    $this->{'R_URI'} = $legacyRedirectUri;
                }
            } else {
                $legacyRedirectUri = property_exists($this, 'R_URI') ? (string)$this->{'R_URI'} : '';
            }
            return new RedirectResponse($legacyRedirectUri, 302);
        }

        if (!method_exists($this, 'main')) {
            return parent::mainAction($request);
        }
        $view->assign('bodyHtml', $this->{'main'}($view, $request));
        return $view->renderResponse('Form/EditDocument');
    }

    protected function setModuleContext(ModuleTemplate $view): void
    {
        parent::setModuleContext($view);

        $tableName = (string)key($this->editconf);
        if ($tableName === '') {
            $view->assign('tonictypesContext', false);
            return;
        }

        $tableEditConfiguration = $this->editconf[$tableName] ?? [];
        $recordId = key($tableEditConfiguration);
        $isNewRecord = $recordId !== null && ($tableEditConfiguration[$recordId] ?? '') === 'new';
        $recordId = $isNewRecord ? null : (int)$recordId;

        $this->applyTonictypesContext($view, $tableName, $recordId);
    }

    protected function applyTonictypesContext(ModuleTemplate $view, string $tableName, ?int $recordId): void
    {
        /************************************************************************************************************
         * TONICTYPES CUSTOM BACKEND LAYOUT
         ***********************************************************************************************************/
        $view->assign('tonictypesContext', false);
        $datatypeUid = 0;
        $datatypeByTable = $this->datatypeRepository->findOneBy(['tablename' => $tableName]);
        if ($datatypeByTable instanceof Datatype) {
            $datatypeUid = (int)$datatypeByTable->getUid();
        }

        $datatype = $datatypeUid > 0
            ? $this->datatypeRepository->findOneBy(['uid' => (int)$datatypeUid])
            : null;
        if (!$datatype instanceof Datatype) {
            return;
        }

        // Obtain general view variables for custom backend template rendering.
        $record = $recordId !== null ? BackendUtility::getRecord($tableName, (int)$recordId) : null;
        $sysLanguage = null;
        if ($record !== null && isset($record['sys_language_uid'])) {
            $sysLanguage = BackendUtility::getRecord('sys_language', (int)$record['sys_language_uid']);
        }

        $view->setModuleId('tonictypes-edit');
        $view->setModuleName('tonictypes-edit-document');
        $view->assignMultiple([
            'datatype' => $datatype,
            'record' => $record,
            'logoUrl' => $this->backendAccessService->getLogoUrl(),
            'logoBrightUrl' => $this->backendAccessService->getBrightLogoUrl(),
            'supportEmail' => $this->backendAccessService->getSupportEmail(),
            'supportMessage' => $this->backendAccessService->disableSupportMessage(),
            'version' => ExtensionManagementUtility::getExtensionVersion('tonictypes'),
            'language' => $sysLanguage,
            'typo3IsV12' => GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() === 12,
            'tonictypesContext' => true,
        ]);
        /************************************************************************************************************/
    }
}