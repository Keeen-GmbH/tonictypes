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

namespace K3n\Tonictypes\Factory;

use K3n\Tonictypes\Domain\Model\Datatype;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

class ClassFactory implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Domain Mode/Repository Template File Paths
     *
     * @var string
     */
    public const DOMAIN_MODEL_TEMPLATE_FILE = 'EXT:tonictypes/Resources/Private/Init/Record.php.phtml';
    public const DOMAIN_REPOSITORY_TEMPLATE_FILE = 'EXT:tonictypes/Resources/Private/Init/RecordRepository.php.phtml';

    /**
     * Reflection Service
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @param ReflectionService $reflectionService
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Creates a class file by a given datatype
     *
     * @param string $classTemplateFile
     * @param Datatype $datatype
     * @return string
     */
    public function getClassFileContents(string $classTemplateFile, Datatype $datatype): string
    {
        /* @var \K3n\Tonictypes\Fluid\View\StandaloneView $view */
        $standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
        $templateFile = GeneralUtility::getFileAbsFileName($classTemplateFile);
        $standaloneView->setTemplatePathAndFilename($templateFile);

        $className = $datatype->getClassName();

        $variables = [
            'className' => $className,
            'classValid' => $this->classValid($datatype),
            'datatype'  => $datatype,
            'fileName' => $classTemplateFile,
        ];

        return $standaloneView->renderSection('class', $variables);
    }

    /**
     * Creates a class file by a given datatype
     *
     * @param Datatype $datatype
     * @return bool
     */
    public function buildDomainModelClassFile(Datatype $datatype): bool
    {
        $classFileName = GeneralUtility::getFileAbsFileName($datatype->getClassFilePath());
        $contents = $this->getClassFileContents(self::DOMAIN_MODEL_TEMPLATE_FILE, $datatype);
        return $this->createFile($classFileName, $contents);
    }

    /**
     * Creates a class file by a given datatype
     *
     * @param \K3n\Tonictypes\Domain\Model\Datatype $datatype
     * @return bool
     */
    public function buildDomainRepositoryClassFile(Datatype $datatype): bool
    {
        $classFileName = GeneralUtility::getFileAbsFileName($datatype->getRepositoryFilePath());
        $contents = $this->getClassFileContents(self::DOMAIN_REPOSITORY_TEMPLATE_FILE, $datatype);
        return $this->createFile($classFileName, $contents);
    }

    /**
     * Creates a file with given contents
     *
     * @param string $filename
     * @param string $contents
     * @return bool
     */
    public function createFile(string $filename, string $contents): bool
    {
        try {
            $path = dirname($filename);
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $result = GeneralUtility::writeFile($filename, $contents);
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage(), ['exception' => $e]);

            return false;
        }

        return $result;
    }

    /**
     * Dump autoload information
     *
     * @return void
     */
    public function dumpAutoload(): void
    {
        ClassLoadingInformation::dumpClassLoadingInformation();
    }

    /**
     * Checks if a class is valid
     *
     * @param Datatype $datatype
     * @return bool
     */
    public function classValid(Datatype $datatype): bool
    {
        $domainModelClassName = $datatype->getFullyQualifiedClassName();
        $domainRepositoryClassName = $datatype->getFullyQualifiedRepositoryClassName();
        $domainModelFilePath = GeneralUtility::getFileAbsFileName($datatype->getClassFilePath());
        $domainRepositoryFilePath = GeneralUtility::getFileAbsFileName($datatype->getRepositoryFilePath());

        // Check if class is loaded
        if (!class_exists($domainModelClassName) || !class_exists($domainRepositoryClassName)) {
            return false;
        }

        // Check if class file exists
        if (!file_exists($domainModelFilePath) || !file_exists($domainRepositoryFilePath)) {
            return false;
        }

        // Check if php class can be loaded
        try {
            require_once($domainModelFilePath);
            require_once($domainRepositoryFilePath);
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage(), ['exception' => $e]);

            return false;
        }

        return true;
    }

    /**
     * Checks if a class is actual
     *
     * @param Datatype $datatype
     */
    public function classActual(Datatype $datatype): bool
    {
        // Checks both domain model and domain repository to be valid and actual
        $domainModelClassFilePath = GeneralUtility::getFileAbsFileName($datatype->getClassFilePath());
        $domainRepositoryClassFilePath = GeneralUtility::getFileAbsFileName($datatype->getRepositoryFilePath());

        // Check Domain Model
        try {
            $oldDomainModelClassContents = @file_get_contents($domainModelClassFilePath);
            $newDomainModelClassContents = $this->getClassFileContents(self::DOMAIN_MODEL_TEMPLATE_FILE, $datatype);

            if (!$oldDomainModelClassContents) {
                return false;
            }

            if (md5($oldDomainModelClassContents) != md5($newDomainModelClassContents)) {
                return false;
            }

        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage(), ['exception' => $e]);

            return false;
        }

        // Check Domain Repository
        try {
            $oldDomainRepositoryClassContents = @file_get_contents($domainRepositoryClassFilePath);
            $newDomainRepositoryClassContents = $this->getClassFileContents(self::DOMAIN_REPOSITORY_TEMPLATE_FILE, $datatype);

            if (md5($oldDomainRepositoryClassContents) != md5($newDomainRepositoryClassContents)) {
                return false;
            }

        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage(), ['exception' => $e]);

            return false;
        }

        // All checks were valid
        return true;
    }

    /**
     * Remove generated model/repository class files that no longer map to an active datatype table.
     * Typical after renaming tablename from …_job to …_job2 and republishing.
     *
     * @param list<string> $activeTablenames
     * @return list<string> Removed absolute file paths
     */
    public function cleanupOrphanGeneratedClassFiles(array $activeTablenames): array
    {
        $keep = [];
        foreach ($activeTablenames as $tablename) {
            $tablename = trim((string)$tablename);
            if (!str_starts_with($tablename, 'tx_tonictypes_domain_model_record_')) {
                continue;
            }
            foreach ($this->resolveGeneratedClassPathsForTablename($tablename) as $path) {
                $normalized = $this->normalizePath($path);
                if ($normalized !== '') {
                    $keep[$normalized] = true;
                }
            }
        }

        $removed = [];
        foreach ([
            'EXT:tonictypes/Classes/Domain/Model/Record',
            'EXT:tonictypes/Classes/Domain/Repository/Record',
        ] as $relativeDir) {
            $absDir = GeneralUtility::getFileAbsFileName($relativeDir);
            if (!is_string($absDir) || $absDir === '' || !is_dir($absDir)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absDir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo instanceof \SplFileInfo || !$fileInfo->isFile()) {
                    continue;
                }
                if (strtolower($fileInfo->getExtension()) !== 'php') {
                    continue;
                }
                $path = $fileInfo->getPathname();
                $normalized = $this->normalizePath($path);
                if ($normalized !== '' && isset($keep[$normalized])) {
                    continue;
                }
                if (@unlink($path)) {
                    $removed[] = $path;
                }
            }
        }

        return $removed;
    }

    /**
     * @return list<string> Absolute paths that belong to the given record tablename
     */
    public function resolveGeneratedClassPathsForTablename(string $tablename): array
    {
        if ($tablename === '' || !str_starts_with($tablename, 'tx_tonictypes_domain_model_record_')) {
            return [];
        }

        $partName = substr($tablename, strlen('tx_tonictypes_domain_model_record_'));
        $parts = array_map('ucfirst', GeneralUtility::trimExplode('_', $partName, true));
        if ($parts === []) {
            return [];
        }

        $className = (string)end($parts);
        $dirParts = $parts;
        array_pop($dirParts);
        $subPath = $dirParts !== [] ? implode('/', $dirParts) . '/' : '';

        $paths = [];
        foreach ([
            'EXT:tonictypes/Classes/Domain/Model/Record/' . $subPath . $className . '.php',
            'EXT:tonictypes/Classes/Domain/Repository/Record/' . $subPath . $className . 'Repository.php',
        ] as $relative) {
            $abs = GeneralUtility::getFileAbsFileName($relative);
            if (is_string($abs) && $abs !== '') {
                $paths[] = $abs;
            }
        }

        return $paths;
    }

    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return '';
        }
        $real = realpath($path);
        return is_string($real) && $real !== '' ? $real : $path;
    }

}
