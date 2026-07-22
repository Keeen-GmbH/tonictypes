<?php
/*
 * This file is part of the package k3n/tonictypes.
 */

use K3n\Tonictypes\Controller\DatatypeTransferController;

return [
    'k3n_tonictypes_transfer' => [
        'parent' => 'system',
        'position' => ['after' => '*'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/system/TonictypesTransfer',
        'iconIdentifier' => 'extensions-tonictypes-transfer',
        'labels' => 'LLL:EXT:tonictypes/Resources/Private/Language/locallang_mod_transfer.xlf',
        'aliases' => [
            'web_tonictypespro_transfer',
            'k3n_tonictypespro_transfer',
        ],
        'inheritNavigationComponentFromMainModule' => false,
        'extensionName' => 'Tonictypes',
        'controllerActions' => [
            DatatypeTransferController::class => [
                'index',
                'export',
                'importPreview',
                'importExecute',
            ],
        ],
    ],
];
