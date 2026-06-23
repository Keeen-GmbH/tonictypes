<?php
declare(strict_types=1);

namespace K3n\Tonictypes\Upgrades;

use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('TonictypesCTypeMigration')]
final class TonictypesCTypeMigration implements UpgradeWizardInterface
{
    public function getTitle(): string
    {
        return 'Migrate "Tonictypes" plugins to content elements.';
    }

    public function getDescription(): string
    {
        return 'The "Tonictypes" plugins are now registered as content elements. This update migrates existing records.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return $this->getMigrationRecords() !== [];
    }

    public function executeUpdate(): bool
    {
        foreach ($this->getMigrationRecords() as $record) {
            $this->updateRow($record);
        }
        return true;
    }

    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'tonictypes_list' => 'tonictypes_list',
            'tonictypes_detail' => 'tonictypes_detail',
            'tonictypes_dynamic' => 'tonictypes_dynamic',
            'tonictypes_plain' => 'tonictypes_plain',
        ];
    }

    protected function getPluginsWithFlexForm(): array
    {
        return [
            'tonictypes_list',
            'tonictypes_detail',
            'tonictypes_dynamic',
            'tonictypes_plain',
        ];
    }

    protected function hasListTypeColumn(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');
        $columns = $connection->createSchemaManager()->listTableColumns('tt_content');
        return isset($columns['list_type']);
    }

    protected function getMigrationRecords(): array
    {
        if (!$this->hasListTypeColumn()) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid', 'list_type', 'CType')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter('list')
                ),
                $queryBuilder->expr()->in(
                    'list_type',
                    $queryBuilder->createNamedParameter(
                        array_keys($this->getListTypeToCTypeMapping()),
                        ArrayParameterType::STRING
                    )
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    protected function updateRow(array $row): void
    {
        $listType = (string)($row['list_type'] ?? '');
        $newCType = $this->getListTypeToCTypeMapping()[$listType] ?? $listType;

        $updateData = [
            'CType' => $newCType,
        ];

        if (!in_array($listType, $this->getPluginsWithFlexForm(), true)) {
            $updateData['pi_flexform'] = '';
        }

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                $updateData,
                ['uid' => (int)$row['uid']]
            );
    }
}

