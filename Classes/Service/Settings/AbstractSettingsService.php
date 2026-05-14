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
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

abstract class AbstractSettingsService implements SingletonInterface
{
  /**
   * @var array
   */
  protected $configuration = null;

  /**
   * @var BackendConfigurationManager
   */
  protected $backendConfigurationManager;

  /**
   * @var ConfigurationManager
   */
  protected $configurationManager;

  /**
   * @param BackendConfigurationManager $backendConfigurationManager
   * @return void
   */
  public function injectBackendConfigurationManager(BackendConfigurationManager $backendConfigurationManager): void
  {
    $this->backendConfigurationManager = $backendConfigurationManager;
  }

  /**
   * @param ConfigurationManager $configurationManager
   */
  public function injectConfigurationManager(ConfigurationManager $configurationManager)
  {
   $this->configurationManager = $configurationManager;
  }

  /**
   * Returns all settings.
   *
   * @param string $path Configuration Path
   * @return array
   */
  public function getConfiguration(string $path, int $pid = 0)
  {
    $config = [];
    $request = $GLOBALS['TYPO3_REQUEST'] ?? GeneralUtility::makeInstance(ServerRequest::class);
    if (!$request instanceof ServerRequestInterface) {
      $request = GeneralUtility::makeInstance(ServerRequest::class);
    }
    if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() >= 14) {
      $isFrontend = false;
      try {
        $isFrontend = ApplicationType::fromRequest($request)->isFrontend();
      } catch (\Throwable $e) {
        // Some backend flows provide a request without applicationType attribute.
        $isFrontend = !isset($GLOBALS['BE_USER']);
      }
      if ($isFrontend) {
        if ($this->configurationManager instanceof ConfigurationManagerInterface) {
          try {
            $config = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
          } catch (\Throwable $e) {
            $config = [];
          }
        }
      } elseif ($pid > 0) {
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pid);
        $request = (new ServerRequest())->withQueryParams(['id' => $pid])->withAttribute('site', $site);
        $config = $this->backendConfigurationManager->getTypoScriptSetup($request);
      } elseif ($this->configurationManager instanceof ConfigurationManagerInterface) {
          try {
            $config = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
          } catch (\Throwable $e) {
            $config = [];
          }
      }
    } else {
      $config = $this->backendConfigurationManager->getTypoScriptSetup($request);
    }
    if (!is_array($config)) {
      return [];
    }
    try {
      $config = GeneralUtility::removeDotsFromTS($config);
      $value = ArrayUtility::getValueByPath($config, $path, '.');
      return $value;
    } catch (\Exception $e) {
    }

    return [];
  }

  /**
   * Gets the template partial path from configuration
   *
   * @return string
   */
  public function getPartialPaths(int $pid = 0): array
  {
    $path = 'plugin.tx_tonictypes.view.partialRootPaths';
    $config = $this->getConfiguration($path, $pid);

    if (!is_array($config) || $config === []) {
      $config = [100 => 'EXT:tonictypes/Resources/Private/Partials/'];
    }

    return $config;
  }

  /**
   * Gets the template path from configuration
   *
   * @return string
   */
  public function getTemplatePaths(int $pid = 0): array
  {
    $path = 'plugin.tx_tonictypes.view.templateRootPaths';
    $config = $this->getConfiguration($path, $pid);

    if (!is_array($config) || $config === []) {
      $config = [100 => 'EXT:tonictypes/Resources/Private/Templates/'];
    }

    return $config;
  }

  /**
   * Gets the layout path from configuration
   *
   * @return array|string
   */
  public function getLayoutPaths(int $pid = 0): array
  {
    $path = 'plugin.tx_tonictypes.view.layoutRootPaths';
    $config = $this->getConfiguration($path, $pid);

    if (!is_array($config) || $config === []) {
      $config = [100 => 'EXT:tonictypes/Resources/Private/Layouts/'];
    }

    return $config;
  }
}
