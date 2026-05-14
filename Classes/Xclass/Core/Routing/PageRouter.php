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

namespace K3n\Tonictypes\Xclass\Core\Routing;

use K3n\Tonictypes\Routing\Enhancer\TonictypesEnhancer;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class PageRouter extends \TYPO3\CMS\Core\Routing\PageRouter
{
    /**
     * Fetch possible enhancers + aspects based on the current page configuration and the site configuration put
     * into "routeEnhancers"
     *
     * @param int $pageId
     * @param SiteLanguage $language
     * @return EnhancerInterface[]
     */
    protected function getEnhancersForPage(int $pageId, SiteLanguage $language, array $page = []): array
    {
        //return parent::getEnhancersForPage($pageId, $language);
        $enhancers = [];
        foreach ($this->site->getConfiguration()['routeEnhancers'] ?? [] as $enhancerConfiguration) {
            // Check if there is a restriction to page Ids.
            if (is_array($enhancerConfiguration['limitToPages'] ?? null) && !in_array($pageId, $enhancerConfiguration['limitToPages'])) {
                continue;
            }
            $enhancerType = $enhancerConfiguration['type'] ?? '';

            // Check if enhancer can be started at current page
            if ($enhancerType == 'Tonictypes') {
                if (array_key_exists('targetPages', $enhancerConfiguration) && !in_array($pageId, $enhancerConfiguration['targetPages'])) {
                    continue;
                }
            }

            $enhancer = $this->enhancerFactory->create($enhancerType, $enhancerConfiguration);

            // Custom part to process aspects for the correct url building
            if ($enhancer instanceof TonictypesEnhancer) {
                $enhancer->setPageId($pageId);
            }

            if (!empty($enhancerConfiguration['aspects'] ?? null)) {
                $aspects = $this->aspectFactory->createAspects(
                    $enhancerConfiguration['aspects'],
                    $language,
                    $this->site
                );
                $enhancer->setAspects($aspects);
            }
            $enhancers[] = $enhancer;
        }

        return $enhancers;
    }
}
