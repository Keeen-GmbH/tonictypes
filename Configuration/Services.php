<?php

declare(strict_types=1);

use K3n\Tonictypes\EventListener\NewContentElementPreviewRenderer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Register backend preview event listener for TYPO3 v14+ only.
 * v12/v13 use page TSconfig (mod.web_layout.tt_content.preview.*).
 */
return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder): void {
    if ((new Typo3Version())->getMajorVersion() < 14) {
        return;
    }

    $container->services()
        ->set(NewContentElementPreviewRenderer::class)
        ->autowire(false)
        ->autoconfigure(false)
        ->public(false)
        ->tag('event.listener', [
            'identifier' => 'tonictypes/preview-renderer',
            'event' => PageContentPreviewRenderingEvent::class,
            'method' => '__invoke',
            'before' => 'typo3-backend/fluid-preview/content',
        ]);
};
