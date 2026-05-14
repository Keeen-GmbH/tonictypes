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

namespace K3n\Tonictypes\Fluid\View;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\View\TemplatePaths;
use TYPO3Fluid\Fluid\View\TemplateView;

class StandaloneView extends TemplateView
{
    protected ?ServerRequestInterface $request = null;

    public function __construct()
    {
        parent::__construct(
            GeneralUtility::makeInstance(RenderingContextFactory::class)->create()
        );

        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request instanceof ServerRequestInterface) {
            $this->setRequest($request);
        }
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
        $renderingContext = $this->getRenderingContext();
        if (method_exists($renderingContext, 'setAttribute')) {
            $renderingContext->setAttribute(ServerRequestInterface::class, $request);
        }
    }

    public function setTemplateSource(string $templateSource): void
    {
        $this->getTemplatePaths()->setTemplateSource($templateSource);
    }

    public function setTemplatePathAndFilename(string $templatePathAndFilename): void
    {
        $this->getTemplatePaths()->setTemplatePathAndFilename($templatePathAndFilename);
    }

    public function setTemplateRootPaths(array $templateRootPaths): void
    {
        $this->getTemplatePaths()->setTemplateRootPaths($templateRootPaths);
    }

    public function setLayoutRootPaths(array $layoutRootPaths): void
    {
        $this->getTemplatePaths()->setLayoutRootPaths($layoutRootPaths);
    }

    public function setPartialRootPaths(array $partialRootPaths): void
    {
        $this->getTemplatePaths()->setPartialRootPaths($partialRootPaths);
    }

    public function renderSource(string $source): string
    {
        $this->setTemplateSource($source);
        return (string)$this->render();
    }

    public function getTemplatePaths(): TemplatePaths
    {
        return $this->getRenderingContext()->getTemplatePaths();
    }

}
