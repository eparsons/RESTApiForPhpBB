<?php
/**
*
* @package phpBB Extension - eparsons/restapi
* @copyright (c) 2018 Eric Parsons
* @license https://opensource.org/licenses/GPL-2.0 GNU General Public License v2
*
*/
namespace eparsons\restapi\controller\auth;

use eparsons\restapi\controller\users\Users;
use eparsons\restapi\ErrorResponse;
use eparsons\restapi\Validation;
use phpbb\config\config;
use phpbb\request\request_interface;
use phpbb\auth\auth;
use phpbb\request\type_cast_helper;
use phpbb\user;
use Symfony\Component\HttpFoundation\JsonResponse;

if (!defined('IN_PHPBB'))
{
	exit();
}

class Authentication
{
	/**
	* phpBB request
	*
	* @var request_interface
	*/
	private $request;

	/**
	* phpBB auth
	*
	* @var auth
	*/
	private $auth;

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
	 * phpBB request
	 *
	 * @var config
	 */
	private $config;

	/**
	 * Constructor
	 *
	 * @param request_interface $request
	 * @param auth $auth
	 * @param user $user
	 * @param Validation $validation
	 */
	public function __construct(request_interface $request, auth $auth, user $user, Validation $validation, config $config)
	{
		$this->request = $request;
		$this->auth = $auth;
		$this->user = $user;
		$this->validation = $validation;
		$this->config = $config;
	}

	/**
	* Handler for /api/login POST requests
	*
	* @return JsonResponse A Symfony JsonResponse object
	*/
	public function login()
	{
		// Force use of POST
		$error = $this->validation->ValidateRequest('POST');
		$error = $error ?: $this->validation->ValidateRequiredParameter('username', request_interface::POST);
		$error = $error ?: $this->validation->ValidateRequiredParameter('password', request_interface::POST);
		if ($error != null)
		{
			return $error;
		}

		// Make sure user->setup() has been called
		if (!$this->user->is_setup())
		{
			$this->user->setup();
		}

		if ($this->user->data['is_registered'] == true) {
			return new ErrorResponse("ALREADY_LOGGED_IN", "You are already logged in.", 400);
		}

		$username = $this->request->variable('username', '', true);
		$password = $this->untrimmed_variable('password', '', true);
		$persistLogin = (bool)$this->request->variable('persistLogin', false);

		$auth_result = $this->auth->login($username, $password, $persistLogin);

		switch ($auth_result['status'])
		{
			case LOGIN_SUCCESS:
				$response['isRegistered'] = $this->user->data['is_registered'];
				$response['isBanned'] = isset($this->user->data['is_banned']) ? $this->user->data['is_banned'] : false;
				$response['isPasswordChangeNeeded'] = Users::isPasswordChangeNeeded($this->user, $this->config);
				$response['userId'] = (int)$this->user->data['user_id'];
				$response['userName'] = $this->user->data['username'];

				return new JsonResponse($response, 200);
			
			case LOGIN_ERROR_USERNAME:
			case LOGIN_ERROR_PASSWORD:
				return new ErrorResponse("INVALID_CREDENTIALS", "Invalid username or password specified.", 401);
			
			case LOGIN_ERROR_ATTEMPTS:
				return new ErrorResponse("UNAUTHORIZED", "Too many incorrect attempts.  Please login via the forum and complete the captcha to continue.", 401);

			case LOGIN_ERROR_ACTIVE:
				return new ErrorResponse("INACTIVE_ACCOUNT", "Account must be activated before logging in.", 401);
			
			default:
				// TODO: Add logging and give client a more vague error message.
				return new ErrorResponse("UNEXPECTED_AUTH_STATE", "Unexpected auth status: {$auth_result['status']}.", 500);
		}
	}

	/**
	* Handler for /api/logout POST requests
	*
	* @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	*/
	public function logout()
	{
		// Force use of POST
		$error = $this->validation->ValidateRequest('POST');
		if ($error != null)
		{
			return $error;
		}

		$this->user->session_kill();

		$response = array();

		$response['isRegistered'] = $this->user->data['is_registered'];
		$response['isBanned'] = isset($this->user->data['is_banned']) ? $this->user->data['is_banned'] : false;
		$response['isPasswordChangeNeeded'] = Users::isPasswordChangeNeeded($this->user, $this->config);
		$response['userId'] = (int)$this->user->data['user_id'];
		$response['userName'] = $this->user->data['username'];

		return new JsonResponse($response, 200);
	}


	/**
	 * This method is needed for getting untrimmed passwords because the method untrimmed_variable is not exposed on
	 * the request_interface
	 *
	 * @param $var_name
	 * @param $default
	 * @param bool $multibyte
	 * @param int $super_global
	 * @return mixed
	 */
	private function untrimmed_variable($var_name, $default, $multibyte = false, $super_global = request_interface::REQUEST)
	{
		return $this->_variable($var_name, $default, $multibyte, $super_global, false);
	}

	/**
	 * This method is needed for getting untrimmed passwords because the method untrimmed_variable is not exposed on
	 * the request_interface
	 *
	 * @param $var_name
	 * @param $default
	 * @param bool $multibyte
	 * @param int $super_global
	 * @param bool $trim
	 * @return mixed
	 */
	private function _variable($var_name, $default, $multibyte = false, $super_global = request_interface::REQUEST, $trim = true)
	{
		$var = $this->request->raw_variable($var_name, $default, $super_global);

		// Return prematurely if raw variable is empty array or the same as
		// the default. Using strict comparison to ensure that one can't
		// prevent proper type checking on any input variable
		if ($var === array() || $var === $default)
		{
			return $var;
		}

		$type_cast_helper = new type_cast_helper();
		$type_cast_helper->recursive_set_var($var, $default, $multibyte, $trim);

		return $var;
	}
}

