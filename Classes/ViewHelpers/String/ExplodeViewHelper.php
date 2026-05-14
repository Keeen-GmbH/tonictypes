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

use K3n\Tonictypes\ViewHelpers\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class ExplodeViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('string', 'string', 'String to explode', true);
        $this->registerArgument('delimeter', 'string', 'Delimeter', false, ',');
        $this->registerArgument('removeEmptyValues', 'bool', 'Remove empty values', false, true);
        $this->registerArgument('limit', 'int', 'Limit', false, 0);
        parent::initializeArguments();
    }

    /**
     * Explodes an string
     *
     * @return array
     */
    public function render(): array
    {
        return GeneralUtility::trimExplode($this->arguments['delimeter'], $this->arguments['string'], $this->arguments['removeEmptyValues'], $this->arguments['limit']);
    }
}