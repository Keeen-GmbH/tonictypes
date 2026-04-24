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

namespace K3n\Tonictypes\Service\Backend;

use K3n\Tonictypes\Domain\Model\AbstractRecordModel;
use K3n\Tonictypes\Domain\Model\Datatype;
use K3n\Tonictypes\Fluid\View\StandaloneView;
use K3n\Tonictypes\Utility\LocalizationUtility;
use K3n\Tonictypes\Utility\UrlUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class FlashMessageService
{
    /**
     * @var string[]
     */
    protected $allowedRoutes = [
        '/module/web/layout',       // Page Module
        '/module/web/list',         // List Module
        '/module/system/config',    // Configuration Module
    ];

    /**
     * Sends a message with the given parameters.
     *
     * @param string $message The message to be sent.
     * @param string $title The title of the message. (optional)
     * @param mixed $severity The severity level of the message. (optional)
     * @param bool $checkRequest Check the request environment
     * @return void
     */
    public function addFlashMessage(string $message, string $title = '', $severity = ContextualFeedbackSeverity::OK, ?ServerRequestInterface $checkRequest = null): void
    {
        $sendMessage = true;
        if($checkRequest instanceof ServerRequestInterface) {
            $sendMessage = false;
            if (ApplicationType::fromRequest($checkRequest)->isBackend() && in_array($checkRequest->getAttribute('route')->getPath(), $this->allowedRoutes)) {
                // Request is valid and flash message can be sent
                $sendMessage = true;
            }
        }

        if($sendMessage === true) {
            $message = GeneralUtility::makeInstance(FlashMessage::class,
                $message,
                $title,
                $severity,
                true
            );

            $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
            $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $messageQueue->addMessage($message);
        }

    }
}