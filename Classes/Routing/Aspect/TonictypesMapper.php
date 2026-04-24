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

namespace K3n\Tonictypes\Routing\Aspect;

use K3n\Tonictypes\Domain\Model\Datatype;
use K3n\Tonictypes\Domain\Repository\DatatypeRepository;
use K3n\Tonictypes\Tca\Generator;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Routing\Aspect\PersistedPatternMapper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 *  The Mapper needs configuration of the according datatype
 *  in combination with the Tonictypes Route Enhancer, that
 *  will limit the routing to the target pages with the detail
 *  plugin.
 *
 *  See the following example for configuration
 *
 *  routeEnhancers:
 *     News:
 *         type: Tonictypes
 *         targetPages: [ 26,27,28 ]
 *         routes:
 *             - { routePath: '{url}', _controller: 'Record::dynamicDetail', _arguments: {'url': 'record'} }
 *         defaultController: 'Record::dynamicDetail'
 *         aspects:
 *             url:
 *                 type: TonictypesMapper
 *                 datatype: 3
 *                 routeFieldPattern: '^(?P<path_segment>.+)$'
 *                 routeFieldResult: '{path_segment}'
 */
class TonictypesMapper extends PersistedPatternMapper
{
    /**
     * Target Page Id
     * @var int
     */
    protected $pageId;

    /**
     * @param array $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        $datatypeUid = $settings['datatype'] ?? null;

        if(!is_numeric($datatypeUid)) {
            throw new \InvalidArgumentException('datatype must be integer', 1639584156);
        }

        $datatype = BackendUtility::getRecord('tx_tonictypes_domain_model_datatype', $datatypeUid, 'tablename');

        if(!$datatype || !array_key_exists('tablename', $datatype)) {
            throw new \InvalidArgumentException('datatype with uid \''.$datatypeUid.'\' not found', 1639584369);
        }

        $settings['tableName'] = $datatype['tablename'];

        // We need to generate the additional tonictypes tca, to correctly prefill the $GLOBALS
        //$tcaGenerator = GeneralUtility::makeInstance(Generator::class);
        //$tcaGenerator->processTca(true);

        // Because we define this by tonictypes with no direct possibility to change,
        // we manually inject this here to speed up a little bit!
        $GLOBALS['TCA'][$settings['tableName']]['ctrl']['languageField'] = 'sys_language_uid';
        $GLOBALS['TCA'][$settings['tableName']]['ctrl']['transOrigPointerField'] = 'l10n_parent';

        parent::__construct($settings);
    }

    /**
     * @return int
     */
    public function getPageId(): int
    {
        return $this->pageId;
    }

    /**
     * @param int $pageId
     */
    public function setPageId(int $pageId): void
    {
        $this->pageId = $pageId;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $value): ?string
    {
        $result = $this->findByIdentifier($value);
        $result = $this->resolveOverlay($result);
        return $this->createRouteResult($result);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $value): ?string
    {
        if (!preg_match('#' . $this->routeFieldPattern . '#', $value, $matches)) {
            return null;
        }
        $values = $this->filterNamesKeys($matches);
        $result = $this->findByRouteFieldValues($values);
        if ($result[$this->languageParentFieldName] ?? null > 0) {
            return (string)$result[$this->languageParentFieldName];
        }
        if (isset($result['uid'])) {
            return (string)$result['uid'];
        }
        return null;
    }

}