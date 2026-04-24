<?php
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
    // required import configurations of other extensions,
    // in case a module imports from another package
    'dependencies' => [
        'core',
        'backend',
    ],
    'tags' => [
        'backend.form',
        'backend.module',
        'backend.navigation-component',
        'backend.contextmenu',
    ],
    'imports' => [
        // recursive definiton, all *.js files in this folder are import-mapped
        // trailing slash is required per importmap-specification
        '@k3n/tonictypes/' => [
            'path' => 'EXT:tonictypes/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:tonictypes/Resources/Public/JavaScript/Contrib/',
            ],
        ],
        'jquery-extendext' => 'EXT:tonictypes/Resources/Public/JavaScript/Contrib/jquery-extendext.js',
        'query-builder' => 'EXT:tonictypes/Resources/Public/JavaScript/Contrib/query-builder.js',
        'query-builder-templates' => 'EXT:tonictypes/Resources/Public/JavaScript/QueryBuilderTemplates.js',
    ],
];
