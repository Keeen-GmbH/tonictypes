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

namespace K3n\Tonictypes\ViewHelpers\Record;

use K3n\Tonictypes\Domain\Model\AbstractRecordModel;
use K3n\Tonictypes\Domain\Repository\AbstractRepository;
use K3n\Tonictypes\ViewHelpers\AbstractViewHelper;


class GetViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments.
     * @return void
     * @api
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'Record Uid', true);
        $this->registerArgument('datatype', '\\K3n\\Tonictypes\\Domain\\Model\\Datatype', 'Datatype of the according record', true);
        $this->registerArgument('onlyEnabled', 'bool', 'Only enabled', false, true);
        parent::initializeArguments();
    }

    /**
     * @return AbstractRecordModel|null
     */
    public function render(): ?AbstractRecordModel
    {
        $datatype = $this->arguments['datatype'];
        $repository = $datatype->getRepository();
        if($repository instanceof AbstractRepository) {
            return $repository->findByUid($this->arguments['uid'], $this->arguments['onlyEnabled'],false);
        }

        return null;
    }
}