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
use TYPO3\CMS\Backend\Context\PageContext;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Injects field-type flexform TCA once TypoScript is available (per request).
 *
 * This cannot run at {@see \TYPO3\CMS\Core\Core\Event\BootCompletedEvent}: TypoScript for
 * {@see FieldSettingsService::getTcaFlexFormConfiguration()} is not loaded yet at that point.
 */
class FieldtypeConfigurationMiddlewareV14 implements MiddlewareInterface
{
    public function __construct(
        private readonly FieldSettingsService $fieldSettingsService,
        private readonly FieldtypeFlexformTcaApplicator $fieldtypeFlexformTcaApplicator,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pid = $this->resolvePidFromRequest($request);
        $fieldFlexformConfig = $this->fieldSettingsService->getTcaFlexFormConfiguration($pid);
        if (!is_array($fieldFlexformConfig)) {
            $fieldFlexformConfig = [];
        }
        $this->fieldtypeFlexformTcaApplicator->apply($fieldFlexformConfig);
        // FormEngine / schema layer must see merged flexform DS (Generator already rebuilt once earlier).
        $tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        $tcaSchemaFactory->rebuild($GLOBALS['TCA']);

        return $handler->handle($request);
    }

    protected function resolvePidFromRequest(ServerRequestInterface $request): int
    {
        $pageContext = $request->getAttribute('pageContext');
        if ($pageContext instanceof PageContext && $pageContext->pageId > 0) {
            return $pageContext->pageId;
        }

        foreach ([$request->getQueryParams()['id'] ?? null, $request->getParsedBody()['id'] ?? null] as $candidate) {
            $pid = $this->extractPositiveInt($candidate);
            if ($pid > 0) {
                return $pid;
            }
        }

        $routing = $request->getAttribute('routing');
        if ($routing instanceof PageArguments) {
            $pid = $routing->getPageId();
            if ($pid > 0) {
                return $pid;
            }
        }

        $beUser = $GLOBALS['BE_USER'] ?? null;
        if ($beUser !== null) {
            $moduleData = $beUser->uc['moduleData']['web_layout'] ?? null;
            $pid = $this->extractPositiveInt($moduleData);
            if ($pid > 0) {
                return $pid;
            }
        }

        return 0;
    }

    protected function extractPositiveInt(mixed $value): int
    {
        if (is_array($value)) {
            $value = reset($value);
        }
        if (!is_scalar($value)) {
            return 0;
        }
        $stringValue = (string)$value;
        if (!MathUtility::canBeInterpretedAsInteger($stringValue)) {
            return 0;
        }
        $intValue = (int)$stringValue;

        return $intValue > 0 ? $intValue : 0;
    }
}
