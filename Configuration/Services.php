<?php

declare(strict_types=1);

use K3n\Tonictypes\EventListener\NewContentElementPreviewRenderer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Core\Information\Typo3Version;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder): void {
    if ((new Typo3Version())->getMajorVersion() < 13) {
        return;
    }

    $container->services()
        ->set(NewContentElementPreviewRenderer::class)
        ->autowire()
        ->autoconfigure()
        ->public(false)
        ->tag('event.listener', [
            'identifier' => 'tonictypes/flexform-process',
            'event' => PageContentPreviewRenderingEvent::class,
        ]);
};
