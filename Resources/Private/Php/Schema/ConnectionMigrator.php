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

use TYPO3\CMS\Core\Database\Schema\ConnectionMigrator as CoreConnectionMigrator;
use TYPO3\CMS\Core\Database\Schema\SchemaDiff as Typo3SchemaDiff;

/**
 * TYPO3 13+ XCLASS: parent buildSchemaDiff() returns Core SchemaDiff.
 *
 * Loaded only on TYPO3 13/14 via ext_localconf require_once — kept outside Classes/
 * so PSR-4/DI never autoload it on TYPO3 12.
 *
 * Filters Tonictypes dynamic record tables out of Install Tool schema *suggestions*.
 *
 * Important: filtering must NOT run during SchemaMigrator::install()/publish, otherwise
 * CREATE TABLE for tx_tonictypes_domain_model_record_* is suppressed and tables never appear.
 *
 * - Analyze (renameUnused=true): hide create/alter for record tables; protect active drops;
 *   surface orphan drops.
 * - Install/publish (renameUnused=false): leave diff untouched so Tonictypes can create/alter.
 *
 * @internal
 */
class ConnectionMigrator extends CoreConnectionMigrator
{
    use TonictypesSchemaDiffFilterTrait;

    protected function buildSchemaDiff(bool $renameUnused = true): Typo3SchemaDiff
    {
        /** @var Typo3SchemaDiff $schemaDiff */
        $schemaDiff = $this->applyTonictypesAnalyzeFilters(
            parent::buildSchemaDiff($renameUnused),
            $renameUnused
        );

        return $schemaDiff;
    }
}
