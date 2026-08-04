<?php

declare(strict_types=1);
/*
 * This file is part of the package k3n/tonictypes.
 */

namespace K3n\Tonictypes\Controller\Backend;

use K3n\Tonictypes\Service\Import\PredefinedDatatypeImportService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

final class PredefinedDatatypeImportController
{
    public function __construct(
        private readonly PredefinedDatatypeImportService $importService,
    ) {
    }

    public function importAction(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $storagePid = $this->resolveStoragePid($request);
            $result = $this->importService->importPredefinedArchive($storagePid);

            // Always 200 so the dashboard widget can read JSON (AjaxRequest throws on 4xx).
            return new JsonResponse($result);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : 'Import failed.',
            ]);
        }
    }

    private function resolveStoragePid(ServerRequestInterface $request): int
    {
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && isset($parsedBody['storagePid'])) {
            return (int)$parsedBody['storagePid'];
        }

        $queryParams = $request->getQueryParams();
        if (isset($queryParams['storagePid'])) {
            return (int)$queryParams['storagePid'];
        }

        // Fallback for clients that send raw body / multipart edge cases.
        $body = (string)$request->getBody();
        if ($body !== '') {
            parse_str($body, $parsed);
            if (isset($parsed['storagePid'])) {
                return (int)$parsed['storagePid'];
            }
        }

        return 0;
    }
}
