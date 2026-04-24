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

namespace K3n\Tonictypes\ViewHelpers\Datatype;

use K3n\Tonictypes\Domain\Model\Datatype;
use K3n\Tonictypes\Domain\Repository\DatatypeRepository;
use K3n\Tonictypes\ViewHelpers\AbstractViewHelper;

class GetViewHelper extends AbstractViewHelper
{
    /**
     * @var DatatypeRepository
     */
    protected $datatypeRepository;

    /**
     * @param DatatypeRepository $datatypeRepository
     */
    public function injectDatatypeRepository(DatatypeRepository $datatypeRepository)
    {
        $this->datatypeRepository = $datatypeRepository;
    }

    /**
     * Initialize arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'Datatype Uid', true, '');
        $this->registerArgument('onlyEnabled', 'bool', 'Only enabled', false, true);
        parent::initializeArguments();
    }

    /**
     * @return Datatype|null
     */
    public function render(): ?Datatype
    {
        $datatype = $this->datatypeRepository->findByUid($this->arguments['uid'], $this->arguments['onlyEnabled']);
        if ($datatype instanceof Datatype) {
            return $datatype;
        }

        return null;
    }
}