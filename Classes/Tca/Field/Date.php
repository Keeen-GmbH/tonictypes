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

class Date extends Input
{
    /**
     * Gets the sql create statement
     *
     * @return string
     */
    public function getSqlCreateStatement(): string
    {
        if ($this->getField()->getDatabaseType() != '') {
            return $this->getField()->getDatabaseType();
        }
        return "int(11) unsigned DEFAULT '0' NOT NULL";
    }

    /**
     * @return string
     */
    public function getVariableType(): string
    {
        return $this->getField()->getFrontendType();
    }

    /**
     * Gets built tca array
     *
     * @return array
     */
    public function getTca(): array
    {
        $tca = parent::getTca();
        $eval = $this->getField()->getConfig('eval');
        if ($eval !== '') {
            $tca['config']['eval'] = $eval;
        }
        $tca['config']['type'] = 'datetime';
        $tca['config']['format'] = 'date';
        return $this->mergeConfigurationToTca($tca);
    }

    /**
     * @return int
     */
    public function getDefaultValue()
    {
        $value = parent::getDefaultValue();

        if (is_array($value)) {
            return (int)$value[0];
        }

        return (int)$value;
    }

}
