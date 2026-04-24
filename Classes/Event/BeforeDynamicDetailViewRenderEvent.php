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

namespace K3n\Tonictypes\Event;

use K3n\Tonictypes\Controller\RecordController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

final class BeforeDynamicDetailViewRenderEvent
{
    public function __construct(
        protected readonly RecordController $recordController,
        protected readonly RequestInterface $request,
        protected array $settings,
        protected array $variables
    ) {
    }

    /**
     * @return RecordController
     */
    public function getRecordController(): RecordController
    {
        return $this->recordController;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     * @return void
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @param array $variables
     * @return void
     */
    public function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }

}

