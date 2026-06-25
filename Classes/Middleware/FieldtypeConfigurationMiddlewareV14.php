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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

        foreach ([$request->getQueryParams()['id'] ?? null, $request->getParsedBody()['id'] ?? null] as $id) {
            if (($pid = $this->toPositiveInt($id)) > 0) {
                return $pid;
            }
        }

        $routing = $request->getAttribute('routing');
        if ($routing instanceof PageArguments && ($pid = $routing->getPageId()) > 0) {
            return $pid;
        }

        if (($pid = $this->resolvePidFromEdit($request)) > 0) {
            return $pid;
        }

        return $this->toPositiveInt($GLOBALS['BE_USER']?->uc['moduleData']['web_layout'] ?? null);
    }

    protected function resolvePidFromEdit(ServerRequestInterface $request): int
    {
        $edit = $request->getQueryParams()['edit'] ?? $request->getParsedBody()['edit'] ?? null;
        if (!is_array($edit)) {
            return 0;
        }

        foreach ($edit as $table => $commands) {
            if (!is_array($commands)) {
                continue;
            }
            foreach ($commands as $key => $command) {
                $uid = (int)$key;
                if ($command === 'new') {
                    if ($uid > 0) {
                        return $uid;
                    }
                    if ($uid < 0) {
                        return $this->toPositiveInt(BackendUtility::getRecord($table, abs($uid), 'pid')['pid'] ?? null);
                    }
                    continue;
                }
                if ($uid <= 0) {
                    continue;
                }
                if ($table === 'pages') {
                    return $uid;
                }
                if (($pid = $this->toPositiveInt(BackendUtility::getRecord($table, $uid, 'pid')['pid'] ?? null)) > 0) {
                    return $pid;
                }
            }
        }

        return 0;
    }

    protected function toPositiveInt(mixed $value): int
    {
        if (is_array($value)) {
            $value = reset($value);
        }
        $int = is_scalar($value) ? (int)$value : 0;

        return $int > 0 ? $int : 0;
    }
}
