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

namespace K3n\Tonictypes\UserFunc;

use K3n\Tonictypes\Domain\Repository\DatatypeRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use K3n\Tonictypes\Domain\Model\Datatype;

class Record
{
    /**
     * @var DatatypeRepository
     */
    protected $datatypeRepository;

    /**
     * @param DatatypeRepository $datatypeRepository
     */
    public function injectDatatypeRepository(DatatypeRepository $datatypeRepository)
    {
        $this->datatypeRepository = $datatypeRepository;
    }

    /**
     * Populate records on selected pages
     * @param array $config Configuration Array
     * @param mixed $parentObject Parent Object
     * @return array
     */
    public function populateRecordsByDatatypeSelection(array &$config, &$parentObject): void
    {
        $pages = $config["flexParentDatabaseRow"]["pages"];

        if (!is_array($pages)) {
            $pages = GeneralUtility::intExplode(",", $pages);
        }

        $datatypeUid = $config['row']['settings.datatype_selection'];

        if(is_array($datatypeUid)) {
            $datatypeUid = (int)reset($datatypeUid);
        }

        if($datatypeUid <= 0) {
            return;
        }

        /* @var Datatype $datatype */
        $datatype = $this->datatypeRepository->findByUid($datatypeUid, false);

        if($datatype instanceof Datatype) {
            $table = $datatype->getTablename();

            // Fetch all records of datatype on the selected pages
            /* @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);

            $result = $queryBuilder->select('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->in('pid', $pages)
                )
                ->andWhere('deleted = 0')
                ->andWhere('sys_language_uid = 0')
                ->executeQuery()
                ->fetchAllAssociative();

            $options = [];
            if(!empty($result)) {
                $options[] = ["[{$datatype->getUid()}] {$datatype->getName()}x", "--div--"];
                foreach($result as $_row) {
                    $label = $_row['title'];
                    $value = $_row['uid'];
                    $options[] = [
                        'label' => $label,
                        'value' => $value,
                        'icon' => 'extensions-tonictypes-'.$_row['icon']];
                }
            }

            $config["items"] = array_merge($config["items"], $options);
        }
    }
}
