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

namespace K3n\Tonictypes\ViewHelpers\Template;

use K3n\Tonictypes\Configuration\ExtensionConfiguration;
use K3n\Tonictypes\Domain\Repository\VariableRepository;
use K3n\Tonictypes\Factory\VariableFactory;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use K3n\Tonictypes\ViewHelpers\AbstractViewHelper;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use K3n\Tonictypes\Domain\Model\Variable;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException;

class RenderViewHelper extends AbstractViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var VariableRepository
     */
    protected $variableRepository;

    /**
     * @var VariableFactory
     */
    protected $variableFactory;

    /**
     * Cache Manager
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @param VariableRepository $variableRepository
     */
    public function injectVariableRepository(VariableRepository $variableRepository)
    {
        $this->variableRepository = $variableRepository;
    }

    /**
     * @param VariableFactory $variableFactory
     */
    public function injectVariableFactory(VariableFactory $variableFactory)
    {
        $this->variableFactory = $variableFactory;
    }

    /**
     * @param CacheManager $cacheManager
     */
    public function injectCacheManager(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Initialize arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('arguments', 'array', 'The arguments for the template', false, []);
        $this->registerArgument('template', 'string', 'The template file that has to be used', true);
        $this->registerArgument('variables', 'array', 'Ids of additional template variables to inject', false, []);
        $this->registerArgument('cache', 'bool', 'Enables or disables cache', false, false);
        $this->registerArgument('lifetime', 'int', 'Cache Lifetime', false);
        $this->registerArgument('cacheIdentifier', 'string', 'Cache Identifier', false);
        $this->registerArgument('pid', 'int', 'Page ID', false);
        parent::initializeArguments();
    }

    /**
     * Get cache identifier
     * @return string
     */
    protected function _getGeneratedCacheIdentifier(array $additionalParameters = []): string
    {
        /* @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        $languageAspect = $context->getAspect('language');

        $data = [
            $languageAspect->getId()
        ];
        $data = array_merge($this->arguments['arguments'], $additionalParameters);
        $data = array_merge($data, $additionalParameters);
        return md5(json_encode($data));
    }

    /**
     * Renders a template
     * @return string|null
     * @throws NoSuchCacheException
     * @throws AspectNotFoundException
     * @throws InvalidConfigurationTypeException
     * @throws InvalidArgumentNameException
     */
    public function render(): ?string
    {
        $template   = $this->arguments['template'];
        $pid = $this->arguments['pid'] ?? 0;
        $predefined = $this->pluginSettingsService->getPredefinedTemplateById($template);

        if (is_string($predefined) && trim($predefined) !== '') {
            $template = trim($predefined);
        }

        $cache = $this->cacheManager->getCache('core');
        if ($this->hasArgument('cacheIdentifier')) {
            $cacheIdentifier = $this->arguments['cacheIdentifier'];
        } else {
            $cacheIdentifier = $this->_getGeneratedCacheIdentifier([$template]);
        }

        if (($this->arguments['cache'] == true || $this->arguments['cacheIdentifier']) && $cache->has($cacheIdentifier)) {
            // We try to load the output from the cache
            return $cache->get($cacheIdentifier);
        }

        $templateFile = GeneralUtility::getFileAbsFileName($template);

        if (file_exists($templateFile)) {
            /* @var StandaloneView $standaloneView */
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename($templateFile);
            $view->assignMultiple($this->arguments['arguments']);
            $request = null;
            if (method_exists($this->renderingContext, 'getAttribute')) {
                $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
            }
            if (!$request instanceof ServerRequestInterface && isset($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
                $request = $GLOBALS['TYPO3_REQUEST'];
            }
            if ($request instanceof ServerRequestInterface) {
                $view->setRequest($request);
            }
            $view->assign('cacheIdentifier', $cacheIdentifier);
            if (!empty($this->arguments['variables'])) {
                $variables = $this->variableRepository->findByUids($this->arguments['variables']);
                if (count($variables)) {
                    foreach ($variables as $_variable) {
                        /** @var Variable $_variable */
                        $view->assign($_variable->getVariableName(), $this->variableFactory->prepareVariableValue($_variable));
                    }
                }
            }

            $output = $view->render();

            $lifetime = (int)$this->pluginSettingsService->getConfiguration('plugin.tx_tonictypes.developer.cache_lifetime', $pid);
            if ($this->hasArgument('lifetime')) {
                $lifetime = (int)$this->arguments['lifetime'];
            }

            $cache->set($cacheIdentifier, $output, [], $lifetime);

            return $output;
        }

        return "Template '{$template}' not found!";
    }
}
