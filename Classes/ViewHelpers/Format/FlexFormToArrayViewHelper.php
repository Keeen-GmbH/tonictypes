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

use K3n\Tonictypes\DataProcessing\FlexFormProcessor;
use K3n\Tonictypes\ViewHelpers\AbstractViewHelper;

/**
 * Converts FlexForm XML into a simple array for Fluid templates.
 */
class FlexFormToArrayViewHelper extends AbstractViewHelper
{
    protected FlexFormProcessor $flexFormProcessor;

    public function injectFlexFormProcessor(FlexFormProcessor $flexFormProcessor): void
    {
        $this->flexFormProcessor = $flexFormProcessor;
    }

    public function initializeArguments(): void
    {
        $this->registerArgument('flex', 'string', 'FlexForm XML string', true);
        $this->registerArgument('languagePointer', 'string', 'Language pointer', false, 'lDEF');
        $this->registerArgument('valuePointer', 'string', 'Value pointer', false, 'vDEF');
        parent::initializeArguments();
    }

    /**
     * @return array<string, mixed>
     */
    public function render(): array
    {
        return $this->flexFormProcessor->convert((string)($this->arguments['flex'] ?? ''));
    }
}
