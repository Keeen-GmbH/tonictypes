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
    // Table Routes
    'tonictypes_table_status' => [
        'path' => '/tonictypes/table/status',
        'target' => \K3n\Tonictypes\Controller\Backend\TableController::class . '::tableStatusAction'
    ],
    'tonictypes_table_migrate' => [
        'path' => '/tonictypes/table/migrate',
        'target' => \K3n\Tonictypes\Controller\Backend\TableController::class . '::tableMigrateAction'
    ],
    'tonictypes_table_delete' => [
        'path' => '/tonictypes/table/delete',
        'target' => \K3n\Tonictypes\Controller\Backend\TableController::class . '::tableDeleteAction'
    ],
    'tonictypes_table_generate_tca' => [
        'path' => '/tonictypes/table/generate-tca',
        'target' => \K3n\Tonictypes\Controller\Backend\TableController::class . '::tableGenerateTcaAction'
    ],

    // Class Routes
    'tonictypes_class_status' => [
        'path' => '/tonictypes/class/status',
        'target' => \K3n\Tonictypes\Controller\Backend\ClassController::class . '::classStatusAction'
    ],
    'tonictypes_class_migrate' => [
        'path' => '/tonictypes/class/migrate',
        'target' => \K3n\Tonictypes\Controller\Backend\ClassController::class . '::classMigrateAction'
    ],
    'tonictypes_class_delete' => [
        'path' => '/tonictypes/class/delete',
        'target' => \K3n\Tonictypes\Controller\Backend\ClassController::class . '::classDeleteAction'
    ],

    // Querybuilder related
    'tonictypes_querybuilder_configuration_get' => [
        'path' => '/tonictypes/querybuilder/configuration/get',
        'target' => \K3n\Tonictypes\Controller\Backend\QueryBuilderController::class . '::getConfigurationAction'
    ],
];
