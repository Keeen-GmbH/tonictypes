<?php
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
declare(strict_types = 1);

namespace K3n\Tonictypes\Form\Element;

use K3n\Tonictypes\Utility\UrlUtility;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use K3n\Tonictypes\Fluid\View\StandaloneView;

class QueryBuilderElement extends AbstractFormElement
{
    /**
     * @var FlexFormService
     */
    protected $flexFormService;

    /**
     * Container objects give $nodeFactory down to other containers.
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(?NodeFactory $nodeFactory = null, array $data = [])
    {
        $this->data = $data;
        if ($nodeFactory !== null) {
            $this->nodeFactory = $nodeFactory;
        }
        $this->flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
    }

    /**
     * Main render method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        // Render the HTML output
        $result = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'] ?? [];
        if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() < 14) {
            if (empty($parameterArray['itemFormElID']) && !empty($parameterArray['itemFormElId'])) {
                // TYPO3 v13 may provide itemFormElId instead of itemFormElID.
                $this->data['parameterArray']['itemFormElID'] = (string)$parameterArray['itemFormElId'];
            }
        }

        /* @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $template = 'EXT:tonictypes/Resources/Private/Templates/UserFunc/Form/Element/QueryBuilderElement.html';
        $template = GeneralUtility::getFileAbsFileName($template);
        $view->setTemplatePathAndFilename($template);

        $css = [
            'EXT:tonictypes/Resources/Public/Css/Contrib/query-builder.default.css',
            'EXT:tonictypes/Resources/Public/Css/tonictypes-backend.css',
        ];

        $cssFiles = [];
        foreach($css as $_css) {
            $cssFiles[] = UrlUtility::getFileUrl($_css);
        }

        $view->assign('cssFiles', $cssFiles);
        $view->assign('data', $this->data);

        $randVarName = StringUtility::getUniqueId('qb_');
        $view->assign('id', $randVarName);

        $datatype = 0;
        $parameterArray = (array)($this->data['parameterArray'] ?? []);
        $fieldId = $this->resolveItemFormElementId($parameterArray);
        $valueFieldValue = $this->resolveItemFormElementValue($parameterArray);
        $pages = [];
        $languageUid = 0;

        if (isset($this->data['parameterArray']) && is_array($this->data['parameterArray'])) {
            // itemFormElID was removed in TYPO3 v13; keep both keys for v12 templates / legacy code.
            $this->data['parameterArray']['itemFormElID'] = $fieldId;
            $this->data['parameterArray']['itemFormElId'] = $fieldId;
        }

        $view->assignMultiple([
            'fieldId' => $fieldId,
            'valueFieldValue' => $valueFieldValue,
        ]);

        $databaseRow = $this->data['databaseRow'] ?? null;
        if (is_array($databaseRow)) {
            $pages = $this->normalizePageUids($databaseRow['pages'] ?? []);
            $languageUid = $this->resolveLanguageUidFromDatabaseRow($databaseRow);

            if (!empty($databaseRow['pi_flexform'])) {
                $flexForm = $this->parsePiFlexForm($databaseRow['pi_flexform']);
                $datatype = $this->resolveFlexFormDatatypeSelection($flexForm);
            }
        }

        $builderId = "#builder_{$fieldId}_{$this->data['vanillaUid']}_{$this->data['fieldName']}";
        $view->assign('data', $this->data);
        $view->assignMultiple([
            'fieldId' => $fieldId,
            'builderId' => $builderId,
            'pages' => $pages,
            'languageUid' => $languageUid,
            'datatype' => $datatype,
        ]);

        $result['html'] = $view->render();
        $result['javaScriptModules'][] = JavaScriptModuleInstruction::create('jquery-extendext');
        $result['javaScriptModules'][] = JavaScriptModuleInstruction::create('query-builder');
        $result['javaScriptModules'][] = JavaScriptModuleInstruction::create('query-builder-templates');
        $result['javaScriptModules'][] = JavaScriptModuleInstruction::create('@k3n/tonictypes/QueryBuilderElement.js')
            ->instance(
                $randVarName,           // Id
                (int)$datatype,                // Datatype Id
                $fieldId,                      // Value Field Id
                $languageUid,                  // Language Uid
                $pages                         // Page Ids
        );

        return $result;
    }

    protected function resolveItemFormElementId(array $parameterArray): string
    {
        $itemFormElId = (string)($parameterArray['itemFormElID'] ?? $parameterArray['itemFormElId'] ?? '');
        if ($itemFormElId !== '') {
            return $this->sanitizeItemFormElementId($itemFormElId);
        }
        return $this->sanitizeItemFormElementId((string)($parameterArray['itemFormElName'] ?? ''));
    }

    protected function resolveItemFormElementValue(array $parameterArray): string
    {
        $value = $parameterArray['itemFormElValue'] ?? '';
        if (is_array($value)) {
            if ($value === []) {
                return '';
            }
            $value = reset($value);
        }
        if (!is_scalar($value)) {
            return '';
        }

        return (string)$value;
    }

    protected function sanitizeItemFormElementId(string $itemFormElementName): string
    {
        $fieldId = str_replace(['][', '[', ']'], ['_', '_', ''], $itemFormElementName);
        $fieldId = (string)preg_replace('/[^a-zA-Z0-9_:-]/', '_', $fieldId);
        $fieldId = (string)preg_replace('/_+/', '_', $fieldId);
        $fieldId = trim($fieldId, '_');
        if ($fieldId === '') {
            return 'x_tonictypes_field';
        }
        return (string)preg_replace('/^[^a-zA-Z]/', 'x', $fieldId);
    }

    protected function parsePiFlexForm(mixed $piFlexform): array
    {
        if ($piFlexform === null || $piFlexform === '' || $piFlexform === []) {
            return [];
        }

        if (is_string($piFlexform)) {
            return $this->flexFormService->convertFlexFormContentToArray($piFlexform);
        }

        if (!is_array($piFlexform)) {
            return [];
        }

        if ($this->flexFormNeedsNormalization($piFlexform)) {
            $flexForm = $this->walkFlexFormNode($piFlexform);
            return is_array($flexForm) ? $this->walkFlexFormNode($flexForm, 'lDEF') : [];
        }

        return $piFlexform;
    }

    protected function flexFormNeedsNormalization(array $flexForm): bool
    {
        if (isset($flexForm['data']) && is_array($flexForm['data'])) {
            $datatypeSelection = $flexForm['data']['general_settings']['settings']['datatype_selection'] ?? null;
            if (is_array($datatypeSelection) && (array_key_exists('vDEF', $datatypeSelection) || array_key_exists('lDEF', $datatypeSelection))) {
                return true;
            }
            return false;
        }

        return isset($flexForm['sheets']) || isset($flexForm['el']) || array_key_exists('vDEF', $flexForm) || array_key_exists('lDEF', $flexForm);
    }

    /**
     * Normalize raw flexform XML arrays (v12/v13 backend) to flat value arrays.
     *
     * @param mixed $nodeArray
     * @param string $valuePointer
     * @return mixed
     */
    protected function walkFlexFormNode($nodeArray, string $valuePointer = 'vDEF')
    {
        if (is_array($nodeArray)) {
            $return = [];
            foreach ($nodeArray as $nodeKey => $nodeValue) {
                if ($nodeKey === $valuePointer) {
                    return $nodeValue;
                }
                if (in_array($nodeKey, ['el', '_arrayContainer'], true)) {
                    return $this->walkFlexFormNode($nodeValue, $valuePointer);
                }
                if (($nodeKey[0] ?? '') === '_') {
                    continue;
                }
                if (strpos((string)$nodeKey, '.') !== false) {
                    $nodeKeyParts = explode('.', (string)$nodeKey);
                    $currentNode = &$return;
                    $nodeKeyPartsCount = count($nodeKeyParts);
                    for ($i = 0; $i < $nodeKeyPartsCount - 1; $i++) {
                        $currentNode = &$currentNode[$nodeKeyParts[$i]];
                    }
                    $newNode = [next($nodeKeyParts) => $nodeValue];
                    $subVal = $this->walkFlexFormNode($newNode, $valuePointer);
                    $currentNode[key($subVal)] = current($subVal);
                } elseif (is_array($nodeValue)) {
                    if (array_key_exists($valuePointer, $nodeValue)) {
                        $return[$nodeKey] = $nodeValue[$valuePointer];
                    } else {
                        $return[$nodeKey] = $this->walkFlexFormNode($nodeValue, $valuePointer);
                    }
                } else {
                    $return[$nodeKey] = $nodeValue;
                }
            }
            return $return;
        }
        return $nodeArray;
    }

    protected function resolveFlexFormDatatypeSelection(array $flexForm): int
    {
        $value = $flexForm['data']['general_settings']['settings']['datatype_selection'] ?? null;

        return $this->resolvePositiveIntegerValue($value) ?? 0;
    }

    protected function resolveLanguageUidFromDatabaseRow(array $databaseRow): int
    {
        return $this->resolveIntegerValue($databaseRow['sys_language_uid'] ?? 0);
    }

    protected function resolveIntegerValue($value): int
    {
        if (is_array($value)) {
            if ($value === []) {
                return 0;
            }
            $value = reset($value);
        }
        if (!is_scalar($value) || !is_numeric((string)$value)) {
            return 0;
        }

        return (int)$value;
    }

    protected function resolvePositiveIntegerValue($value): ?int
    {
        if (is_array($value)) {
            if ($value === []) {
                return null;
            }
            $value = reset($value);
        }
        if (!is_scalar($value) || !is_numeric((string)$value)) {
            return null;
        }
        $value = (int)$value;
        return $value > 0 ? $value : null;
    }

    protected function normalizePageUids($pages): array
    {
        if (!is_array($pages)) {
            $uid = $this->resolvePositiveIntegerValue($pages);
            return $uid === null ? [] : [$uid];
        }

        $uids = [];
        foreach ($pages as $page) {
            if (is_array($page)) {
                $uid = $this->resolvePositiveIntegerValue($page['uid'] ?? null);
            } else {
                $uid = $this->resolvePositiveIntegerValue($page);
            }
            if ($uid !== null) {
                $uids[] = $uid;
            }
        }

        return array_values(array_unique($uids));
    }
}