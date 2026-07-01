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

namespace K3n\Tonictypes\Controller;

use K3n\Tonictypes\Domain\Model\AbstractRecordModel;
use K3n\Tonictypes\Domain\Model\Datatype;
use K3n\Tonictypes\Domain\Repository\AbstractRepository;
use K3n\Tonictypes\Event\BeforeDynamicDetailViewRenderEvent;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use K3n\Tonictypes\Service\Settings\Plugin\PluginSettingsService;
use K3n\Tonictypes\Utility\LocalizationUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;

class RecordController extends AbstractController
{
    /**
     * @var string
     */
    protected ?string $cacheIdentifier = null;

    /**
     * View Variables
     * @var array
     */
    protected $variables = [];

    /**
     * Headers to deliver
     *
     * @var array
     */
    private $_headers = [];

    /**
     * Default Variable Names
     * @var string
     */
    const DEFAULT_VAR_COBJ = 'cObj';
    const DEFAULT_VAR_SETTINGS = 'settings';
    const DEFAULT_VAR_DATATYPE = 'datatype';
    const DEFAULT_VAR_ERRORS = 'errors';
    const DEFAULT_VAR_DETAILPID = 'detailPid';
    const DEFAULT_VAR_OVERALLCOUNT = 'overallCount';
    const DEFAULT_VAR_RECORD_UID = 'recordUid';

    // Paginator Related
    const DEFAULT_VAR_PAGINATOR = 'p_paginator';
    const DEFAULT_VAR_PAGING = 'p_paging';
    const DEFAULT_VAR_PAGES = 'p_pages';
    const DEFAULT_VAR_ITEMS_PER_PAGE_VARIABLE_NAME = 'p_items_per_page_var_name';
    const DEFAULT_VAR_PAGE_NUMBER_VARIABLE_NAME = 'p_page_number_var_name';

    /**
     * Default Items per Page on Pagination
     */
    const PAGINATION_DEFAULT_ITEMS_PER_PAGE = 10;

    /**
     * Returns a response object with either the given html string or the current rendered view as content.
     *
     * @param string|null $html
     */
    protected function htmlResponse(?string $html = null): ResponseInterface
    {
        $response = parent::htmlResponse($html);
        $this->processPreparedHeaders($response);
        return $response;
    }


    /**
     * Initializes the controller before invoking an action method.
     *
     * Override this method to solve tasks which all actions have in
     * common.
     */
    protected function initializeAction(): void
    {
        // Process custom headers from the configuration
        $this->prepareConfiguredHeaders();
        parent::initializeAction();

        $variables = $this->_getEnvironmentalVariables(true);
        if (!array_key_exists(1, $this->variables)) {
            $this->variables[1] = is_array($variables) ? $variables : [];
        }
        if (!empty($variables)) {
            $this->bindDatatypeToRecordVariables($variables);
        }

        if (isset($this->view)) {
            $this->configureView($this->view, $variables ?? []);
        }
    }

    /**
     * initializeView
     * Kept for backward compatibility with TYPO3 v12/v13.
     * In TYPO3 v14+ this method is no longer invoked by the framework.
     *
     * @param mixed $view
     * @return void
     */
    protected function initializeView($view): void
    {
        $variables = $this->_getEnvironmentalVariables(true);
        $this->configureView($view, $variables);
    }

    /**
     * Configures the view: resolves the correct template path/source and
     * assigns all environment variables. Called from both initializeAction()
     * (TYPO3 v14+) and initializeView() (TYPO3 v12/v13 backward compat).
     *
     * @param mixed $view
     * @param array $variables
     * @return void
     * @throws \Exception
     */
    protected function configureView(mixed $view, array $variables): void
    {
        $templateSwitch = $this->_getTemplateSwitch($variables);

        $source = '';
        $type = '';
        if (is_array($templateSwitch)) {
            // TEMPLATE SWITCH BEHAVIOUR
            $type = key($templateSwitch);
            $source = reset($templateSwitch);
        } else {
            $fluidCode = (string)($this->settings['fluid_code'] ?? '');
            $templateOverride = (string)($this->settings['template_override'] ?? '0');
            $templateSelection = (string)($this->settings['template_selection'] ?? '');

            // DEFAULT TEMPLATE BEHAVIOUR FROM PLUGIN SETTINGS
            if ($templateSelection == PluginSettingsService::TEMPLATE_SELECTION_FLUID) {
                $type = PluginSettingsService::TEMPLATE_SELECTION_FLUID;
                $source = $fluidCode;
            } else if ($templateSelection == PluginSettingsService::TEMPLATE_SELECTION_CUSTOM) {
                if ($templateOverride === '1') {
                    // Selected template is a file relation
                    /* @var \TYPO3\CMS\Core\Resource\FileReference $fileRelation */
                    $contentUid = $this->request->getAttribute('currentContentObject')?->data['uid'] ?? 0;
                    $fileRelation = $this->fileRepository->findByRelation('tt_content', 'settings.template_override', (int)$contentUid);
                    if (!is_array($fileRelation) || empty($fileRelation)) {
                        // Backward compatibility for records that stored plain fieldname.
                        $fileRelation = $this->fileRepository->findByRelation('tt_content', 'template_override', (int)$contentUid);
                    }
                    if (is_array($fileRelation) && !empty($fileRelation)) {
                        $fileRelation = reset($fileRelation);
                        $source = $fileRelation->getOriginalFile()->getForLocalProcessing(false);
                    } else {
                        $this->addFlashMessage(
                            LocalizationUtility::translate('LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:plugin.records.message.template_not_found'),
                            '',
                            ContextualFeedbackSeverity::ERROR
                        );
                        $type = PluginSettingsService::TEMPLATE_SELECTION_DEBUG;
                        $source = '';
                    }
                } else {
                    // Selected template is a preconfigured template file from typoscript settings
                    $type = PluginSettingsService::TEMPLATE_SELECTION_DEBUG;
                }
            } else {
                $type = $templateSelection;
                if ($templateSelection == '') {
                    $type = PluginSettingsService::TEMPLATE_SELECTION_DEBUG;
                } else {
                    $source = $this->pluginSettingsService->getPredefinedTemplateById($templateSelection);
                }
            }
        }

        switch ($type) {
            case PluginSettingsService::TEMPLATE_SELECTION_FLUID:
                if (is_null($view->getRenderingContext())) {
                    $view = GeneralUtility::makeInstance(StandaloneView::class);
                }
                $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($source);
                break;
            case PluginSettingsService::TEMPLATE_SELECTION_DEBUG:
                break;
            case PluginSettingsService::TEMPLATE_SELECTION_CUSTOM:
            default:
                // A template id was selected, so we need to get the template path
                if ($view instanceof FluidViewAdapter) {
                    $view->getRenderingContext()->getTemplatePaths()->setTemplatePathAndFilename($source);
                } else if (method_exists($view, 'setTemplatePathAndFilename')) {
                    $view->setTemplatePathAndFilename($source);
                } else if (!is_null($view->getRenderingContext())) {
                    $view->getRenderingContext()->getTemplatePaths()->setTemplatePathAndFilename($source);
                }
                break;
        }

        $view->assignMultiple($variables);
    }

    /**
     * Gets an array with variables, configured in the system
     * @param bool $returnOnlyValues
     * @return array
     * @throws \Exception
     */
    protected function _getEnvironmentalVariables(bool $returnOnlyValues = true): array
    {
        // Prepare Cache Identifier
        $this->prepareCacheIdentifier();
        $cacheIdentifierVariables = $this->cacheIdentifier . '_variables';

        $staticCache = (bool)($this->settings['static_cache']??false);

        // Check for cache existence and directly jump back, when cache is found, to prevent additional processes
        // PLEASE NOTE:
        // There is a big problem with this cache, because it does not return the objects, that have been cached
        // before. Instead the values are converted to arrays with plain data!
        if ((true === $staticCache) && $this->getCache()->has($this->cacheIdentifier)) {

            // We check the cache for our variables
            if ($this->getCache()->has($cacheIdentifierVariables)) {
                $cached = json_decode($this->getCache()->get($cacheIdentifierVariables), true);
                if(null !== $cached) {
                  return $cached;
                }
            }

            return [];
        }

        $frameworkConfiguration = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );
        $controllerConfiguration = $frameworkConfiguration['controllerConfiguration'] ?? [];
        $action = $this->actionMethodName;
        if (array_key_exists(RecordController::class, $controllerConfiguration)) {
            $action = reset($controllerConfiguration[RecordController::class]['actions']) . 'Action';
        }

        if(!empty($this->variables[(int)$returnOnlyValues])) {
            return $this->variables[(int)$returnOnlyValues];
        }


        // Obtain used variables
        $variableIds = GeneralUtility::intExplode(',', $this->settings['variables'],true);
        $variables = [];
        if (!empty($variableIds)) {
            $variables = $this->variableRepository->findByUids($variableIds);
        }

        $vars = [];
        foreach ($variables as $_v) {
            /* @var Variable $_v */
            $vars[$_v->getVariableName()] = ($returnOnlyValues === true)?$_v->getValue():$_v;
        }

        // Default Variables
        $vars[self::DEFAULT_VAR_SETTINGS] = $this->settings;
        $vars[self::DEFAULT_VAR_COBJ] = $this->request->getAttribute('currentContentObject')?->data ?? [];

        // Datatype
        $datatypeUid = ($this->settings['datatype_selection']??0);
        if ($datatypeUid && $datatypeUid > 0) {
            $datatype = $this->datatypeRepository->findByUid($datatypeUid);
            $vars[self::DEFAULT_VAR_DATATYPE] = $datatype;

            if($datatype instanceof Datatype) {
                /* @var Datatype $datatype */
                // Selected datatype is ok, so we need to get an instance of the according repository
                /* @var AbstractRepository $repository */
                $repository = $datatype->getRepository();
                if ($repository instanceof AbstractRepository) {
                    // Record or records context
                    switch ($action) {
                        case 'detailAction':
                            $recordUid = (int)$this->settings['single_record_selection'];
                            $record = $repository->findByUid($recordUid);
                            $vars[$this->settings['singleRecordVariableName']] = $record;
                            $vars[self::DEFAULT_VAR_RECORD_UID] = $recordUid;
                            $vars[self::DEFAULT_VAR_DETAILPID] = $this->_getDetailPid($vars);
                            break;
                        case 'dynamicDetailAction':
                            $recordUid = (int)($this->request->getArguments()[$this->settings['singleRecordVariableName']]??0);
                            $record = $repository->findByUid($recordUid);
                            $vars[$this->settings['singleRecordVariableName']] = $record;
                            $vars[self::DEFAULT_VAR_RECORD_UID] = $recordUid;
                            $vars[self::DEFAULT_VAR_DETAILPID] = $this->_getDetailPid($vars);
                            break;
                        case 'listAction':
                            /* @var Query $query */
                            /* @var QueryBuilder $queryBuilder */
                            $query = $repository->createQuery();
                            $pages = (string)($this->request->getAttribute('currentContentObject')?->data['pages'] ?? '');
                            $storagePids = GeneralUtility::intExplode(',', $pages, true);

                            $query = $this->extbaseQueryService->applySettingsToQuery($query, $this->settings, $storagePids);
                            $query = $this->extbaseQueryService->applyFiltersToQuery($query, $this->settings, $vars);
                            $result = $this->extbaseQueryService->applyPostProcessFiltersToQuery($query, $this->settings, $vars);

                            $vars[self::DEFAULT_VAR_OVERALLCOUNT] = $result->count();
                            $result = $this->extbaseQueryService->applyLimitOffsetToQuery($result, $this->settings, $vars);

                            if(isset($this->settings['debug']) && $this->settings['debug'] == 1) {
                                $queryParser = GeneralUtility::makeInstance(Typo3DbQueryParser::class);
                                echo "<code>".$queryParser->convertQueryToDoctrineQueryBuilder($result)->getSQL()."</code>";
                            }

                            $records = $result->execute();

                            // Check for pagination
                            $enablePagination = (bool)($this->settings['enable_pagination']??false);

                            if($enablePagination === true) {
                                $requestArguments = $this->request->getArguments();
                                $queryParams = $this->request->getQueryParams();
                                $itemsPerPageVariableName = (string)($this->settings['items_per_page_variable'] ?? 'itemsPerPage');
                                if ($itemsPerPageVariableName === '') {
                                    $itemsPerPageVariableName = 'itemsPerPage';
                                }
                                $itemsPerPageRaw = $requestArguments[$itemsPerPageVariableName]
                                    ?? $queryParams[$itemsPerPageVariableName]
                                    ?? $vars[$itemsPerPageVariableName]
                                    ?? self::PAGINATION_DEFAULT_ITEMS_PER_PAGE;
                                $itemsPerPage = (int)$itemsPerPageRaw;
                                $settingsDefaultPage = (int)($this->settings['default_page'] ?? 1);
                                $pageNumberVariableName = (string)($this->settings['page_number_variable'] ?? 'currentPage');
                                if ($pageNumberVariableName === '') {
                                    $pageNumberVariableName = 'currentPage';
                                }
                                $currentPageRaw = ($requestArguments[$pageNumberVariableName] ?? null)
                                    ?? ($queryParams[$pageNumberVariableName] ?? null)
                                    ?? ($vars[$pageNumberVariableName] ?? null)
                                    ?? $settingsDefaultPage;
                                $currentPage = (int)$currentPageRaw;

                                // Current page needs to be at least 1
                                if($currentPage <= 0) {
                                    $currentPage = 1;
                                }

                                $paginator = GeneralUtility::makeInstance(QueryResultPaginator::class, $records, $currentPage, $itemsPerPage);
                                $maximumNumberOfLinks = 5;
                                /** @var SlidingWindowPagination $paging */
                                $paging = GeneralUtility::makeInstance(SlidingWindowPagination::class, $paginator, $maximumNumberOfLinks);

                                $vars[self::DEFAULT_VAR_PAGINATOR] = $paginator;
                                $vars[self::DEFAULT_VAR_PAGING] = $paging;
                                $vars[self::DEFAULT_VAR_PAGES] = range($paging->getDisplayRangeStart(), $paging->getDisplayRangeEnd());
                                $vars[self::DEFAULT_VAR_ITEMS_PER_PAGE_VARIABLE_NAME] = $itemsPerPageVariableName;
                                $vars[self::DEFAULT_VAR_PAGE_NUMBER_VARIABLE_NAME] = $pageNumberVariableName;
                            }

                            $vars[$this->settings['recordsVariableName']] = $records;
                            $vars[self::DEFAULT_VAR_DETAILPID] = $this->_getDetailPid($vars);
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        $this->variables[(int)$returnOnlyValues] = $vars;

        // Put variables into cache
        $this->prepareVarsForCaching($vars);
        // TESTING FOR VARIABLE/OBJECT CACHING
        // @see https://forge.typo3.org/issues/95899
        // @see https://forge.typo3.org/issues/103040
        /*
        echo \TYPO3\CMS\Core\Utility\DebugUtility::debug($vars, __METHOD__.'@'.__LINE__);
        $this->getCache()->set($cacheIdentifierVariables, json_encode($vars), [], $this->pluginSettingsService->getCacheLifetime());
        $c = json_decode($this->getCache()->get($cacheIdentifierVariables), true);
        echo \TYPO3\CMS\Core\Utility\DebugUtility::debug($c, __METHOD__ . '@' . __LINE__);
        die();
        */

        $this->getCache()->set($cacheIdentifierVariables, json_encode($vars), [], $this->pluginSettingsService->getCacheLifetime());

        return $this->variables[(int)$returnOnlyValues];
    }

    protected function bindDatatypeToRecordVariables(array &$variables): void
    {
        $datatype = $variables[self::DEFAULT_VAR_DATATYPE] ?? null;
        if (!$datatype instanceof Datatype) {
            return;
        }

        $singleRecordVariableName = (string)($this->settings['singleRecordVariableName'] ?? '');
        if (
            $singleRecordVariableName !== ''
            && isset($variables[$singleRecordVariableName])
            && $variables[$singleRecordVariableName] instanceof AbstractRecordModel
        ) {
            $variables[$singleRecordVariableName]->setDatatype($datatype);
        }

        $recordsVariableName = (string)($this->settings['recordsVariableName'] ?? '');
        if (
            $recordsVariableName !== ''
            && isset($variables[$recordsVariableName])
            && is_iterable($variables[$recordsVariableName])
        ) {
            foreach ($variables[$recordsVariableName] as $record) {
                if ($record instanceof AbstractRecordModel) {
                    $record->setDatatype($datatype);
                }
            }
        }
    }

    /**
     * Generates a cache identifier by a few environment settings
     * such as Filters, Sorting, Pagination
     * @param array $additionalCacheParameters
     * @return string
     */
    public function prepareCacheIdentifier(array $additionalCacheParameters = []): void
    {
        if (!$this->cacheIdentifier) {

            /** @var LanguageAspect $languageAspect */
            $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
            $languageUid    = $languageAspect->getId();
            $cacheData = [
                $this->settings,
                $_GET,
                $_POST,
                $this->request->getAttribute('currentContentObject')?->data['uid'] ?? null,
                $languageUid,
            ];

            $cacheData             = array_merge($cacheData, $additionalCacheParameters);
            $this->cacheIdentifier = md5(serialize($cacheData));
        }
    }

    /**
     * @return FrontendInterface
     */
    public function getCache(): FrontendInterface
    {
        return $this->cacheManager->getCache('hash');
    }

    /**
     * Debug Action
     * @return void
     */
    public function debugAction(): void
    {
    }

    /**
     * Listing records
     * @return ResponseInterface
     */
    public function listAction(): ResponseInterface
    {
        $staticCache = (bool)($this->settings['static_cache'] ?? false);
        $content = '';

        if (true === $staticCache && $this->getCache()->has($this->cacheIdentifier)) {
            $content = $this->getCache()->get($this->cacheIdentifier) . "\r\n" . '<!-- CACHE: ' . $this->cacheIdentifier . ' -->';
        } else {
            $content = $this->view->render();
            // Set content to cache
            $this->getCache()->set($this->cacheIdentifier, $content, [], $this->pluginSettingsService->getCacheLifetime());
        }

        // Directly render the template and put anything else to exit
        if ((bool)($this->settings['render_only_template'] ?? false) === true) {
          echo $content;
          exit();
        }

        $response = $this->htmlResponse($content);
        $response = $response->withHeader('Cache-Control', 'public, max-age=86400');
        return $response;
    }

    /**
     * Showing record details
     * @return void
     */
    public function detailAction(): ResponseInterface
    {
        $response = (new ForwardResponse('dynamicDetail'))
            ->withArguments([
                $this->settings['singleRecordVariableName'] => (int)$this->settings['single_record_selection']
        ]);

        return $response;
    }

    /**
     * Showing record details
     * @return string
     */
    public function dynamicDetailAction(): ResponseInterface
    {
        $this->eventDispatcher->dispatch(new BeforeDynamicDetailViewRenderEvent(
            $this,
            $this->request,
            $this->settings,
            $this->variables
        ));

        $staticCache = (bool)($this->settings['static_cache'] ?? false);
        if (true === $staticCache && $this->getCache()->has($this->cacheIdentifier)) {
            $responseHtml = $this->getCache()->get($this->cacheIdentifier)."\r\n".'<!-- CACHE: '.$this->cacheIdentifier.' -->';
            return $this->htmlResponse($responseHtml);
        }

        $variables = $this->variables[1];
        $singleRecordVariableName = (string)($this->settings['singleRecordVariableName'] ?? 'record');
        if ($singleRecordVariableName === '') {
            $singleRecordVariableName = 'record';
        }
        $record = $variables[$singleRecordVariableName] ?? null;
        $datatype  = $variables[self::DEFAULT_VAR_DATATYPE];

        ////////////////////////////////////////////////////////////////////////
        /// This is an administration layer to quickly access the record
        /// when a backend user is logged in
        ////////////////////////////////////////////////////////////////////////
        if ($this->backendAccessService->isAdmin() && $this->backendAccessService->showRecordEditButton() && $record instanceof AbstractRecordModel) {
            echo $this->backendAccessService->getRecordEditButton($record, $record->getDatatype());
        }

        if ($record instanceof AbstractRecordModel && ($record->getDatatype()->getUid() !== $datatype->getUid())) {
            $this->addFlashMessage(LocalizationUtility::translate('plugin.records.message.record_not_allowed', [$record->getUid()]), '', ContextualFeedbackSeverity::ERROR);
            return $this->htmlResponse('');
        }

        $content = $this->view->render();

        // Set content to cache
        $this->getCache()->set($this->cacheIdentifier, $content, [], $this->pluginSettingsService->getCacheLifetime());

        // Directly render the template and put anything else to exit
        if ((bool)($this->settings['render_only_template'] ?? false) === true) {
            echo $content;
            exit();
        }

        $response = $this->htmlResponse($content);
        return $response;
    }

    /**
     * Action for plain template
     * @return string
     */
    public function plainAction(): ResponseInterface
    {
        $content = '';
        $staticCache = (bool)($this->settings['static_cache'] ?? false);
        if (true === $staticCache) {
            if ($this->getCache()->has($this->cacheIdentifier)) {
                $content = $this->getCache()->get($this->cacheIdentifier) . "\r\n" . '<!-- CACHE: ' . $this->cacheIdentifier . ' -->';
            }
        } else {
          $content = $this->view->render();
          // Set content to cache
          $this->getCache()->set($this->cacheIdentifier, $content, [], $this->pluginSettingsService->getCacheLifetime());
        }

        // Directly render the template and put anything else to exit
        if ((bool)($this->settings['render_only_template'] ?? false) === true) {
          echo $content;
          exit();
        }

        $response = $this->htmlResponse($content);
        return $response;
    }


    /**
     * Get the detail page uid
     * @param array $variables
     * @return int|null
     */
    protected function _getDetailPid(array $variables): ?int
    {
        $configuration = ($this->settings['detail_pid']??false);

        if(!is_array($configuration)) {
            return null;
        }

        foreach ($configuration as $_detailPidSingle) {
            $condition = $_detailPidSingle['pageids']['condition'];
            if ($condition == '' || $this->conditionService->isValid( $condition, $variables)) {
                $rawPageId = $_detailPidSingle['pageids']['pageid'] ?? null;
                if (is_int($rawPageId) || (is_string($rawPageId) && ctype_digit($rawPageId))) {
                    return (int)$rawPageId;
                }
                if (is_string($rawPageId)) {
                    // inputLink stores e.g. "t3://page?uid=123"
                    if (preg_match('/(?:\\?|&)uid=(\\d+)/', $rawPageId, $matches)) {
                        return (int)$matches[1];
                    }
                }
                return null;
            }
        }

        return null;
    }

    /**
     * Gets template switch information by
     * checking all switches
     * @param array $variables
     * @return array
     */
    protected function _getTemplateSwitch(array $variables): ?array
    {
        $templateSwitch = ($this->settings['template_switch']??false);

        if (!is_array($templateSwitch)) {
            return null;
        }

        foreach ($templateSwitch as $_condition) {
            $conditionStr = $_condition['switches']['condition'];
            $templateId = $_condition['switches']['template_selection'];

            // Since we yet do not know how to render the nodes separately, we
            // just render a simple full fluid condition here
            if ($this->conditionService->isValid($conditionStr, $variables)) {

                // Enforce configuration to possibly rendering directly, when the condition is valid and only template has to be rendered
                $this->settings['render_only_template'] = (int)($_condition['switches']['render_only_template'] ?? 0);

                if ($templateId == PluginSettingsService::TEMPLATE_SELECTION_FLUID) {
                    $fluidCode = (string)($_condition['switches']['fluid_code'] ?? '');
                    if ($fluidCode === '') {
                        // Ignore incomplete switch entries and continue checking others.
                        continue;
                    }
                    return [$templateId => $fluidCode];
                }
                return [PluginSettingsService::TEMPLATE_SELECTION_CUSTOM => $templateId];
            }

        }

        return null;
    }

    /**
     * Prepares configured headers to the
     * response object by obtaining the
     * plugin configuration and setting
     * valid entries to the response object
     *
     * @return void
     */
    public function prepareConfiguredHeaders(): void
    {
        $headers = $this->getConfiguredHeaders();

        if (!empty($headers)) {
            // Setting custom headers
            foreach ($headers as $_headerName => $_headerValue) {
                $this->_headers[$_headerName] = $_headerValue;
            }
        }
    }

    /**
     * Gets all custom headers that are valid for
     * the current view
     *
     * @return array
     */
    public function getConfiguredHeaders(): array
    {
        $headerConfig = ($this->settings['custom_headers']??[]);

        if(!is_array($headerConfig)) {
            return [];
        }

        $variables = $this->_getEnvironmentalVariables();
        $headers = [];

        foreach ($headerConfig as $_header) {
            $conditionStr = $_header['headers']['condition'];
            $headerName = $_header['headers']['name'];
            $headerValue = $this->fluidRenderService->renderFluid($_header['headers']['value'], $variables);

            // Since we yet do not know how to render the nodes separately, we
            // just render a simple full fluid condition here
            if ($this->conditionService->isValid($conditionStr, $variables)) {
                $headers[$headerName] = $headerValue;
            }
        }

        return $headers;
    }


    /**
     * Updates response with the prepared headers
     *
     * @param ResponseInterface $response
     * @return void
     */
    protected function processPreparedHeaders(ResponseInterface &$response): void
    {
        if(is_array($this->_headers) && !empty($this->_headers)) {
            foreach($this->_headers as $_name=>$_value) {
                if (!is_string($_name) || trim($_name) === '' || !$this->isValidHeaderName($_name)) {
                    continue;
                }
                $updatedResponse = $response->withHeader($_name, $_value);
                $response = $updatedResponse;
            }
        }
    }

    protected function isValidHeaderName(string $name): bool
    {
        return preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/', $name) === 1;
    }

    protected function prepareVarsForCaching(array &$vars): void
    {
        foreach($vars as $_i=>$_v) {
            if(is_object($_v)) {
                switch (get_class($_v)) {
                    case \K3n\Tonictypes\Domain\Model\AbstractRecordModel::class:
                    case \K3n\Tonictypes\Domain\Model\Datatype::class:
                        $vars[$_i] = $_v->_getProperties();
                        break;
                    case \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult::class:
                        $vars[$_i] = $_v->toArray();
                        foreach($vars[$_i] as $_j=>$_item) {
                            $vars[$_i][$_j] = $_item->_getProperties();
                        }
                        break;
                    default:
                        break;
                }
            }
        }
    }
}
