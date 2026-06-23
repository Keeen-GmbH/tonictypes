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

namespace K3n\Tonictypes\ViewHelpers\Backend\Render;

use K3n\Tonictypes\ViewHelpers\AbstractViewHelper;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

abstract class AbstractRenderViewHelper extends AbstractViewHelper
{
    /**
     * Specifies whether the escaping interceptors should be disabled or enabled for the render-result of this ViewHelper
     * @see isOutputEscapingEnabled()
     *
     * @var boolean
     * @api
     */
    protected $escapeOutput = false;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @param IconFactory $iconFactory
     */
    public function injectIconFactory(IconFactory $iconFactory)
    {
        $this->iconFactory = $iconFactory;
    }

    /**
     * Arguments initialization
     * @throws Exception
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
    }

}
