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
namespace K3n\Tonictypes\Service\Query;

use K3n\Tonictypes\Domain\Model\Field;
use K3n\Tonictypes\Domain\Model\Variable;
use K3n\Tonictypes\Domain\Repository\FieldRepository;
use K3n\Tonictypes\Domain\Repository\VariableRepository;
use K3n\Tonictypes\Factory\VariableFactory;
use K3n\Tonictypes\Service\Fluid\ConditionService;
use K3n\Tonictypes\Service\Fluid\FluidRenderService;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class QueryFilterService implements \TYPO3\CMS\Core\SingletonInterface

{
    /**
     * @var VariableRepository
     */
    protected $variableRepository;

    /**
     * @var FieldRepository
     */
    protected $fieldRepository;

    /**
     * @var VariableFactory
     */
    protected $variableFactory;

    /**
     * @var ConditionService
     */
    protected $conditionService;

    /**
     * @var FluidRenderService
     */
    protected $fluidRenderService;

    /**
     * @param VariableRepository $variableRepository
     */
    public function injectVariableRepository(VariableRepository $variableRepository)
    {
        $this->variableRepository = $variableRepository;
    }

    /**
     * @param FieldRepository $fieldRepository
     */
    public function injectFieldRepository(FieldRepository $fieldRepository)
    {
        $this->fieldRepository = $fieldRepository;
    }

    /**
     * @param VariableFactory $variableFactory
     */
    public function injectVariableFactory(VariableFactory $variableFactory)
    {
        $this->variableFactory = $variableFactory;
    }

    /**
     * @param ConditionService $conditionService
     */
    public function injectConditionService(ConditionService $conditionService)
    {
        $this->conditionService = $conditionService;
    }

    /**
     * @param FluidRenderService $fluidRenderService
     */
    public function injectFluidRenderService(FluidRenderService $fluidRenderService)
    {
        $this->fluidRenderService = $fluidRenderService;
    }

    /**
     * Parses all rules of a given filter
     * @param null|\stdClass $filter
     * @param array $variables
     * @return \stdClass
     */
    protected function _parseRules(?\stdClass &$filter, array $variables = []): \stdClass
    {
        if(is_null($filter)) {
            $std = new \stdClass();
            $std->rules = [];
            return $std;
        }

        $parsed = clone $filter;
        if (!isset($parsed->rules) || !is_array($parsed->rules) || count($parsed->rules) < 1) {
            return $parsed;
        }

        if(count($parsed->rules) > 0) {
            foreach($parsed->rules as $_i=>$_rule) {
                if (!$_rule instanceof \stdClass) {
                    continue;
                }

                if(property_exists($_rule, 'value') && $_rule->value) {
                    if(is_array($_rule->value)) {
                        // We need to render each array element
                        foreach($_rule->value as $_r=>$_v) {
                            $parsed->rules[$_i]->value[$_r] = $this->fluidRenderService->renderFluid($_v, $variables);
                        }
                    } else {
                        $parsed->rules[$_i]->value = $this->fluidRenderService->renderFluid($_rule->value, $variables);
                    }
                }

                if(property_exists($_rule, 'rules')) {
                    $parsed->rules[$_i]->rules = $this->_parseRules($parsed->rules[$_i], $variables);
                }
            }
        }

        return $parsed;
    }

    /**
     * @param $settings
     * @param array $variables
     * @param string $itemIdentifier
     * @param string $filterIdentifier
     * @param string $conditionIdentifier
     * @param string $postProcessIdentifier
     * @return \stdClass
     */
    public function parseFilters($settings,
        array $variables = [],
        string $itemIdentifier = 'filters',
        string $filterIdentifier = 'querybuilder',
        string $conditionIdentifier = 'activate_condition',
        string $postProcessIdentifier = 'post_process'): \stdClass

    {

        $rawFilters = [];
        if(array_key_exists('field_value_filter', $settings)) {
            if(!is_array($settings['field_value_filter'])) {
                $rawFilters = [];
            } else {
                $rawFilters = $settings['field_value_filter'];
            }
        }

        $filters = new \stdClass();
        $filters->condition = 'AND';
        $filters->rules = [];

        foreach($rawFilters as $_rawFilter) {
            $postProcess = $_rawFilter[$itemIdentifier][$postProcessIdentifier];
            if($postProcess != '') {
                continue;
            }

            if(isset($_rawFilter[$itemIdentifier][$conditionIdentifier]) && isset($_rawFilter[$itemIdentifier][$filterIdentifier])) {
                $condition = $_rawFilter[$itemIdentifier][$conditionIdentifier];
                if($this->_validate($condition, $variables)) {
                    $filter = json_decode($_rawFilter[$itemIdentifier][$filterIdentifier]);
                    $parsedFilter = $this->_parseRules($filter, $variables);

                    if(count($parsedFilter->rules) > 1) {

                        foreach($parsedFilter->rules as $_r) {
                            if ($_r instanceof \stdClass) {
                                $_r->post_process = $postProcess;
                            }
                        }

                        // Multiple filters on this item, so we need to add them with the condition
                        $newFilter = new \stdClass();
                        $newFilter->condition = $parsedFilter->condition;
                        $newFilter->rules = $parsedFilter->rules;
                        $filters->rules[] = $newFilter;
                    } else {
                        $filters->rules = array_merge($filters->rules, $parsedFilter->rules);
                    }
                }
            }
        }

        return $filters;
    }

    /**
     * @param $settings
     * @param array $variables
     * @param string $itemIdentifier
     * @param string $filterIdentifier
     * @param string $conditionIdentifier
     * @param string $postProcessIdentifier
     * @return array
     */
    public function parsePostProcessFilters($settings,
        array $variables = [],
        string $itemIdentifier = 'filters',
        string $filterIdentifier = 'querybuilder',
        string $conditionIdentifier = 'activate_condition',
        string $postProcessIdentifier = 'post_process'): array
    {
        $rawFilters = [];
        if(array_key_exists('field_value_filter', $settings)) {
            if (!is_array($settings['field_value_filter'])) {
                $rawFilters = [];
            } else {
                $rawFilters = $settings['field_value_filter'];
            }
        }

        $filters = [];
        foreach($rawFilters as $_rawFilter) {
            $postProcess = $_rawFilter[$itemIdentifier][$postProcessIdentifier];
            if($postProcess == '') {
                continue;
            }

            if(isset($_rawFilter[$itemIdentifier][$conditionIdentifier]) && isset($_rawFilter[$itemIdentifier][$filterIdentifier])) {
                $condition = $_rawFilter[$itemIdentifier][$conditionIdentifier];
                if($this->_validate($condition, $variables)) {
                    $filter = json_decode($_rawFilter[$itemIdentifier][$filterIdentifier]);
                    $filter->post_process = $postProcess;
                    $filters[] = $this->_parseRules($filter, $variables);
                }
            }
        }

        return $filters;
    }

    /**
     * @param array $settings
     * @param array $variables
     * @param string $itemIdentifier
     * @param string $fieldIdentifier
     * @param string $orderIdentifier
     * @return array
     */
    public function parseOrderings($settings,
        array $variables = [],
        string $itemIdentifier = 'ordering',
        string $fieldIdentifier = 'field',
        string $orderIdentifier = 'order',
        string $conditionIdentifier = 'condition'): array
    {
        $rawOrderings = [];
        if(array_key_exists('orderings', $settings)) {
            if (!is_array($settings['orderings'])) {
                $rawOrderings = [];
            } else {
                $rawOrderings = $settings['orderings'];
            }
        }

        $orderings = [];
        foreach($rawOrderings as $_ordering) {

            $condition = $_ordering[$itemIdentifier][$conditionIdentifier];
            if($this->_validate($condition, $variables)) {

                $orderingField = $_ordering[$itemIdentifier][$fieldIdentifier];

                if(MathUtility::canBeInterpretedAsInteger($orderingField)) {
                    $field = $this->fieldRepository->findByUid($orderingField, false);
                    if(!$field instanceof Field) {
                        continue;
                    }

                    $orderingField = $field->getVariableName();

                    if(array_key_exists('subfield', $_ordering['ordering'])) {
                      if ($_ordering['ordering']['subfield'] != '') {
                        $orderingField = "{$orderingField}.{$_ordering['ordering']['subfield']}";
                      }
                    }
                }

                $orderings[$orderingField] = $_ordering[$itemIdentifier][$orderIdentifier];
            }
        }

        // Respect Overrides
        $sortingVariableKey = (string)($settings['sorting_variable'] ?? '');
        if($sortingVariableKey !== '' && array_key_exists($sortingVariableKey, $variables) && $variables[$sortingVariableKey] != '') {
            $sortingVariableName = $variables[$sortingVariableKey];
            $orderDir = array_key_exists($sortingVariableName, $orderings)?$orderings[$sortingVariableName]:QueryInterface::ORDER_ASCENDING;
            $orderVariableKey = (string)($settings['order_variable'] ?? '');
            if($orderVariableKey !== '' && array_key_exists($orderVariableKey, $variables) && $variables[$orderVariableKey] != '') {
                if(strtoupper((string)$variables[$orderVariableKey]) != QueryInterface::ORDER_ASCENDING) {
                    $orderDir = QueryInterface::ORDER_DESCENDING;
                }
            }

            $orderings[$sortingVariableName] = $orderDir;
        }

        // Check for injected variables in order direction
        foreach($orderings as $_ordering=>$dir) {
            if(MathUtility::canBeInterpretedAsInteger($dir)) {
                $variableUid = $dir;
                $variable = $this->variableRepository->findByUid($variableUid, true);
                if($variable instanceof Variable) {
                    $value = $this->variableFactory->prepareVariableValue($variable);

                    if($value == 'ASC' || $value == 'DESC') {
                        // Ordering direction is valid, so we change it here with the value from the variable
                        $orderings[$_ordering] = $value;
                    } else {
                        // We remove the ordering completely, because the value for the ordering direction is not valid
                        unset($orderings[$_ordering]);
                    }
                }
            }
        }

        return $orderings;
    }

    /**
     * @param array $settings
     * @param array $variables
     * @return int|null
     */
    public function parseLimit(array $settings, array $variables): ?int
    {
        // Can be numeric, or ''
        $limit = $settings['limit'];

        $limitVariableKey = (string)($settings['limit_variable'] ?? '');
        if($limitVariableKey !== '' && array_key_exists($limitVariableKey, $variables) && $variables[$limitVariableKey] != '' && !is_null($variables[$limitVariableKey])) {
            // Limit comes from a variable
            $limit = (int)$variables[$limitVariableKey];
        }

        if($limit == '') {
            $limit = null;
        }

        return $limit;
    }

    /**
     * @param array $settings
     * @param array $variables
     * @return int|null
     */
    public function parseOffset(array $settings, array $variables): ?int
    {
        // Can be numeric, or ''
        $offset = $settings['offset'] ?? 0;

        $offsetVariableKey = (string)($settings['offset_variable'] ?? '');
        if($offsetVariableKey !== '' && array_key_exists($offsetVariableKey, $variables) && $variables[$offsetVariableKey] != '' && !is_null($variables[$offsetVariableKey])) {
            // Limit comes from a variable
            $offset = (int)$variables[$offsetVariableKey];
        }

        if($offset == '') {
            $offset = null;
        }

        return $offset;
    }

    /**
     * @param string $condition
     * @param array $variables
     * @return bool
     */
    protected function _validate($condition, array $variables): bool
    {
        if($condition == '') {
            return true;
        }

        return $this->conditionService->isValid($condition, $variables);
    }
}
