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
namespace K3n\Tonictypes\UserFunc;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class Extension
{
    /**
     * Populate extensions
     * @param array $config Configuration Array
     * @param mixed $parentObject Parent Object
     * @return void
     */
    public function populateLoadedExtensions(array &$config, &$parentObject): void
    {
        $options = [];
        $extList = ExtensionManagementUtility::getLoadedExtensionListArray();
        sort($extList);

        foreach($extList as $_i=>$_ext) {
            $options[] = [
                'label' => $_ext,
                'value' => $_ext
            ];
        }

        if (is_array($config['items'])) {
            $config['items'] = array_merge($config['items'], $options);
        } else {
            $config['items'] = $options;
        }
    }

}
