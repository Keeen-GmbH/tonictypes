<?php
declare(strict_types=1);
/*
 * This file is part of the package k3n/tonictypes.
 */

namespace K3n\Tonictypes\Service\Transfer;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

final class TonictypesProGuard
{
    public static function isAvailable(): bool
    {
        return ExtensionManagementUtility::isLoaded('tonictypes_pro');
    }

    public static function assertAvailable(): void
    {
        if (!self::isAvailable()) {
            throw new \RuntimeException(
                'This feature requires the extension tonictypes_pro.'
            );
        }
    }
}
