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

namespace K3n\Tonictypes\Tca\Field;

use K3n\Tonictypes\Tca;

class Editor extends Textarea implements Tca\FieldInterface
{
    /**
     * Gets built tca array
     *
     * @return array
     */
    public function getTca(): array
    {
        $tca = [
            'exclude' => (int)$this->getField()->isExclude(),
            'label' => $this->getField()->getFrontendLabel(),
            'config' => [
                'type' => 'text',
                'renderType' => 't3editor', // TYPO3 13: codeEditor
                'enableRichtext' => true,
                'format' => $this->getField()->getConfig('format')?:'mixed',
                'rows' => (int)$this->getField()->getConfig('rows')?:10,
            ],
        ];

        return $this->mergeConfigurationToTca($tca);
    }
}
