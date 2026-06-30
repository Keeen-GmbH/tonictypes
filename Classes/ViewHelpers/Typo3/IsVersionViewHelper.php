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

namespace K3n\Tonictypes\ViewHelpers\Typo3;

use K3n\Tonictypes\ViewHelpers\AbstractViewHelper;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IsVersionViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('major', 'int', 'Major TYPO3 version to check', true);
        parent::initializeArguments();
    }

    public function render(): bool
    {
        return GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() === (int)$this->arguments['major'];
    }
}
