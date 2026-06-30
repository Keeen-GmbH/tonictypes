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

namespace K3n\Tonictypes\ViewHelpers\Backend\Render;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

class FileViewHelper extends AbstractRenderViewHelper
{
    /**
     * Arguments initialization
     * @throws Exception
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('uid', 'int', 'Uid of the file', true);
        $this->registerArgument('table', 'string', 'Name of the table', true);
        $this->registerArgument('field', 'string', 'Name of the relation field', true);
    }

    public function render(): string
    {
        // The field is a FlexForm field (not a real TCA column on the table), so the file
        // references are resolved directly from sys_file_reference instead of FileRepository
        // relation lookups, which validate the field against the table's TCA schema.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        $referenceUid = $queryBuilder
            ->select('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter((int)$this->arguments['uid'], Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter((string)$this->arguments['table'], Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter((string)$this->arguments['field'], Connection::PARAM_STR)
                )
            )
            ->orderBy('sorting_foreign')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        if ($referenceUid === false) {
            return '';
        }

        $fileReference = GeneralUtility::makeInstance(ResourceFactory::class)
            ->getFileReferenceObject((int)$referenceUid);


        if (!$fileReference instanceof FileReference) {
            return '';
        }

        $iconSize = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() < 13
            ? Icon::SIZE_SMALL
            : IconSize::SMALL;

        return $this->iconFactory->getIconForFileExtension($fileReference->getExtension(), $iconSize)
            . ' '
            . $fileReference->getPublicUrl();
    }
}
