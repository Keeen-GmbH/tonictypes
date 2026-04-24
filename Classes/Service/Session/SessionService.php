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

namespace K3n\Tonictypes\Service\Session;

use Exception;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class SessionService
{
    /**
     * Session Object
     * @var object
     */
    protected $sessionObject = null;

    /**
     * Prefix Key
     * @var string
     */
    protected $prefixKey = "tx_tonictypes";

    /**
     * Sets the session prefix key
     *
     * @param string $prefixKey
     * @return void
     */
    public function setPrefixKey(string $prefixKey): void
    {
        $this->prefixKey = $prefixKey;
    }

    /**
     * Gets the session prefix key
     *
     * @return string
     */
    public function getPrefixKey(): string
    {
        return $this->prefixKey;
    }

    /**
     * Class constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request !== null) {
            $frontendUser = $request->getAttribute('frontend.user');
            if ($frontendUser instanceof FrontendUserAuthentication) {
                $this->sessionObject = $frontendUser;
            }
        }

        if ($this->sessionObject === null && (($GLOBALS['BE_USER'] ?? null) instanceof BackendUserAuthentication)) {
            $this->sessionObject = $GLOBALS['BE_USER'];
        }
    }

    /**
     * Restores data from the session
     *
     * @param string $key
     * @return mixed
     */
    protected function restoreFromSession(string $key)
    {
        if ($this->sessionObject instanceof FrontendUserAuthentication) {
            $sessionData = $this->sessionObject->getKey('ses', $this->prefixKey . $key);
        } elseif ($this->sessionObject instanceof BackendUserAuthentication) {
            $sessionData = $this->sessionObject->getSessionData($this->prefixKey . $key);
        } else {
            return null;
        }

        if (is_array($sessionData)) {
            return $sessionData;
        }

        return unserialize($sessionData);
    }

    /**
     * Writes data to the session
     *
     * @param mixed $object Object to write to the session
     * @param string $key Identifier for the session
     * @return $this
     */
    protected function writeToSession($object, string $key): self
    {
        if (!$this->sessionObject instanceof FrontendUserAuthentication && !$this->sessionObject instanceof BackendUserAuthentication) {
            return $this;
        }
        $sessionData = serialize($object);
        if ($this->sessionObject instanceof FrontendUserAuthentication) {
            $this->sessionObject->setKey('ses', $this->prefixKey . $key, $sessionData);
            $this->sessionObject->storeSessionData();
        } else {
            $this->sessionObject->setAndSaveSessionData($this->prefixKey . $key, $sessionData);
        }

        return $this;
    }

    /**
     * Cleans a variable that is stored in the session
     *
     * @param string $key
     * @return $this
     */
    protected function cleanUpSession(string $key): self
    {
        if (!$this->sessionObject instanceof FrontendUserAuthentication && !$this->sessionObject instanceof BackendUserAuthentication) {
            return $this;
        }
        if ($this->sessionObject instanceof FrontendUserAuthentication) {
            $this->sessionObject->setKey('ses', $this->prefixKey . $key, null);
            $this->sessionObject->storeSessionData();
        } else {
            $this->sessionObject->setAndSaveSessionData($this->prefixKey . $key, null);
        }

        return $this;
    }


    /**
     * Set/Get attribute wrapper
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws Exception
     */
    public function __call(string $method, array $args)
    {
        $key = $this->_underscore(substr($method, 3));
        switch (substr($method, 0, 3)) {
            case "get":
                return $this->getData($key);
            case "set":
                return $this->setData($key, isset($args[0]) ? $args[0] : null);
            case "uns":
                return $this->unsetData($key);
            case "has":
                return $this->hasData($key);
        }

        throw new Exception("Invalid method " . get_class($this) . "::" . $method . "(" . print_r($args, true) . ")");
    }

    /**
     * Converts field names for Setters and Getters
     * @param string $name
     * @return string
     */
    protected function _underscore(string $name): string
    {
        return strtolower(preg_replace("/(.)([A-Z])/", "$1_$2", $name));
    }

    /**
     * Sets data to the session
     *
     * @param string $key Session Identifier
     * @param mixed $value The value to set
     * @return $this
     */
    public function setData(string $key, $value): self
    {
        return $this->writeToSession($value, $key);
    }

    /**
     * Gets data from the session
     *
     * @param string $key Session Identifier
     * @return mixed
     */
    public function getData(string $key)
    {
        return $this->restoreFromSession($key);
    }

    /**
     * Checks if a session value exists
     *
     * @param string $key
     * @return bool
     */
    public function hasData(string $key): bool
    {
        return ($this->restoreFromSession($key));
    }

    /**
     * Unsets data from the session
     *
     * @param string $key
     * @return $this
     */
    public function unsetData(string $key): self
    {
        return $this->cleanUpSession($key);
    }

}
