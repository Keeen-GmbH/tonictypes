<?php
declare(strict_types=1);
/*
 * This file is part of the package k3n/tonictypes.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * Many thanks to Auth: B. Zagar / Maint: J. Pietschmann for sharing this extension - TYPO3 inspiring people to share!
 * Contact: support@tonictypes.com
 *
 */

namespace K3n\Tonictypes\EventListener;

use Throwable;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\ModifyLoadedPageTsConfigEvent;

final readonly class ModifyLoadedPageTsConfigEventListener
{
    public function __construct(
        private ConnectionPool $connectionPool
    ) {
    }

    public function __invoke(ModifyLoadedPageTsConfigEvent $event): void
    {
        try {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_tonictypes_domain_model_datatype');
            $rows = $queryBuilder
                ->select('tablename')
                ->from('tx_tonictypes_domain_model_datatype')
                ->executeQuery()
                ->fetchFirstColumn();
        } catch (Throwable) {
            // Keep backend boot stable if schema is not ready yet.
            return;
        }

        $lines = [];
        foreach ($rows as $tableName) {
            $tableName = trim((string)$tableName);
            if ($tableName === '') {
                continue;
            }
            $lines[] = 'mod.web_list.tableDisplayOrder.' . $tableName . '.before = pages, fe_groups, fe_users, tx_tonictypes_domain_model_datatype';
        }

        if ($lines === []) {
            return;
        }

        $event->addTsConfig(implode(LF, $lines) . LF);
    }
}
