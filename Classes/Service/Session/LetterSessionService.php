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

class LetterSessionService extends SessionService
{
    /**
     * Session Prefix Key
     *
     * @var string
     */
    const SESSION_PREFIX_KEY = "tx-tonictypes-letter";

    /**
     * Session Keys
     *
     * @var string
     */
    const SESSION_KEY_LETTER = "tx-tonictypes-letter-selection";
    const SESSION_KEY_LETTER_FIELD = "tx-tonictypes-letter-field";

    /**
     * Sets the selected letter to the session
     *
     * @param string $letter
     * @return LetterSessionService
     */
    public function setSelectedLetter(string $letter): LetterSessionService
    {
        return $this->writeToSession($letter, self::SESSION_KEY_LETTER);
    }

    /**
     * Gets the selected letter from the session
     *
     * @return string
     */
    public function getSelectedLetter(): string
    {
        return (string)$this->restoreFromSession(self::SESSION_KEY_LETTER);
    }

    /**
     * Sets the letter selection field id to the
     * session
     *
     * @param int|string $field
     * @return LetterSessionService
     */
    public function setLetterSelectionField($field): LetterSessionService
    {
        return $this->writeToSession($field, self::SESSION_KEY_LETTER_FIELD);
    }

    /**
     * Gets the letter selection field from
     * the session
     *
     * @return int|string
     */
    public function getLetterSelectionField()
    {
        return $this->restoreFromSession(self::SESSION_KEY_LETTER_FIELD);
    }
}
