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

class ConfigurationSessionService extends BackendSessionService
{
    /**
     * @var string
     */
    protected $storageKey = "tx_tonictypes_configuration";

    /**
     * Session Key
     *
     * @var string
     */
    const SESSION_KEY_CONFIGURATION = "tx-tonictypes-configuration";

    /**
     * Sets a configuration value
     *
     * @param string $config
     * @param mixed $value
     * @return void
     */
    public function setConfiguration(string $config, $value): void
    {
        $configuration          = $this->getConfiguration();
        $configuration[$config] = $value;
        $this->set(self::SESSION_KEY_CONFIGURATION, $configuration);
    }

    /**
     * Gets a configuration value
     *
     * @param string|null $name
     * @return mixed
     */
    public function getConfiguration(?string $name = null)
    {
        $configuration = $this->get(self::SESSION_KEY_CONFIGURATION);
        if (is_array($configuration)) {
            // return the whole configuration array
            if (is_null($name)) {
                return $configuration;
            } else {
                if (array_key_exists($name, $configuration)) {
                    return $configuration[$name];
                }
            }
        }

        return null;
    }

    /**
     * Resets the configuration
     *
     * @return void
     */
    public function reset(): void
    {
        $this->set(self::SESSION_KEY_CONFIGURATION, []);
    }
}
