<?php
/**
 *
 * @package phpBB Extension - eparsons/restapi
 * @copyright (c) 2018 Eric Parsons
 * @license https://opensource.org/licenses/GPL-2.0 GNU General Public License v2
 *
 */
namespace eparsons\restapi;

use Symfony\Component\HttpFoundation\JsonResponse;

if (!defined('IN_PHPBB'))
{
    exit();
}

class ErrorResponse extends JsonResponse
{
    /**
     * Constructor
     *
     * @param string $errorName
     * @param string $errorMessage
     * @param int $statusCode
     */
    public function __construct($errorName, $errorMessage, $statusCode)
    {
        $response = array();
        $response['error'] = $errorName;
        $response['errorMessage'] = $errorMessage;
        parent::__construct($response, $statusCode);
    }
}

