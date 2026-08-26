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

namespace K3n\Tonictypes\Service\Auth;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Frontend authentication helpers compatible with TYPO3 v12–v14.
 *
 * Prefer the PSR-7 request attribute `frontend.user` (v12+), then fall back to
 * `$GLOBALS['TSFE']->fe_user` for older request bootstrapping paths.
 */
class AuthenticationService extends FrontendUserAuthentication implements SingletonInterface
{
    /**
     * Logs out the current frontend user
     *
     * @return bool
     * @throws AspectNotFoundException
     */
    public function logout(): bool
    {
        if (!$this->isLoggedIn() || !$this->getFrontendUserUid()) {
            return false;
        }

        $feUser = $this->getFrontendUserAuthentication();
        if ($feUser instanceof FrontendUserAuthentication) {
            $feUser->logoff();

            return true;
        }

        return false;
    }

    /**
     * Checks if a user is logged in
     *
     * @return bool
     * @throws AspectNotFoundException
     */
    public function isLoggedIn(): bool
    {
        return (int)GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'id', 0) > 0;
    }

    /**
     * Gets the current logged in frontend user details
     *
     * @return array|null
     * @throws AspectNotFoundException
     */
    public function getFrontendUser(): ?array
    {
        if ($feUserAuth = $this->getFrontendUserAuthentication()) {
            if (is_array($feUserAuth->user) && !empty($feUserAuth->user)) {
                return array_change_key_case($feUserAuth->user, CASE_LOWER);
            }
        }

        return null;
    }

    /**
     * Gets the current logged-in frontend user authentication object.
     *
     * Resolution order (TYPO3 v12–v14):
     * 1. `$GLOBALS['TYPO3_REQUEST']` attribute `frontend.user`
     * 2. Legacy `$GLOBALS['TSFE']->fe_user` when still available
     *
     * @return FrontendUserAuthentication|null
     * @throws AspectNotFoundException
     */
    public function getFrontendUserAuthentication(): ?FrontendUserAuthentication
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $feUser = $this->resolveFrontendUserFromRequest();
        if ($this->isUsableFrontendUser($feUser)) {
            return $feUser;
        }

        $feUser = $this->resolveFrontendUserFromTypoScriptFrontendController();
        if ($this->isUsableFrontendUser($feUser)) {
            return $feUser;
        }

        return null;
    }

    /**
     * Get the uid of the current feuser
     *
     * @return int|null
     * @throws AspectNotFoundException
     */
    public function getFrontendUserUid(): ?int
    {
        $contextUid = (int)GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'id', 0);
        if ($contextUid > 0) {
            return $contextUid;
        }

        $feUser = $this->getFrontendUser();
        if (isset($feUser['uid'])) {
            return (int)$feUser['uid'];
        }

        return null;
    }

    protected function resolveFrontendUserFromRequest(): ?FrontendUserAuthentication
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            return null;
        }

        $feUser = $request->getAttribute('frontend.user');

        return $feUser instanceof FrontendUserAuthentication ? $feUser : null;
    }

    protected function resolveFrontendUserFromTypoScriptFrontendController(): ?FrontendUserAuthentication
    {
        $tsfe = $GLOBALS['TSFE'] ?? null;
        if (!is_object($tsfe) || !isset($tsfe->fe_user)) {
            return null;
        }

        $feUser = $tsfe->fe_user;

        return $feUser instanceof FrontendUserAuthentication ? $feUser : null;
    }

    protected function isUsableFrontendUser(?FrontendUserAuthentication $feUser): bool
    {
        return $feUser instanceof FrontendUserAuthentication
            && is_array($feUser->user)
            && !empty($feUser->user['uid']);
    }
}
