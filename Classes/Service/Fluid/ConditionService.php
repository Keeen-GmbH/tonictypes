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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConditionService implements SingletonInterface
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
        }

        return $this->view;
    }

    /**
     * Validates a fluid condition against variables
     * @param string $condition
     * @param array $variables
     * @return bool
     */
    public function isValid(string $condition, array $variables = []): bool
    {
        if($condition == '') {
            return true;
        }

        $conditionText = "<f:if condition=\"{$condition}\">1</f:if>";
        $this->getView()->assignMultiple($variables);
        return (bool)$this->getView()->renderSource($conditionText);
    }
}