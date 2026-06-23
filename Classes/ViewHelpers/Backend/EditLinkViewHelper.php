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

namespace K3n\Tonictypes\ViewHelpers\Backend;

use Closure;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * ViewHelper to create a link to the list module
 * @internal
 */
class EditLinkViewHelper extends AbstractLinkViewHelper
{
    /**
     * Arguments initialization
     *
     * @throws Exception
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('id', 'int', 'Id of the link', true);
        $this->registerArgument('table', 'string', 'Tablename', true);
        $this->registerArgument('returnUrl', 'string', 'The return url', false, null);
    }


    /**
     * @return string
     * @throws RouteNotFoundException
     */
    public function render(): string
    {
        return static::renderStatic(
            [
                'id' => $this->arguments["id"],
                'table' => $this->arguments["table"],
                'returnUrl' => $this->arguments["returnUrl"],
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws RouteNotFoundException
     */
    public static function renderStatic(array $arguments, Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        return self::getModuleUrl(
            'record_edit',
            [
                'edit['.$arguments['table'].'][' . $arguments['id'] . ']' => 'edit',
                'returnUrl' => (isset($arguments["returnUrl"]))?$arguments["returnUrl"]:GeneralUtility::getIndpEnv('REQUEST_URI'),
            ]
        );
    }
}
