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
     * Human-readable datatype names may include letters, numbers, spaces,
     * hyphens and parentheses (e.g. "MCP Review Datatype (updated)").
     * Special SQL-breaking characters are rejected; table names are sanitized separately.
     */
    private const NAME_PATTERN = '/^[A-Za-z][A-Za-z0-9() \-]*$/';

    /**
     * DataHandler custom eval hook (TYPO3 v12+ compatible signature).
     *
     * @param mixed $value
     * @param string $isIn
     * @param bool $set
     */
    public function evaluateFieldValue($value, $isIn = '', &$set = true): string
    {
        if (!is_scalar($value)) {
            $set = false;
            return '';
        }

        $rawName = trim((string)$value);
        if ($rawName === '') {
            $set = false;
            return '';
        }

        // Collapse repeated whitespace for stable storage / table suggestion.
        $rawName = preg_replace('/\s+/', ' ', $rawName) ?? $rawName;

        if ($this->getValidationError($rawName) !== null) {
            $set = false;
            return '';
        }

        return $rawName;
    }

    /**
     * Human-readable validation error, or null when the name is valid.
     */
    public function getValidationError(string $value): ?string
    {
        $rawName = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        if ($rawName === '') {
            return 'Datatype name cannot be empty.';
        }

        if (!preg_match(self::NAME_PATTERN, $rawName)) {
            return 'Datatype name may only contain letters, numbers, spaces, hyphens and parentheses'
                . ' (e.g. "Job Offer" or "MCP Review Datatype (updated)").'
                . ' Characters like _ . , @ # / \\ are not allowed.';
        }

        // Must still produce a usable PHP/SQL identifier after sanitization.
        $classCode = StringUtility::createCodeFromString($rawName);
        if ($classCode === '') {
            return 'Datatype name does not produce a valid identifier.';
        }

        $className = ucfirst($classCode);
        if ($this->isReservedPhpKeyword($className)) {
            return 'Invalid datatype name: PHP reserved keywords are not allowed.';
        }

        return null;
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
