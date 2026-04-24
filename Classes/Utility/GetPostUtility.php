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

namespace K3n\Tonictypes\Utility;

use K3n\Tonictypes\Domain\Model\Variable;
use Psr\Http\Message\ServerRequestInterface;

class GetPostUtility
{
	/**
	 * Secures a GET variable
	 *
	 * @param string $variable
	 * @return string
	 */
	public static function secureVariableGet(string $variable): string
	{
		$variable = htmlspecialchars($variable);
		$variable = strip_tags($variable);

		return $variable;
	}

	/**
	 * Secures a POST variable
	 *
	 * @param string $variable
	 * @return string
	 */
	public static function secureVariablePost(string $variable): string
	{
		$variable = htmlspecialchars($variable);
		$variable = strip_tags($variable);

		return $variable;
	}

    /**
     * @param string $parameterName
     * @param string $type
     * @return mixed
     */
    public static function getEnvironmentalParameterValue(string $parameterName, string $type = 'GET')
    {
        $request = self::getCurrentRequest();
        $queryParams = $request?->getQueryParams() ?? [];
        $parsedBody = $request?->getParsedBody();
        $postParams = is_array($parsedBody) ? $parsedBody : [];

        switch ($type) {
            case 'GET':
            case (string)Variable::VARIABLE_TYPE_GET:
                return $queryParams[$parameterName] ?? $_GET[$parameterName] ?? null;
            case (string)Variable::VARIABLE_TYPE_POST:
            case 'POST':
                return $postParams[$parameterName] ?? $_POST[$parameterName] ?? null;
            case (string)Variable::VARIABLE_TYPE_GET_POST:
            case 'GET/POST':
            case 'GETPOST':
            default:
                return $queryParams[$parameterName] ?? $postParams[$parameterName] ?? $_REQUEST[$parameterName] ?? null;
        }
    }

    protected static function getCurrentRequest(): ?ServerRequestInterface
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        return $request instanceof ServerRequestInterface ? $request : null;
    }
}
