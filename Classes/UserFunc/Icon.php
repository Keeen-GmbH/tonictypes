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

namespace K3n\Tonictypes\UserFunc;

use K3n\Tonictypes\Icon\TonictypesIconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class Icon
{
	/**
	 * Gets icons
	 *
	 * @param array $config Configuration Array
	 * @param mixed $parentObject Parent Object
	 * @return array
	 */
	public function displayIconSelection(array &$config, &$parentObject): string
	{
		$fieldName = (string)($config['itemFormElName'] ?? $config['itemFormElname'] ?? '');
		$fieldId = $this->resolveItemFormElementId($config);
		$value = (string)($config['itemFormElValue'] ?? $config['itemFormElvalue'] ?? '');
		$checked		= ($value == "")?"checked":"";
		$onChange = $config['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] ?? '';
		if (!is_string($onChange)) {
			$onChange = '';
		}
        //onchange=\"{$onChange}\"

		/* @var TonictypesIconRegistry $dvIconRegistry */
		$dvIconRegistry = GeneralUtility::makeInstance(TonictypesIconRegistry::class);
		$icons = $dvIconRegistry->getIcons(['EXT:tonictypes/Resources/Public/Icons/Datatype'],'extensions-tonictypes-',false);

		$html = "";

        $border = "1px solid #c0c0c0";
        if ($checked == "checked") {
            $border = "2px solid #000";
        }

		// Empty - Default Icon
		$emptyOptionId = $fieldId . '_empty';
		$html .= "<div style=\"width:50px; height: 30px; border: {$border}; margin:0 3px 3px 0; padding: 3px; \">";
		$html .= "<input type=\"radio\" {$checked} id=\"{$emptyOptionId}\" name=\"{$fieldName}\" value=\"\" style=\"float:left; margin-right:4px; \">";
		$html .= "<label for=\"{$emptyOptionId}\" style=\"display:block; width: 22px; float: left; cursor:pointer;\">" . "</label>";
		$html .= "</div>";

		$i = 1;
		foreach ($icons as $_hash=>$_file) {
			$file = GeneralUtility::getFileAbsFileName($_file);
			if (file_exists($file)) {
				$imageSize = getimagesize($file);
                if(!is_array($imageSize)) {
                    continue;
                }
				$xS = $imageSize[0];
				$yS = $imageSize[1];
				$checked = ($value == $_hash)? "checked" : "";
                $border = "1px solid #c0c0c0";
                if ($value == $_hash) {
                    $border = "2px solid #000";
                }

				$img = PathUtility::getAbsoluteWebPath($file);
				if ($xS <= 22 && $yS <= 22) {
					$optionId = $fieldId . '_' . $i;
					$html .= "<div style=\"width:50px; height: 30px; float: left; border: {$border}; margin:0 3px 3px 0; padding: 3px; \">";
					$html .= "<input type=\"radio\" {$checked} id=\"{$optionId}\" name=\"{$fieldName}\" value=\"{$_hash}\" style=\"float:left; margin-right:4px; \">";
					$html .= "<label for=\"{$optionId}\" style=\"display:block; width: 22px; float: left; cursor:pointer;\">" . "<img src=\"{$img}\" border=\"0\" title=\"extensions-tonictypes-{$_hash}\">" . "</label>";
					$html .= "</div>";
				}

				$i++;
			}
		}
		return $html;
	}

    protected function resolveItemFormElementId(array $config): string
    {
        $itemFormElId = (string)($config['itemFormElID'] ?? $config['itemFormElId'] ?? '');
        if ($itemFormElId !== '') {
            return $itemFormElId;
        }

        $itemFormElName = (string)($config['itemFormElName'] ?? $config['itemFormElname'] ?? '');
        $fieldId = (string)preg_replace('/[^a-zA-Z0-9_:-]/', '_', $itemFormElName);
        if ($fieldId === '') {
            return 'x_tonictypes_icon';
        }

        return (string)preg_replace('/^[^a-zA-Z]/', 'x', $fieldId);
    }
}
