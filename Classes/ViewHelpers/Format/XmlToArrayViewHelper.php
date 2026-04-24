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

namespace K3n\Tonictypes\ViewHelpers\Format;

use K3n\Tonictypes\Utility\ArrayUtility;
use K3n\Tonictypes\ViewHelpers\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class XmlToArrayViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('xml', 'string', 'XML Input', true);
        parent::initializeArguments();
    }

    /**
     * Creates an code from a string
     *
     * @return string
     */
    public function render(): array
    {
        if($this->arguments['xml'] == '') {
            return [];
        }

        return GeneralUtility::xml2array($this->arguments['xml']);
    }
}