<?php
declare(strict_types=1);
/*
 * This file is part of the package k3n/tonictypes.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * Many thanks to Auth: B. Zagar / Maint: J. Pietschmann for sharing this extension - TYPO3 inspiring people to share!
 * Contact: support@tonictypes.com
 *
 */

namespace K3n\Tonictypes\Middleware;

use K3n\Tonictypes\Configuration\ConfigurationRegistry;
use K3n\Tonictypes\Icon\TonictypesIconRegistry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendInitializationMiddleware implements MiddlewareInterface
{
    protected static bool $initialized = false;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!self::$initialized) {
            // Run once per request lifecycle, after TYPO3 bootstrap is complete.
            /** @var TonictypesIconRegistry $tonictypesIconRegistry */
            $tonictypesIconRegistry = GeneralUtility::makeInstance(TonictypesIconRegistry::class);
            /** @var ConfigurationRegistry $configurationRegistry */
            $configurationRegistry = GeneralUtility::makeInstance(ConfigurationRegistry::class);

            $tonictypesIconRegistry->registerDatatypeIcons();
            $tonictypesIconRegistry->registerTonictypesIcons();
            $configurationRegistry->registerTableOrderings();
            self::$initialized = true;
        }

        return $handler->handle($request);
    }
}
