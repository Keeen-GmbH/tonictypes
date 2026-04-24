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

namespace K3n\Tonictypes\Fluid\View;

use TYPO3\CMS\Fluid\View\StandaloneView as TYPO3StandaloneView;

class StandaloneView extends TYPO3StandaloneView
{
	/**
	 * Renders template source code by a given string
	 *
	 * @param string $source Template Source Code
	 * @return string
	 */
	public function renderSource($source): string
	{
		$this->setTemplateSource($source);
		return (string)$this->render();
	}

}
