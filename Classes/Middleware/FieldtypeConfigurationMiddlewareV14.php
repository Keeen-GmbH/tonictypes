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

namespace K3n\Tonictypes\Middleware;

use K3n\Tonictypes\Service\Settings\FieldSettingsService;
use K3n\Tonictypes\Tca\FieldtypeFlexformTcaApplicator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Injects field-type flexform TCA once TypoScript is available (TYPO3 v14).
 */
class FieldtypeConfigurationMiddlewareV14 implements MiddlewareInterface
{
    use ResolvesBackendPageIdFromRequest;

    public function __construct(
        private readonly FieldSettingsService $fieldSettingsService,
        private readonly FieldtypeFlexformTcaApplicator $fieldtypeFlexformTcaApplicator,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pid = $this->resolvePidFromRequest($request);
        $this->fieldtypeFlexformTcaApplicator->apply(
            $this->fieldSettingsService->getTcaFlexFormConfiguration($pid)
        );
        GeneralUtility::makeInstance(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        return $handler->handle($request);
    }
}
