<?php
/**
*
* @package phpBB Extension - eparsons/restapi
* @copyright (c) 2018 Eric Parsons
* @license https://opensource.org/licenses/GPL-2.0 GNU General Public License v2
*
*/
namespace eparsons\restapi;

use phpbb\request\request_interface;
use Symfony\Component\HttpFoundation\JsonResponse;

if (!defined('IN_PHPBB'))
{
    exit();
}

class Validation
{
	/**
	* phpBB request
	*
	* @var request_interface
	*/
	private $request;

    /**
    * Constructor
    *
    * @param    request_interface   $request
    */
    public function __construct(request_interface   $request)
    {
        $this->request = $request;
    }

    /**
     * Validates requests
     * @param string $requiredRequestMethod
     * @param string $hostname
     * @return null|JsonResponse
     */
    public function ValidateRequest($requiredRequestMethod = 'GET', $hostname = null)
    {
        $error = null;
        $error = $error ?: $this->ValidateProtocol();
        $error = $error ?: $this->ValidateRemoteHost($hostname);
        $error = $error ?: $this->ValidateRequestMethod($requiredRequestMethod);
        return $error;
    }

    public function ValidateRequiredParameter($parameterName, $parameterLocation, $parameterType = null)
    {
        if (!$this->request->is_set($parameterName, $parameterLocation))
        {
            $requiredLocation = "";
            if ($parameterLocation == request_interface::GET)
            {
                $requiredLocation = 'url query string';
            }
            else if ($parameterLocation == request_interface::POST)
            {
                $requiredLocation = 'POST body';
            }

			return new ErrorResponse("INVALID_REQUEST", "Required parameter '{$parameterName}' missing from {$requiredLocation}.", 400);
        }

        if ($parameterType != null)
        {
            // TODO: Validate parameter type
        }

        return null;
    }    

    private function ValidateProtocol()
    {
		if (!$this->request->is_secure())
		{
			return new ErrorResponse("INVALID_PROTOCOL", "This api requires the use of HTTPS.", 403);
		}

		return null;
    }

    private function ValidateRemoteHost($hostname = null)
    {
        $isValidRemoteHost = false;
        if ($hostname == null && $this->request->server('REMOTE_HOST') === 'localhost')
        {
            $isValidRemoteHost = true;
        }
        elseif ($hostname != null)
        {
            $isValidRemoteHost = $this->request->server('REMOTE_HOST') === $hostname;
        }

        if (!$isValidRemoteHost)
        {
            return new ErrorResponse('InvalidRemoteHost', "Remote host is not allowed to use the api.", 403);
        }

        return null;
    }

    private function ValidateRequestMethod($requiredRequestMethod)
    {
        if ($this->request->server('REQUEST_METHOD') !== $requiredRequestMethod)
        {
			return new ErrorResponse("INVALID_REQUEST", "This api call requires using {$requiredRequestMethod} requests.", 400);
        }

        return null;
    }
}