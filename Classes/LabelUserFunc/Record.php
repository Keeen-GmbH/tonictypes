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

use K3n\Tonictypes\Domain\Repository\DatatypeRepository;
use K3n\Tonictypes\Service\Session\BackendSessionService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\ApplicationType;

class Record
{
    /**
     * @var DatatypeRepository
     */
    protected $datatypeRepository;

    /**
     * @var BackendSessionService
     */
    protected $backendSessionService;

    /**
     * @param DatatypeRepository $datatypeRepository
     */
    public function injectDatatypeRepository(DatatypeRepository $datatypeRepository)
    {
        $this->datatypeRepository = $datatypeRepository;
    }

    /**
     * @param BackendSessionService $backendSessionService
     */
    public function injectBackendSessionService(BackendSessionService $backendSessionService)
    {
        $this->backendSessionService = $backendSessionService;
    }

	/**
	 * UserFunc for Field Label
	 *
	 * @param array $pObj Object Information
	 * @return void
	 */
	public function displayLabel(&$pObj): void
	{
        $mainRequest = $GLOBALS['TYPO3_REQUEST'];
        if (ApplicationType::fromRequest($mainRequest)->isBackend()) {
            return;
        }

		if($pObj['row']['sys_language_uid'] > 0) {
            $row = 	BackendUtility::getRecord($pObj['table'], $pObj['row']['uid'],'*');
            $pObj['row'] = $row;
        }

        $pObj['title'] = $pObj['row']['title'];
	}

}
