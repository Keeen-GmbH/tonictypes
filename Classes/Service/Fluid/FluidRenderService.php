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

namespace K3n\Tonictypes\Service\Fluid;

use K3n\Tonictypes\Fluid\View\StandaloneView;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FluidRenderService
{
    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @return StandaloneView $view
     */
    public function getView(): StandaloneView
    {
        if(!$this->view) {
            $this->view = GeneralUtility::makeInstance(StandaloneView::class);
            $this->view->setRequest($GLOBALS['TYPO3_REQUEST']);
        }

        return $this->view;
    }

    /**
     * Renders fluid and returns the output
     * @param string $fluid
     * @param array $variables
     * @param bool $enforceString
     * @return string
     */
    public function renderFluid(string $fluid, array $variables = [], bool $enforceString = true)
    {
        $this->getView()->assignMultiple($variables);

        if($enforceString === true) {
            return (string)$this->getView()->renderSource($fluid);
        }

        return $this->getView()->renderSource($fluid);
    }
}
