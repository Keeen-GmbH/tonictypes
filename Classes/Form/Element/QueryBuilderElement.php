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
use TYPO3\CMS\Fluid\View\StandaloneView;

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

        $randVarName = uniqid('qb_');
        $view->assign('id', $randVarName);

        if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() < 14)
        {
            $datatype = 0;
            $fieldId = $this->resolveItemFormElementId((array)($this->data['parameterArray'] ?? []));
            if (isset($this->data['parameterArray']) && is_array($this->data['parameterArray'])) {
                // Keep both keys to support v12/v13 template expectations.
                $this->data['parameterArray']['itemFormElID'] = $fieldId;
                $this->data['parameterArray']['itemFormElId'] = $fieldId;
            }
            $view->assign('data', $this->data);

            $builderId = "#builder_{$fieldId}_{$this->data['vanillaUid']}_{$this->data['fieldName']}";
            $pages = $this->normalizePageUids($this->data['databaseRow']['pages'] ?? []);
            $languageUid = $this->resolvePositiveIntegerValue($this->data['databaseRow']['sys_language_uid'] ?? null) ?? 0;

            if (!empty($this->data['databaseRow']['pi_flexform'])) {
                $flexForm = $this->data['databaseRow']['pi_flexform'];
                if (is_string($flexForm)) {
                    $flexForm = GeneralUtility::xml2array($flexForm);
                }
                if (is_array($flexForm)) {
                    $flexForm = $this->flexFormService->walkFlexFormNode($flexForm);
                    $flexForm = $this->flexFormService->walkFlexFormNode($flexForm, 'lDEF');
                    $datatype = $this->resolvePositiveIntegerValue($flexForm['data']['general_settings']['settings']['datatype_selection'] ?? null) ?? 0;
                }
            }

            $view->assignMultiple([
                'fieldId' => $fieldId,
                'builderId' => $builderId,
                'pages' => $pages,
                'languageUid' => $languageUid,
                'datatype' => $datatype,
            ]);
        } else {
            if(!empty($this->data)) {
                $flexForm = $this->data['databaseRow']['pi_flexform'];
                if (is_array($flexForm)) {
                    // Keep parsed data as-is.
                } elseif (is_string($flexForm)) {
                    $flexForm = $this->flexFormService->convertFlexFormContentToArray($flexForm);
                } else {
                    $flexForm = [];
                }
    
                $datatype = (int)reset($flexForm['data']['general_settings']['settings']['datatype_selection']);
    
                // Render javascript code
                $fieldId = $this->resolveItemFormElementId($this->data['parameterArray'] ?? []);
                $builderId = "#builder_{$fieldId}_{$this->data['vanillaUid']}_{$this->data['fieldName']}";
                $pages = array_column($this->data['databaseRow']['pages'], 'uid');
                $languageUid = is_array($this->data['databaseRow']['sys_language_uid'])?reset($this->data['databaseRow']['sys_language_uid']):$this->data['databaseRow']['sys_language_uid'];
    
                $view->assignMultiple([
                   'fieldId' => $fieldId,
                   'builderId' => $builderId,
                   'pages' => $pages,
                   'languageUid' => $languageUid,
                   'datatype' => $datatype,
                ]);
            }
        }  

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
            return $itemFormElId;
        }
        return $this->sanitizeItemFormElementId((string)($parameterArray['itemFormElName'] ?? ''));
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

    protected function resolvePositiveIntegerValue($value): ?int
    {
        if (is_array($value)) {
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