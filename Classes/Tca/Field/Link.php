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

namespace K3n\Tonictypes\Tca\Field;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Link extends Input
{
    /**
     * Gets built tca array
     *
     * @return array
     */
    public function getTca(): array
    {
        $tca = parent::getTca();
        $tca['config']['type'] = 'link';

        // allowedTypes
        if ($allowedTypes = $this->getField()->getConfig('allowedTypes')) {
            $delimeters = [
                "|",
                "/",
                ".",
                "-",
                ";",
            ];

            $tca['config']['allowedTypes'] = str_replace($delimeters,',',$allowedTypes);
            $tca['config']['allowedTypes'] = GeneralUtility::trimExplode(',',$allowedTypes,true);
        }

        return $this->mergeConfigurationToTca($tca);
    }
}
