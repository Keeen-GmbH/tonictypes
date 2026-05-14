<?php
declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

return [
    'backend' => [
        'k3n/tonictypes/tca-generator' => [
            'target' => \K3n\Tonictypes\Tca\Generator::class,
            'after' => [
                'typo3/cms-backend/site-resolver',
            ],
        ],
        'k3n/tonictypes/fieldtype-configuration-generator' => [
            'target' => GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() < 14 ? \K3n\Tonictypes\Middleware\FieldtypeConfigurationMiddleware::class : \K3n\Tonictypes\Middleware\FieldtypeConfigurationMiddlewareV14::class,
            'after' => [
                'k3n/tonictypes/tca-generator',
                'typo3/cms-backend/page-context',
            ],
        ],
    ],
    'frontend' => [
        'k3n/tonictypes/tca-generator' => [
            'target' => \K3n\Tonictypes\Tca\Generator::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
            'before' => [
                'typo3/cms-frontend/shortcut-and-mountpoint-redirect',
            ],
        ],
        'k3n/tonictypes/fieldtype-configuration-generator' => [
            'target' => GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() < 14 ? \K3n\Tonictypes\Middleware\FieldtypeConfigurationMiddleware::class : \K3n\Tonictypes\Middleware\FieldtypeConfigurationMiddlewareV14::class,
            'after' => [
                'k3n/tonictypes/tca-generator',
            ],
        ],
    ],
];
