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


use K3n\Tonictypes\Factory\ClassFactory;
use TYPO3\CMS\Install\Service\ClearCacheService;

abstract class AbstractBackendController
{
    /**
     * @var ClassFactory
     */
    protected $classFactory;

    /**
     * @var ClearCacheService
     */
    protected $clearCacheService;

    /**
     * @param ClassFactory $classFactory
     */
    public function injectClassFactory(ClassFactory $classFactory)
    {
        $this->classFactory = $classFactory;
    }

    /**
     * @param ClearCacheService $clearCacheService
     */
    public function injectClearCacheService(ClearCacheService $clearCacheService)
    {
        $this->clearCacheService = $clearCacheService;
    }

    public function clearAutoloadAndCache(): void
    {
        // Dump autoload, if TYPO3 is not in composer mode
        if (!defined('TYPO3_COMPOSER_MODE')) {
            $this->classFactory->dumpAutoload();
        }

        // Clear all cache
        $this->clearCacheService->clearAll();
    }
}
