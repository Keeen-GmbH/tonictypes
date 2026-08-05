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
use K3n\Tonictypes\Tca\TcaSchemaSynchronizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 v12/v13: merge field-type FlexForm DS into TCA via ds_pointerField map.
 */
class FieldtypeConfigurationMiddleware implements MiddlewareInterface
{
    use ResolvesBackendPageIdFromRequest;

    public function __construct(
        private readonly FieldSettingsService $fieldSettingsService,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pid = $this->resolvePidFromRequest($request);
        $fieldFlexformConfig = $this->fieldSettingsService->getTcaFlexFormConfiguration($pid);
        $fieldTable = ExtensionConfiguration::EXTENSION_FIELD_TABLE;

        $existingDs = $GLOBALS['TCA'][$fieldTable]['columns']['field_conf']['config']['ds'] ?? [];
        if (is_string($existingDs)) {
            $existingDs = $existingDs !== '' ? ['default' => $existingDs] : [];
        } elseif (!is_array($existingDs)) {
            $existingDs = [];
        }

        $mergedDs = array_merge($existingDs, $fieldFlexformConfig);
        if (!isset($mergedDs['default']) || $mergedDs['default'] === '') {
            $mergedDs['default'] = 'FILE:EXT:tonictypes/Configuration/FlexForms/Field/Empty.xml';
        }

        $GLOBALS['TCA'][$fieldTable]['columns']['field_conf']['config']['ds'] = $mergedDs;
        $GLOBALS['TCA'][$fieldTable]['columns']['field_conf']['config']['ds_pointerField'] =
            $GLOBALS['TCA'][$fieldTable]['columns']['field_conf']['config']['ds_pointerField'] ?? 'type';

        $tcaSchemaFactoryClass = 'TYPO3\\CMS\\Core\\Schema\\TcaSchemaFactory';
        if (class_exists($tcaSchemaFactoryClass)) {
            GeneralUtility::makeInstance(TcaSchemaSynchronizer::class)
                ->synchronize(GeneralUtility::makeInstance($tcaSchemaFactoryClass));
        }

        return $handler->handle($request);
    }
}
