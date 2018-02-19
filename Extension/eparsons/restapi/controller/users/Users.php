<?php
/**
*
* @package phpBB Extension - eparsons/restapi
* @copyright (c) 2018 Eric Parsons
* @license https://opensource.org/licenses/GPL-2.0 GNU General Public License v2
*
*/
namespace eparsons\restapi\controller\users;

use eparsons\restapi\ErrorResponse;
use eparsons\restapi\Validation;
use phpbb\config\config;
use phpbb\user;
use Symfony\Component\HttpFoundation\JsonResponse;

if (!defined('IN_PHPBB'))
{
	exit();
}

class Users
{
	/**
	* phpBB user
	*
	* @var user
	*/
	private $user;

	/**
	* REST API validator
	*
	* @var Validation
	*/
	private $validation;

	/**
	 * Constructor
	 *
	 * @param user $user
	 * @param Validation $validation
	 */
	public function __construct(user $user, Validation $validation)
	{
		$this->user = $user;
		$this->validation = $validation;
	}

	/**
	* Handler for /api/users/me GET requests
	*
	* @return JsonResponse A Symfony Response object
	*/
	public function whoAmI()
	{
		$errorResponse = $this->validation->ValidateRequest('GET');
		if ($errorResponse != null)
		{
			return $errorResponse;
		}

		$response = array();
		$response['isRegistered'] = $this->user->data['is_registered'];
		$response['isBanned'] = isset($this->user->data['is_banned']) ? $this->user->data['is_banned'] : false;
		$response['isPasswordChangeNeeded'] = isset($this->user->data['isPasswordChangeNeeded']) ? $this->user->data['isPasswordChangeNeeded'] : false;
		$response['userId'] = (int)$this->user->data['user_id'];
		$response['userName'] = $this->user->data['username'];

		return new JsonResponse($response, 200);
	}
	
	/**
	* Handler for /api/users/{userId} GET requests
	*
	* @return JsonResponse A Symfony Response object
	*/
	public function user($userId)
	{
		$errorResponse = $this->validation->ValidateRequest('GET');
		if ($errorResponse != null)
		{
			return $errorResponse;
		}

		return new ErrorResponse("NotImplemented", "This api is not yet implemented.", 400);
    }

	public static function isPasswordChangeNeeded(user $user, config $config)
	{
		return !empty($user->data['is_registered'])
			&& $user->data['user_passchg'] < time() - ($config['chg_passforce'] * 86400);
	}
}