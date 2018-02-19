<?php
/**
*
* @package Example REST client for the eparsons/restapi unofficial PhpBB extension
* @copyright (c) 2018 Eric Parsons
* @license https://opensource.org/licenses/MIT MIT License
*
*/
namespace RestClientForPhpbb;

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

class ForumUserData
{
    /**
    * @var int
    */
    private $userId;

    /**
    * @var bool
    */
    private $isRegistered;

    /**
     * @var bool
     */
    private $isBanned;

    /**
     * @var bool
     */
    private $isPasswordChangeNeeded;

    /**
     * @param $userId int
     * @param $isRegistered bool
     * @param $isBanned
     * @param $isPasswordChangeNeeded
     */
    public function __construct($userId, $isRegistered, $isBanned, $isPasswordChangeNeeded)
    {
        $this->userId = (int)$userId;
        $this->isRegistered = $isRegistered;
        $this->isBanned = $isBanned;
        $this->isPasswordChangeNeeded = $isPasswordChangeNeeded;
    }

    /**
    * @return int Id of the user
    */
    public function getId()
    {
        return $this->userId;
    }

    /**
    * @return bool True if user is registered, false otherwise
    */
    public function isRegistered()
    {
        return $this->isRegistered;
    }

    /**
     * @return bool True if user is banned, false otherwise
     */
    public function isBanned()
    {
        return $this->isBanned;
    }

    /**
     * @return bool True if user needs to change their password, false otherwise
     */
    public function isPasswordChangeNeeded()
    {
        return $this->isPasswordChangeNeeded;
    }
}

class Forum {

    // Note: If you have url rewriting working in your forum you can use /forum/restApiV1/ instead.
    private $apiPath = "https://www.forum.example/forum/app.php/restApiV1/";
    private $cookieDomain = '.forum.example';
    private $userData = NULL;

    /**
     * Gets basic information about the current user.
     *
     * @return ForumUserData Data about the current user.
     */
    public function getCurrentUser()
    {
        if ($this->userData != null)
        {
            return $this->userData;
        }

        $url = $this->apiPath . 'users/me';
        $client = new Client();
        $response = $client->request(
            'GET',
            $url, 
            [
                'cookies' => $this->getCookieJar(),
                'headers' => ['User-Agent' => strtolower($_SERVER['HTTP_USER_AGENT'])],
            ]
        );

        $this->applyCookies($response);
        $userData = json_decode($response->getBody());

        $this->userData = new ForumUserData(
            $userData->userId,
            $userData->isRegistered,
            $userData->isBanned,
            $userData->isPasswordChangeNeeded);

        return $this->userData;
    }

    /**
     * Logs a user in given their credentials and returns updated information about the user.
     *
     * @param string $username Username
     * @param string $password Password
     * @param bool $autoLogin Whether or not the user should stay logged in after being inactive for an extended time
     * @return ForumUserData Data about the current user.
     */
    public function login($username, $password, $autoLogin = false)
    {
        // 1 = Anonymous user
        if ($this->userData != null && $this->userData->getId() != 1)
        {
            return $this->userData;
        }

        $url = $this->apiPath . 'login';
        $client = new Client();

        $response = null;
        try 
        {
            $response = $client->request('POST', $url, [
                'cookies' => $this->getCookieJar(),
                'headers' => [
                    'User-Agent' => strtolower($_SERVER['HTTP_USER_AGENT']),
                ],
                'form_params' => [
                    'username' => $username,
                    'password' => $password,
                    'persistLogin' => $autoLogin,
                ],
            ]);
    
            $this->applyCookies($response);
    
            $userData = json_decode($response->getBody());
            $this->userData = new ForumUserData(
                $userData->userId,
                $userData->isRegistered,
                $userData->isBanned,
                $userData->isPasswordChangeNeeded);
        }
        catch (RequestException $requestException)
        {
            $this->userData = null;
            if ($requestException->getResponse()->getStatusCode() != 401)
            {
                throw $requestException;
            }

            $this->userData = new ForumUserData(1, false, false, false);
        }

        return $this->userData;
    }

    /**
     * Logs the user out by killing their session
     */
    public function logout()
    {
        $url = $this->apiPath . 'logout';
        $client = new Client();
        $response = $client->request('POST', $url, [
            'cookies' => $this->getCookieJar(),
            'headers' => [
                'User-Agent' => strtolower($_SERVER['HTTP_USER_AGENT']),
            ],
        ]);

        $this->applyCookies($response);
        $userData = json_decode($response->getBody());
        $this->userData = new ForumUserData(
            $userData->userId,
            $userData->isRegistered,
            $userData->isBanned,
            $userData->isPasswordChangeNeeded);

	    return $this->userData;
    }

    /**
     * Grabs the current user's cookies and adds them to jar for API calls so client can act on behalf of user.
     * @return CookieJar Guzzle's populated cookie jar
     */
    private function getCookieJar()
    {
        $jar = new CookieJar();
        foreach ($_COOKIE as $key => $value)
        {
            if ($value != "")
            {
                $cookie = SetCookie::fromString("$key=$value");
                $cookie->setDomain($this->cookieDomain);
                $jar->setCookie($cookie);
            }
        }
        
        return $jar;
    }

    /**
     * Apply cookies returned by the Api to the browser
     * @param Response $response Guzzle response object
     */
    private function applyCookies(Response $response)
    {
        $headers = $response->getHeaders();
        if (isset($headers['Set-Cookie']))
        {
            foreach ($headers['Set-Cookie'] as $cookieString)
            {
                header('Set-Cookie: ' . $cookieString);
            }
        }
    }
}

