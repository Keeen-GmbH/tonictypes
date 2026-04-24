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

namespace K3n\Tonictypes\ViewHelpers\String;

use K3n\Tonictypes\Utility\StringUtility;
use K3n\Tonictypes\ViewHelpers\AbstractViewHelper;


class CodeFromStringViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('string', 'string', 'String to create code from', true, '');
        parent::initializeArguments();
    }

    /**
     * Creates code from a string
     *
     * @return string
     */
    public function render(): string
    {
        $string = $this->arguments['string'];
        return StringUtility::createCodeFromString($string);
    }
}