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

namespace K3n\Tonictypes\Utility;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class TypoScriptUtility
{
    /**
     * Gets a typoscript value from
     * given typoscript
     *
     * @param string $typoScript
     * @return mixed
     */
    public function getTypoScriptValue(string $typoScript)
    {
        try {
            $hash = md5($typoScript);
            /** @var TypoScriptStringFactory $typoScriptStringFactory */
            $typoScriptStringFactory = GeneralUtility::makeInstance(TypoScriptStringFactory::class);
            $rootNode = $typoScriptStringFactory->parseFromStringWithIncludes("tonictypes-typoscript-rendering-$hash", $typoScript);

            /** @var ServerRequest $request */
            $request = GeneralUtility::makeInstance(ServerRequest::class);
            $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $contentObjectRenderer->setRequest($request);
            $result = $contentObjectRenderer->cObjGet($rootNode->toArray());
            return (string)$result;
        } catch (\Exception $e) {
            return '';
        }
    }

}
