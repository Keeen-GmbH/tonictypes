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

namespace K3n\Tonictypes\Tca;

interface FieldInterface
{
    /**
     * Gets the sql create statement
     *
     * @return string
     */
    public function getSqlCreateStatement(): string;

    /**
     * Gets the variable type of the field
     * for creating the frontend class
     *
     * e.g. bool, int, array
     *
     * @return string
     */
    public function getVariableType(): string;

    /**
     * Gets the TCA for the field
     *
     * @return array
     */
    public function getTca(): array;
}
