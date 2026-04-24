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

namespace K3n\Tonictypes\UserFunc;

class Rte
{
	/**
	 * Populate fields
	 *
	 * @param array $config Configuration Array
	 * @param mixed $parentObject Parent Object
	 * @return void
	 */
	public function populateRteConfigurationPresets(array &$config, &$parentObject): void
	{
		$options = [];

		$rteConfigurations = $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets'];
		$rteConfigurationIds = array_keys($rteConfigurations);

		foreach ($rteConfigurationIds as $_configId) {
            $options[] = [
                'label' => $_configId,
                'value' => $_configId
            ];
        }

		$config["items"] = $options;
	}
}
