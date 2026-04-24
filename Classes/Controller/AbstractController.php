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

use K3n\Tonictypes\Domain\Repository\DatatypeRepository;
use K3n\Tonictypes\Domain\Repository\VariableRepository;
use K3n\Tonictypes\Service\Backend\BackendAccessService;
use K3n\Tonictypes\Service\Fluid\ConditionService;
use K3n\Tonictypes\Service\Fluid\FluidRenderService;
use K3n\Tonictypes\Service\Query\ExtbaseQueryService;
use K3n\Tonictypes\Service\Query\QueryFilterService;
use K3n\Tonictypes\Service\Settings\Plugin\PluginSettingsService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

abstract class AbstractController extends ActionController
{
    /**
     * @var string
     */
    protected $cacheIdentifier;

    /**
     * @var \TYPO3\CMS\Core\Cache\CacheManager
     */
    protected $cacheManager;

    /**
     * @var DatatypeRepository
     */
    protected $datatypeRepository;

    /**
     * @var FileRepository
     */
    protected $fileRepository;

    /**
     * @var VariableRepository
     */
    protected $variableRepository;

    /**
     * @var BackendAccessService
     */
    protected $backendAccessService;

    /**
     * @var FluidRenderService
     */
    protected $fluidRenderService;

    /**
     * @var QueryFilterService
     */
    protected $queryFilterService;

    /**
     * @var ExtbaseQueryService
     */
    protected $extbaseQueryService;

    /**
     * @var ConditionService
     */
    protected $conditionService;

    /**
     * @var PluginSettingsService
     */
    protected $pluginSettingsService;

    /**
     * @param CacheManager $cacheManager
     * @return void
     */
    public function injectCacheManager(CacheManager $cacheManager): void
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param DatatypeRepository $datatypeRepository
     * @return void
     */
    public function injectDatatypeRepository(DatatypeRepository $datatypeRepository): void
    {
        $this->datatypeRepository = $datatypeRepository;
    }

    /**
     * @param FileRepository $fileRepository
     * @return void
     */
    public function injectFileRepository(FileRepository $fileRepository): void
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * @param VariableRepository $variableRepository
     * @return void
     */
    public function injectVariableRepository(VariableRepository $variableRepository): void
    {
        $this->variableRepository = $variableRepository;
    }

    /**
     * @param FluidRenderService $fluidRenderService
     * @return void
     */
    public function injectFluidRenderService(FluidRenderService $fluidRenderService): void
    {
        $this->fluidRenderService = $fluidRenderService;
    }

    /**
     * @param BackendAccessService $backendAccessService
     * @return void
     */
    public function injectBackendAccessService(BackendAccessService $backendAccessService): void
    {
        $this->backendAccessService = $backendAccessService;
    }

    /**
     * @param QueryFilterService $queryFilterService
     * @return void
     */
    public function injectQueryFilterService(QueryFilterService $queryFilterService): void
    {
        $this->queryFilterService = $queryFilterService;
    }

    /**
     * @param ExtbaseQueryService $extbaseQueryService
     * @return void
     */
    public function injectExtbaseQueryService(ExtbaseQueryService $extbaseQueryService): void
    {
        $this->extbaseQueryService = $extbaseQueryService;
    }

    /**
     * @param ConditionService $conditionService
     * @return void
     */
    public function injectConditionService(ConditionService $conditionService): void
    {
        $this->conditionService = $conditionService;
    }

    /**
     * @param PluginSettingsService $pluginSettingsService\
     * @return void
     */
    public function injectPluginSettingsService(PluginSettingsService $pluginSettingsService): void
    {
        $this->pluginSettingsService = $pluginSettingsService;
    }

    /**
     * Generates a cache identifier by a few environment settings
     * such as Filters, Sorting, Pagination
     * @param array $additionalCacheParameters
     * @return string
     */
    public function getCacheIdentifier(array $additionalCacheParameters = [])
    {
        if(!$this->cacheIdentifier)
        {
            $cacheData = [
                $this->settings,
                $_GET,
                $_POST,
                $this->configurationManager->getContentObject()->data,
            ];

            $cacheData = array_merge($cacheData, $additionalCacheParameters);
            $this->cacheIdentifier = md5(serialize($cacheData));
        }

        return $this->cacheIdentifier;
    }
}
