<?php
declare(strict_types=1);
/*
 * This file is part of the package k3n/tonictypes.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * Many thanks to Auth: B. Zagar / Maint: J. Pietschmann for sharing this extension - TYPO3 inspiring people to share!
 * Contact: support@tonictypes.com
 *
 */

namespace K3n\Tonictypes\Evaluation;

use K3n\Tonictypes\Utility\StringUtility;

class DatatypeNameEvaluation
{
    /**
     * DataHandler custom eval hook (TYPO3 v12/v13 compatible signature).
     *
     * @param mixed $value
     * @param string $isIn
     * @param bool $set
     */
    public function evaluateFieldValue($value, $isIn = '', &$set = true): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $rawName = trim((string)$value);
        if ($rawName == '') {
            return '';
        }

        $classCode = StringUtility::createCodeFromString($rawName);
        if ($classCode == '') {
            return '';
        }

        $className = ucfirst($classCode);
        if ($this->isReservedPhpKeyword($className)) {
            return '';
        }

        return $rawName;
    }

    protected function isReservedPhpKeyword(string $candidate): bool
    {
        $tokens = token_get_all('<?php ' . $candidate);
        if (!isset($tokens[1])) {
            return true;
        }

        $token = $tokens[1];
        if (!is_array($token)) {
            return true;
        }

        // Non-keyword identifiers are returned as T_STRING.
        return $token[0] !== T_STRING;
    }
}

