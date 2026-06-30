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
            $parsedBody = $request->getParsedBody();
            $storagePid = (int)($parsedBody['storagePid'] ?? 0);

            return new JsonResponse($this->importService->importPredefinedArchive($storagePid));
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 400);
        }
    }
}
