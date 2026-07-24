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

namespace K3n\Tonictypes\Form\Element;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Form\Element\UserElement as BackendFormUserElement;

class UserElement extends BackendFormUserElement
{
    /**
     * Additional ResultArray
     * for merging information
     * that needs to be processed
     *
     * @var array
     */
    public $additionalResultArray = [];

    /**
     * @param string $identifier
     * @return mixed
     */
    public function getData(string $identifier)
    {
        return $this->data[$identifier];
    }

    /**
     * User defined field type
     *
     * TYPO3 v13+ treats type="user" without a dedicated renderType as a
     * fallback (no userFunc). Keep legacy userFunc support for tonictypes
     * fields (e.g. logo), otherwise defer to the core dummy output.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $parameterArray = $this->data['parameterArray'];
        $userFunc = $parameterArray['fieldConf']['config']['userFunc'] ?? null;
        if (!is_string($userFunc) || $userFunc === '') {
            return parent::render();
        }

        $parameterArray['table'] = $this->data['tableName'];
        $parameterArray['field'] = $this->data['fieldName'];
        $parameterArray['row'] = $this->data['databaseRow'];
        $parameterArray['parameters'] = $parameterArray['fieldConf']['config']['parameters'] ?? [];
        $resultArray = $this->initializeResultArray();
        $result = GeneralUtility::callUserFunction(
            $userFunc,
            $parameterArray,
            $this
        );

        if (is_string($result)) {
            $resultArray['html'] = $result;
        } else {
            $resultArray['html'] = '';
        }

        return array_merge($resultArray, $this->additionalResultArray);
    }
}