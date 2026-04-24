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

namespace K3n\Tonictypes\ViewHelpers\Array;

use K3n\Tonictypes\ViewHelpers\AbstractViewHelper;

class KeyValuePairViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('key', 'string', 'Key to use for array creation', true);
        $this->registerArgument('value', 'mixed', 'Value to use for array creation', true);
        parent::initializeArguments();
    }

    /**
     * Creates a custom array with key/value pair
     * @return array
     */
    public function render(): array
    {
        return [$this->arguments['key'] => $this->arguments['value']];
    }
}
