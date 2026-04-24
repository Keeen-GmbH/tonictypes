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
    /**
     * Main form rendering script
     */
    'record_edit' => [
        'path' => '/record/edit',
        'target' => \K3n\Tonictypes\Controller\Backend\EditDocumentController::class . '::mainAction'
    ],
];
