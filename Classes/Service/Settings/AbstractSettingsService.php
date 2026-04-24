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
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;

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
  //public function injectConfigurationManager(ConfigurationManager $configurationManager)
  //{
  //  $this->configurationManager = $configurationManager;
  //}

  /**
   * Returns all settings.
   *
   * @param string $path Configuration Path
   * @return array
   */
  public function getConfiguration(string $path)
  {
    $request = $GLOBALS['TYPO3_REQUEST'] ?? GeneralUtility::makeInstance(ServerRequest::class);
    if (!$request instanceof ServerRequestInterface) {
      $request = GeneralUtility::makeInstance(ServerRequest::class);
    }

    $config = $this->backendConfigurationManager->getTypoScriptSetup($request);
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
  public function getPartialPaths(): array
  {
    $path = 'plugin.tx_tonictypes.view.partialRootPaths';
    $config = $this->getConfiguration($path);

    if (!is_array($config)) {
      $config = [100 => 'EXT:tonictypes/Resources/Private/Partials/'];
    }

    return $config;
  }

  /**
   * Gets the template path from configuration
   *
   * @return string
   */
  public function getTemplatePaths(): array
  {
    $path = 'plugin.tx_tonictypes.view.templateRootPaths';
    $config = $this->getConfiguration($path);

    if (!is_array($config)) {
      $config = [100 => 'EXT:tonictypes/Resources/Private/Templates/'];
    }

    return $config;
  }

  /**
   * Gets the layout path from configuration
   *
   * @return array|string
   */
  public function getLayoutPaths(): array
  {
    $path = 'plugin.tx_tonictypes.view.layoutRootPaths';
    $config = $this->getConfiguration($path);

    if (!is_array($config)) {
      $config = [100 => 'EXT:tonictypes/Resources/Private/Layouts/'];
    }

    return $config;
  }
}
