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

use K3n\Tonictypes\Service\FlexForm\FlexFormService;
use K3n\Tonictypes\ViewHelpers\AbstractViewHelper;

class FlexFormToArrayViewHelper extends AbstractViewHelper
{
    /**
     * @var FlexFormService
     */
    protected $flexFormService;

    /**
     * @param FlexFormService $flexFormService
     */
    public function injectFlexFormService(FlexFormService $flexFormService)
    {
        $this->flexFormService = $flexFormService;
    }

    /**
     * Initialize arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('flex', 'string', 'FlexForm Input', true);
        $this->registerArgument('languagePointer', 'string', 'Language Pointer', false, 'lDEF');
        $this->registerArgument('valuePointer', 'string', 'Value Pointer', false, 'vDEF');
        parent::initializeArguments();
    }

    /**
     * Creates code from a string
     *
     * @return array
     */
    public function render(): array
    {
        if($this->arguments['flex'] == '') {
            return [];
        }

        return $this->flexFormService->convertFlexFormContentToArray($this->arguments['flex'], $this->arguments['languagePointer'], $this->arguments['valuePointer']);
    }
}