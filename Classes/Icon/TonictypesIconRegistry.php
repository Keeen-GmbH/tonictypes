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
namespace K3n\Tonictypes\Icon;

use K3n\Tonictypes\Configuration\ExtensionConfiguration;
use K3n\Tonictypes\Utility\StringUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;

class TonictypesIconRegistry
{
    /**
     * @var IconRegistry
     */
    protected $iconRegistry;

    /**
     * @return IconRegistry
     */
    protected function _getIconRegistry()
    {
        if (!$this->iconRegistry) {
            $this->iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        }

        return $this->iconRegistry;
    }

    /**
     * Registers all existing datatypes with
     * icons
     * @return void
     */
    public function registerDatatypeIcons(): void
    {
        ///////////////////////////////////////////////////////////
        // We generate page icons for each datatype, that exists //
        ///////////////////////////////////////////////////////////
        $datatypes = [];
        try {
            /* @var \TYPO3\CMS\Core\Database\Connection $query */
            $query = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(ExtensionConfiguration::EXTENSION_DATATYPE_TABLE);

            $datatypes = $query->select(['uid', 'name', 'icon'], 'tx_tonictypes_domain_model_datatype',['deleted'=>0])->fetchAllAssociative();

        } catch (\Exception $e) {
            $datatypes = [];
        }

        try {
            $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
                'label' => 'LLL:EXT:tonictypes/Resources/Private/Language/locallang.xlf:tx_tonictypes',
                'value' => '--div--',
            ];

            $icons = $this->getIcons(['EXT:tonictypes/Resources/Public/Icons/Datatype'], 'extensions-tonictypes-', true, false);
            $bitmapProviderClassName = BitmapIconProvider::class;

            // We need to add the selected datatype icon to the registry
            foreach ($datatypes as $_datatype) {

                if($_datatype['icon'] === '') {
                    continue;
                }

                $iconId = 'tonictypes-datatype-'.$_datatype['uid'];

                // Register icon
                if (!$this->_getIconRegistry()->isRegistered($iconId)) {
                    $source = $icons['extensions-tonictypes-'.$_datatype['icon']];
                    if(!is_null($source)) {
                        $this->_getIconRegistry()->registerIcon($iconId, $bitmapProviderClassName, ['source' => $source]);
                    }
                }

                if (!isset($GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']["contains-tonictypes-{$iconId}"])) {
                    $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
                        'label' => $_datatype['name'],
                        'value' => $iconId,
                        'icon' => $iconId
                    ];
                    $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']["contains-{$iconId}"] = $iconId;
                }
            }

            $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
                'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_view_help.xlf:TOC_extensions',
                'value' => '--div--',
            ];

        } catch(Exception $e) {
            // We need to ignore exceptions here in case the table does not exist
            // No exception printing here
        }
    }

    /**
     * Registers all general tonictypes icons
     * that are delivered within this package
     * @return void
     */
    public function registerTonictypesIcons(): void
    {
        $iconsDatatypes = $this->getIcons(['EXT:tonictypes/Resources/Public/Icons/Datatype'], 'extensions-tonictypes-', true, false);
        $iconsFields = $this->getIcons(['EXT:tonictypes/Resources/Public/Icons/Field'], 'extensions-tonictypes-field-', true, false);
        $bitmapProviderClassName = BitmapIconProvider::class;

        $icons = array_merge($iconsDatatypes,$iconsFields);
        foreach ($icons as $_id=>$_location) {
            if (!$this->_getIconRegistry()->isRegistered($_id)) {
                $this->_getIconRegistry()->registerIcon(
                    $_id,
                    $bitmapProviderClassName,
                    ['source' => $_location]
                );
            }
        }
    }

    /**
     * Gets all typeicon classes for datatypes
     * @return array
     */
    public function getDatatypeTypeiconClasses(): array
    {
        $icons = $this->getIcons(['EXT:tonictypes/Resources/Public/Icons/Datatype']);
        $classes = [];

        foreach ($icons as $_iconFile) {
            $pathinfo 		= pathinfo($_iconFile);
            $code           = StringUtility::createCodeFromString($pathinfo['filename']);
            //$code = str_replace('_','-', $code);
            $classes[$code] = "extensions-tonictypes-{$code}";
        }

        // Default Icon
        $classes['default'] = 'tonictypes-datatype-icon-svg';
        return $classes;
    }

    /**
     * Gets all typeicon classes for fields
     * @return array
     */
    public function getFieldTypeIconClasses(): array
    {
        $icons = $this->getIcons(['EXT:tonictypes/Resources/Public/Icons/Field'], 'extensions-tonictypes-field-');

        foreach ($icons as $_iconFile) {
            $pathinfo = pathinfo($_iconFile);
            $code = StringUtility::createCodeFromString($pathinfo['filename']);
            //$code = str_replace('_','-', $code);
            $classes[$code] = "extensions-tonictypes-field-{$code}";
        }

        $classes['default'] = 'tonictypes-field-icon-svg';

        return $classes;
    }

    /**
     * Gets all icons from the typo3/gfx folder
     *
     * @param array $paths
     * @param string $prefix
     * @param bool $includePrefix
     * @param bool $convertPaths
     * @return array
     * @throws InvalidConfigurationTypeException
     */
    public function getIcons(array $paths = [], string $prefix = 'extensions-tonictypes-', bool $includePrefix = true, bool $convertPaths = true): array
    {
        if (!$includePrefix) {
            $prefix = '';
        }

        $icons = [];
        foreach ($paths as $_path) {
            // We check all paths for icons and add them to the registry
            $path = GeneralUtility::getFileAbsFileName($_path);
            $files = GeneralUtility::getAllFilesAndFoldersInPath([], $path, 'gif,png');

            foreach ($files as $_iconFile) {
                $iconPath = $path;
                if ($convertPaths == false) {
                    $iconPath = $_path;
                }

                $pathinfo = pathinfo($_iconFile);
                $filename = basename($_iconFile);
                $_path = trim($_path, '/');
                $code = StringUtility::createCodeFromString($pathinfo['filename']);
                $code = "{$prefix}{$code}";
                //$code = str_replace('_','-', $code);
                $icons[$code] = $iconPath.'/'.$filename;
            }
        }

        // Plugin Icons
        $icons['tonictypes-icon-tonictypes-record'] = 'EXT:tonictypes/Resources/Public/Icons/Plugins/Record.svg';
        //$icons['tonictypes-icon-tonictypes-search'] = 'EXT:tonictypes/Resources/Public/Icons/Plugins/tonictypes-search.gif';
        //$icons['tonictypes-icon-tonictypes-letter'] = 'EXT:tonictypes/Resources/Public/Icons/Plugins/tonictypes-letter.gif';
        //$icons['tonictypes-icon-tonictypes-sort'] = 'EXT:tonictypes/Resources/Public/Icons/Plugins/tonictypes-sort.gif';
        //$icons['tonictypes-icon-tonictypes-filter'] = 'EXT:tonictypes/Resources/Public/Icons/Plugins/tonictypes-filter.gif';
        //$icons['tonictypes-icon-tonictypes-select'] = 'EXT:tonictypes/Resources/Public/Icons/Plugins/tonictypes-select.gif';
        //$icons['tonictypes-icon-tonictypes-form'] = 'EXT:tonictypes/Resources/Public/Icons/Plugins/tonictypes-form.gif';
        //$icons['tonictypes-icon-tonictypes-pager'] = 'EXT:tonictypes/Resources/Public/Icons/Plugins/tonictypes-pager.gif';

        // Extension Icon
        $icons['tonictypes-ext-icon-svg'] = 'EXT:tonictypes/ext_icon.svg';
        // Toolbar Icon
        $icons['tonictypes-toolbar-icon-svg'] = 'EXT:tonictypes/Resources/Public/Icons/toolbar_icon.svg';
        $icons['tonictypes-toolbar-bright-icon-svg'] = 'EXT:tonictypes/Resources/Public/Icons/toolbar_icon_bright.svg';
        // Datatype Icon
        $icons['tonictypes-datatype-icon-svg'] = 'EXT:tonictypes/Resources/Public/Icons/Domain/Model/datatype.svg';
        // Field Icon
        $icons['tonictypes-field-icon-svg'] = 'EXT:tonictypes/Resources/Public/Icons/Domain/Model/field.svg';
        // FieldValue Icon
        $icons['tonictypes-fieldvalue-icon-svg'] = 'EXT:tonictypes/Resources/Public/Icons/Domain/Model/fieldvalue.svg';
        // Variable Icon
        $icons['tonictypes-variable-icon-svg'] = 'EXT:tonictypes/Resources/Public/Icons/Domain/Model/variable.svg';
        // Extension Icon
        $icons['tonictypes-ext-icon-svg'] = 'EXT:tonictypes/ext_icon.svg';

        /****************************
         * Additional Icons
         ***************************/
        $additionalIcons = [];

        // Plugin Icons
        $additionalIcons['tonictypes-icon-tonictypes-record']  = 'EXT:tonictypes/Resources/Public/Icons/Plugins/Record.svg';
        $additionalIcons['tonictypes-icon-tonictypes-list']    = 'EXT:tonictypes/Resources/Public/Icons/Plugins/List.svg';
        $additionalIcons['tonictypes-icon-tonictypes-detail']  = 'EXT:tonictypes/Resources/Public/Icons/Plugins/Detail.svg';
        $additionalIcons['tonictypes-icon-tonictypes-dynamic'] = 'EXT:tonictypes/Resources/Public/Icons/Plugins/Dynamic.svg';
        $additionalIcons['tonictypes-icon-tonictypes-plain']   = 'EXT:tonictypes/Resources/Public/Icons/Plugins/Plain.svg';

        if (class_exists(ConfigurationManagerInterface::class)) {
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
            $configuration        = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

            if (isset($configuration['plugin.']['tx_tonictypes.']['fieldtypes.']) && is_array($configuration['plugin.']['tx_tonictypes.']['fieldtypes.'])) {
                $fieldtypes = GeneralUtility::removeDotsFromTS($configuration['plugin.']['tx_tonictypes.']['fieldtypes.']);
                foreach ($fieldtypes as $_t => $_ft) {
                    $ft = strtolower($_t);
                    //$ft = str_replace('_','-', $ft);
                    $ftIcon                            = (isset($_ft['icon'])) ? $_ft['icon'] : 'EXT:tonictypes/Resources/Public/Icons/Domain/Model/Field.gif';
                    $additionalIcons["{$prefix}{$ft}"] = $ftIcon;
                }
            }
        }

        foreach ($additionalIcons as $_identifier => $_path) {
            $icons[$_identifier] = $convertPaths ? GeneralUtility::getFileAbsFileName($_path) : $_path;
        }

        return $icons;
    }

    /**
     * Gets an specific icon by hash
     * @param string $hash
     * @return string
     * @throws InvalidConfigurationTypeException
     */
    public function getIconByHash(string $hash): string
    {
        $icons = $this->getIcons();
        foreach ($icons as $_hash=>$icon) {
            if ($_hash == $hash) {
                return $icon;
            }
        }
        return '';
    }
}
