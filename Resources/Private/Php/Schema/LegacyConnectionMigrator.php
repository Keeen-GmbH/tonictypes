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

namespace K3n\Tonictypes\Database\Schema;

use Doctrine\DBAL\Schema\SchemaDiff;
use TYPO3\CMS\Core\Database\Schema\ConnectionMigrator as CoreConnectionMigrator;

/**
 * TYPO3 12 XCLASS: parent buildSchemaDiff() returns Doctrine SchemaDiff.
 *
 * Loaded only on TYPO3 12 via ext_localconf require_once — kept outside Classes/
 * so PSR-4/DI never autoload it on TYPO3 13/14.
 *
 * @internal
 */
class LegacyConnectionMigrator extends CoreConnectionMigrator
{
    use TonictypesSchemaDiffFilterTrait;

    protected function buildSchemaDiff(bool $renameUnused = true): SchemaDiff
    {
        /** @var SchemaDiff $schemaDiff */
        $schemaDiff = $this->applyTonictypesAnalyzeFilters(
            parent::buildSchemaDiff($renameUnused),
            $renameUnused
        );

        return $schemaDiff;
    }
}
