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

namespace K3n\Tonictypes\Service\Settings;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Resolves Tonictypes TypoScript for a storage page (classic TS and/or site sets, v12–v14).
 */
abstract class AbstractSettingsService implements SingletonInterface
{
  /**
   * @var BackendConfigurationManager
   */
  protected $backendConfigurationManager;

  /**
   * @var ConfigurationManager
   */
  protected $configurationManager;

  public function injectBackendConfigurationManager(BackendConfigurationManager $backendConfigurationManager): void
  {
    $this->backendConfigurationManager = $backendConfigurationManager;
  }

  public function injectConfigurationManager(ConfigurationManager $configurationManager): void
  {
    $this->configurationManager = $configurationManager;
  }

  /**
   * @param string $path Dot-notation path without trailing dots
   * @return array|mixed
   */
  public function getConfiguration(string $path, int $pid = 0)
  {
    $request = $this->getCurrentRequest();
    $config = $this->isFrontendRequest($request)
      ? $this->loadFrontendTypoScriptSetup()
      : $this->loadBackendTypoScriptSetup($this->buildBackendTypoScriptRequest($request, $pid));

    if (!is_array($config) || $config === []) {
      return [];
    }

    try {
      return ArrayUtility::getValueByPath(GeneralUtility::removeDotsFromTS($config), $path, '.');
    } catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Pin TypoScript evaluation to the storage page's site (sys_template rootline and/or site sets).
   */
  protected function buildBackendTypoScriptRequest(ServerRequestInterface $request, int $pid): ServerRequestInterface
  {
    $pageId = $this->resolvePageId($request, $pid);
    $site = $this->resolveSite($request, $pageId);

    if ($pageId <= 0 && $site instanceof SiteInterface && !($site instanceof NullSite)) {
      $pageId = $site->getRootPageId();
    }

    $applicationType = $request->getAttribute('applicationType');
    if (!is_int($applicationType)) {
      $applicationType = SystemEnvironmentBuilder::REQUESTTYPE_BE;
    }

    return (new ServerRequest())
      ->withQueryParams(['id' => $pageId])
      ->withAttribute('site', $site)
      ->withAttribute('applicationType', $applicationType);
  }

  /**
   * @return array<string, mixed>
   */
  protected function loadBackendTypoScriptSetup(ServerRequestInterface $typoScriptRequest): array
  {
    if (!$this->backendConfigurationManager instanceof BackendConfigurationManager) {
      return [];
    }

    $this->resetBackendTypoScriptPageIdCache((int)($typoScriptRequest->getQueryParams()['id'] ?? 0));

    try {
      // v13+: getTypoScriptSetup(ServerRequestInterface)
      if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() >= 13) {
        $setup = $this->backendConfigurationManager->getTypoScriptSetup($typoScriptRequest);
        return is_array($setup) ? $setup : [];
      }

      // v12: setRequest() + getTypoScriptSetup()
      $previousRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;
      $GLOBALS['TYPO3_REQUEST'] = $typoScriptRequest;
      try {
        $this->backendConfigurationManager->setRequest($typoScriptRequest);
        $setup = $this->backendConfigurationManager->getTypoScriptSetup();
        return is_array($setup) ? $setup : [];
      } finally {
        if ($previousRequest instanceof ServerRequestInterface) {
          $GLOBALS['TYPO3_REQUEST'] = $previousRequest;
        } else {
          unset($GLOBALS['TYPO3_REQUEST']);
        }
      }
    } catch (\Throwable $e) {
      return [];
    }
  }

  /**
   * @return array<string, mixed>
   */
  protected function loadFrontendTypoScriptSetup(): array
  {
    if (!$this->configurationManager instanceof ConfigurationManagerInterface) {
      return [];
    }
    try {
      $setup = $this->configurationManager->getConfiguration(
        ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
      );
      return is_array($setup) ? $setup : [];
    } catch (\Throwable $e) {
      return [];
    }
  }

  protected function getCurrentRequest(): ServerRequestInterface
  {
    $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
    return $request instanceof ServerRequestInterface
      ? $request
      : GeneralUtility::makeInstance(ServerRequest::class);
  }

  protected function isFrontendRequest(ServerRequestInterface $request): bool
  {
    try {
      return ApplicationType::fromRequest($request)->isFrontend();
    } catch (\Throwable $e) {
      return false;
    }
  }

  protected function resolvePageId(ServerRequestInterface $request, int $pid): int
  {
    if ($pid > 0) {
      return $pid;
    }

    foreach ([$request->getQueryParams()['id'] ?? null, $request->getParsedBody()['id'] ?? null] as $id) {
      if (is_array($id)) {
        $id = reset($id);
      }
      if (MathUtility::canBeInterpretedAsInteger($id) && (int)$id > 0) {
        return (int)$id;
      }
    }

    return 0;
  }

  protected function resolveSite(ServerRequestInterface $request, int $pageId): SiteInterface
  {
    if ($pageId > 0) {
      try {
        return GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
      } catch (SiteNotFoundException) {
      }
    }

    $existingSite = $request->getAttribute('site');
    if ($existingSite instanceof SiteInterface && !($existingSite instanceof NullSite)) {
      return $existingSite;
    }

    try {
      $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
      if (count($sites) === 1) {
        return reset($sites);
      }
    } catch (\Throwable $e) {
    }

    return new NullSite();
  }

  /**
   * Clear Extbase's per-request page-id cache when evaluating a different page.
   */
  protected function resetBackendTypoScriptPageIdCache(int $pageId): void
  {
    try {
      $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
      $cachedPageId = $runtimeCache->get('extbase-backend-typoscript-currentPageId');
      if (is_int($cachedPageId) && $cachedPageId !== $pageId) {
        $runtimeCache->remove('extbase-backend-typoscript-currentPageId');
      }
    } catch (\Throwable $e) {
    }
  }

  public function getPartialPaths(int $pid = 0): array
  {
    $config = $this->getConfiguration('plugin.tx_tonictypes.view.partialRootPaths', $pid);
    return is_array($config) && $config !== []
      ? $config
      : [100 => 'EXT:tonictypes/Resources/Private/Partials/'];
  }

  public function getTemplatePaths(int $pid = 0): array
  {
    $config = $this->getConfiguration('plugin.tx_tonictypes.view.templateRootPaths', $pid);
    return is_array($config) && $config !== []
      ? $config
      : [100 => 'EXT:tonictypes/Resources/Private/Templates/'];
  }

  public function getLayoutPaths(int $pid = 0): array
  {
    $config = $this->getConfiguration('plugin.tx_tonictypes.view.layoutRootPaths', $pid);
    return is_array($config) && $config !== []
      ? $config
      : [100 => 'EXT:tonictypes/Resources/Private/Layouts/'];
  }
}
