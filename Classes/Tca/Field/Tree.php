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

class Tree extends Select
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
                'type' => 'select',
                'renderType' => 'selectTree',
                'treeConfig' => [
                    'appearance' => [
                        'expandAll' => $this->getField()->getConfig('expandAll'),
                        'showHeader' => $this->getField()->getConfig('showHeader'),
                    ],
                ],
            ],
        ];

        return $this->mergeConfigurationToTca($tca);
    }

    /**
     * Merges the field configuration to the tca
     *
     * @param array $tca
     * @return array
     */
    public function mergeConfigurationToTca(array $tca): array
    {
        $tca = parent::mergeConfigurationToTca($tca);

        if ((bool)$this->getField()->getConfig('include_field_values_as_options') == true) {
            $tca['config']['items'] = $this->getItems();
        }

        if ($foreignTable = $this->getField()->getConfig('foreign_table')) {
            $tca['config']['foreign_table'] = $foreignTable;
            switch ($foreignTable) {
                case 'sys_category':
                    $tca['config']['treeConfig']['parentField'] = 'parent';
                    break;
                case 'pages':
                    $tca['config']['treeConfig']['parentField'] = 'pid';
                    break;
            }
        }


        return $tca;
    }
}
