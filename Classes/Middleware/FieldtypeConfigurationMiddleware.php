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

use K3n\Tonictypes\Configuration\ExtensionConfiguration;
use K3n\Tonictypes\Service\Settings\FieldSettingsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;

class FieldtypeConfigurationMiddleware implements MiddlewareInterface
{
    /**
     * TCA Cache Service
     *
     * @var FieldSettingsService
     */
    protected $fieldSettingsService;

    /**
     * @param FieldSettingsService $fieldSettingsService
     */
    public function injectFieldSettingsService(FieldSettingsService $fieldSettingsService)
    {
        $this->fieldSettingsService = $fieldSettingsService;
    }

    /**
     * Middleware to inject field configuration into globals
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $fieldFlexformConfig = $this->fieldSettingsService->getTcaFlexFormConfiguration();

        $GLOBALS["TCA"][ExtensionConfiguration::EXTENSION_FIELD_TABLE]['columns']['field_conf']['config']['ds'] =
            array_merge($GLOBALS["TCA"][ExtensionConfiguration::EXTENSION_FIELD_TABLE]['columns']['field_conf']['config']['ds'], $fieldFlexformConfig);

        return $handler->handle($request);
    }
}
