<?php

declare(strict_types=1);

/*
 * This file is part of the package k3n/tonictypes.
 */

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'extensions-tonictypes-dashboard' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:tonictypes/Resources/Public/Icons/Extension.svg',
    ],
];
