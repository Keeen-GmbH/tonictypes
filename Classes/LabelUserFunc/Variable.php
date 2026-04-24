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

namespace K3n\Tonictypes\LabelUserFunc;

use K3n\Tonictypes\Domain\Model\Variable as VariableModel;
use K3n\Tonictypes\Domain\Repository\VariableRepository;
use K3n\Tonictypes\Utility\LocalizationUtility as Locale;

class Variable
{
    /**
     * @var VariableRepository
     */
    protected $variableRepository;

    /**
     * @param VariableRepository $variableRepository
     */
    public function injectVariableRepository(VariableRepository $variableRepository)
    {
        $this->variableRepository = $variableRepository;
    }

	/**
	 * UserFunc for FieldValue Label
	 *
	 * @param array $pObj Object Information
	 * @return void
	 */
	public function displayLabel(&$pObj): void
	{
		if (isset($pObj["row"]))
		{
			$row = $pObj["row"];
			/* @var Variable $variable */
			$variable = $this->variableRepository->findByUid($row["uid"], false);

			if ($variable instanceof VariableModel) {
				$type = $variable->getType();
				$name = $variable->getVariableName();
                if($type == \K3n\Tonictypes\Domain\Model\Variable::VARIABLE_TYPE_GET ||
                   $type == \K3n\Tonictypes\Domain\Model\Variable::VARIABLE_TYPE_GET_POST ||
                   $type == \K3n\Tonictypes\Domain\Model\Variable::VARIABLE_TYPE_POST
                ) {
                    if($variable->getParameterName() != '') {
                        $name = $variable->getParameterName();
                    }
                }
				$pid = $variable->getPid();
                $uid = $variable->getUid();
				$typeStr = Locale::translate("variable_type.{$type}");
				if ($typeStr) {
                    $pObj["title"] = "[u:{$uid}|p:{$pid}]" . " " . $typeStr . " '{$name}'";
                }
			}
		}
	}
}
