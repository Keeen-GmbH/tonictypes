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

use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;
use Doctrine\DBAL\Driver\Mysqli\Exception\StatementError;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

class FileViewHelper extends AbstractRenderViewHelper
{
    /**
     * @var FileRepository
     */
    protected $fileRepository;

    /**
     * @param FileRepository $fileRepository
     */
    public function injectFileRepository(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

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

    /**
     * @return string
     * @throws StatementError
     * @throws DoctrineDBALDriverException
     */
    public function render(): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');

        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        /* @var \Doctrine\DBAL\Driver\Mysqli\MysqliStatement $res */
        $res = $queryBuilder
            ->select('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($this->arguments['uid'], Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter($this->arguments['table'], Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter($this->arguments['field'], Connection::PARAM_STR)
                )
            )
            ->orderBy('sorting_foreign')
            ->executeQuery();

        $result = $res->fetchAllAssociative();
        if(isset($result['uid'])) {
            $fileReference = $this->fileRepository->findFileReferenceByUid($result['uid']);

            $string = '';
            if($fileReference instanceof FileReference) {
                $string .= $this->iconFactory->getIconForFileExtension($fileReference->getExtension(), GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() < 13 ? Icon::SIZE_SMALL : IconSize::SMALL);
                $string .= ' ';
                $string .= $fileReference->getPublicUrl();
            } else {
                $string .= 'File not found!';
            }

            return $string;
        }

        return '';
    }
}
