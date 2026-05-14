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

namespace K3n\Tonictypes\ViewHelpers\Backend\Render;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

class RecordViewHelper extends AbstractRenderViewHelper
{
    /**
     * Arguments initialization
     * @throws Exception
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('uid', 'mixed', 'Uid of the record', true);
        $this->registerArgument('table', 'string', 'Name of the table', true);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $table = $this->resolveTableName($this->arguments['table']);
        $uid = $this->resolveIdentifierToInt($this->arguments['uid']);
        if ($table === '') {
            return 'Record table missing';
        }
        if ($uid <= 0) {
            return $table . ':' . $uid . ' not found!';
        }
        $record = BackendUtility::getRecordWSOL($table, $uid);

        $string = '';
        if(is_array($record) && !empty($record)) {
            $labelField = $GLOBALS['TCA'][$table]['ctrl']['label'];
            $string .= $this->iconFactory->getIconForRecord($table,$record, GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() < 13 ? Icon::SIZE_SMALL : IconSize::SMALL)->render();
            $string .= ' ';
            $string .= '['.$record['uid'].']'.' '.$record[$labelField];
        } else {
            $string .= $table.':'.$uid.' not found!';
        }

        return $string;
    }

    /**
     * Resolves Fluid values like RecordPropertyClosure / Record / scalar to uid.
     *
     * @param mixed $value
     */
    protected function resolveIdentifierToInt($value): int
    {
        if ($value === null) {
            return 0;
        }
        if ($value instanceof \Closure) {
            return $this->resolveIdentifierToInt($value());
        }
        if (is_object($value) && is_callable($value)) {
            return $this->resolveIdentifierToInt($value());
        }
        if (is_object($value) && method_exists($value, 'getUid')) {
            return (int)$value->getUid();
        }
        if (is_array($value) && isset($value['uid'])) {
            return (int)$value['uid'];
        }
        if (is_scalar($value)) {
            return (int)$value;
        }
        return 0;
    }

    /**
     * Resolves Fluid values like RecordPropertyClosure / scalar to table name.
     *
     * @param mixed $value
     */
    protected function resolveTableName($value): string
    {
        if ($value === null) {
            return '';
        }
        if ($value instanceof \Closure) {
            return $this->resolveTableName($value());
        }
        if (is_object($value) && is_callable($value)) {
            return $this->resolveTableName($value());
        }
        if (is_scalar($value)) {
            return trim((string)$value);
        }
        return '';
    }
}
