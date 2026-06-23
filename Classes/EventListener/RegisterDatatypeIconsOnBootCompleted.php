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

namespace K3n\Tonictypes\EventListener;

use K3n\Tonictypes\Icon\TonictypesIconRegistry;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;

/**
 * Registers datatype icons after bootstrap.
 *
 * Dynamic field flexform TCA must not be applied here: TypoScript is not available yet, so
 * {@see \K3n\Tonictypes\Middleware\FieldtypeConfigurationMiddleware} applies it per request.
 */
final class RegisterDatatypeIconsOnBootCompleted
{
    public function __construct(
        private readonly TonictypesIconRegistry $iconRegistry
    ) {}

    public function __invoke(BootCompletedEvent $event): void
    {
        $this->iconRegistry->registerTonictypesIcons();
        $this->iconRegistry->registerDatatypeIcons();
    }
}
