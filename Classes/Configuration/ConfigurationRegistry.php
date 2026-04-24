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

namespace K3n\Tonictypes\Configuration;

use Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class ConfigurationRegistry
{
    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

    /**
     * @param ConnectionPool $connectionPool
     */
    public function injectConnectionPool(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    /**
     * Sorts all datatype tables on top of mod list
     *
     * @return void
     */
    public function registerTableOrderings()
    {
        try {
            // We need to ignore exceptions here in case the table does not exist
            /* @var Connection $query */
            $query = $this->connectionPool
                ->getConnectionForTable('tx_tonictypes_domain_model_datatype');

            $datatypes = $query->select(['uid','tablename'], 'tx_tonictypes_domain_model_datatype')->fetchAllAssociative();

            foreach ($datatypes as $_datatype) {
                if ($_datatype['tablename'] != '') {
                   ExtensionManagementUtility::addPageTSConfig("mod.web_list.tableDisplayOrder.{$_datatype['tablename']}.before = pages, fe_groups, fe_users, tx_tonictypes_domain_model_datatype");
                }
            }

        } catch(Exception $e) {
            // No exception printing here
        }
    }
}