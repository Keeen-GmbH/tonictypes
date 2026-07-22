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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Resolves backend page/storage id for TypoScript (v12–v14; PageContext detected at runtime).
 */
trait ResolvesBackendPageIdFromRequest
{
    protected function resolvePidFromRequest(ServerRequestInterface $request): int
    {
        $pageContext = $request->getAttribute('pageContext');
        if (is_object($pageContext) && isset($pageContext->pageId) && (int)$pageContext->pageId > 0) {
            return (int)$pageContext->pageId;
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
                        $record = BackendUtility::getRecord($table, abs($uid), 'pid');
                        return is_array($record) ? $this->toPositiveInt($record['pid'] ?? null) : 0;
                    }
                    continue;
                }
                if ($uid <= 0) {
                    continue;
                }
                if ($table === 'pages') {
                    return $uid;
                }
                $record = BackendUtility::getRecord($table, $uid, 'pid');
                if (is_array($record) && ($pid = $this->toPositiveInt($record['pid'] ?? null)) > 0) {
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
        if (!MathUtility::canBeInterpretedAsInteger($value)) {
            return 0;
        }
        $int = (int)$value;
        return $int > 0 ? $int : 0;
    }
}
