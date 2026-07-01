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
    protected ?string $cacheIdentifier = null;

    public function __construct(
        protected readonly CacheManager $cacheManager,
        protected readonly DatatypeRepository $datatypeRepository,
        protected readonly FileRepository $fileRepository,
        protected readonly VariableRepository $variableRepository,
        protected readonly BackendAccessService $backendAccessService,
        protected readonly FluidRenderService $fluidRenderService,
        protected readonly QueryFilterService $queryFilterService,
        protected readonly ExtbaseQueryService $extbaseQueryService,
        protected readonly ConditionService $conditionService,
        protected readonly PluginSettingsService $pluginSettingsService,
    ) {
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
