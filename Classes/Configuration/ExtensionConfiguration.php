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

namespace K3n\Tonictypes\Configuration;

class ExtensionConfiguration
{
    /**
     * Extension Key
     *
     * @var string
     */
    public const EXTENSION_KEY = 'tonictypes';

    /**
     * Tables
     *
     * @var string
     */
    public const EXTENSION_FIELD_TABLE 			= 'tx_tonictypes_domain_model_field';
    public const EXTENSION_FIELD_VALUE_TABLE		= 'tx_tonictypes_domain_model_fieldvalue';
    public const EXTENSION_DATATYPE_TABLE 			= 'tx_tonictypes_domain_model_datatype';

    /**
     * Gets the language field for datatypes
     *
     * @return string
     */
    public static function getDatatypesLanguageField(): string
    {
        return $GLOBALS['TCA'][self::EXTENSION_DATATYPE_TABLE]['ctrl']['languageField'];
    }

    /**
     * Gets the language field for fields
     *
     * @return string
     */
    public static function getFieldsLanguageField(): string
    {
        return $GLOBALS['TCA'][self::EXTENSION_FIELD_TABLE]['ctrl']['languageField'];
    }

    /**
     * Gets the language field for fieldValues
     *
     * @return string
     */
    public static function getFieldValuesLanguageField(): string
    {
        return $GLOBALS['TCA'][self::EXTENSION_FIELD_VALUE_TABLE]['ctrl']['languageField'];
    }
}
