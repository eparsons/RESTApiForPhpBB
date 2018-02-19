<?php
/**
 *
 * @package phpBB Extension - eparsons/restapi
 * @copyright (c) 2018 Eric Parsons
 * @license https://opensource.org/licenses/GPL-2.0 GNU General Public License v2
 *
 */
namespace eparsons\restapi\event;

use eparsons\restapi\controller\users\Users;
use phpbb\config\config;
use phpbb\request\request_interface;
use phpbb\user;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class event_listener implements EventSubscriberInterface
{
    /**
     * phpBB user
     *
     * @var \phpbb\user 
     */
    private $user;

    /**
	* phpBB request
	*
	* @var request_interface
	*/
	private $request;


    /**
     * phpBB request
     *
     * @var config
     */
    private $config;

    /**
     * Constructor for listener
     *
     * @param request_interface $request Request interface
     * @param user $user
     * @param config $config
     * @access public
     */
    public function __construct(request_interface $request, user $user, config $config)
    {
        $this->request      = $request;
        $this->user         = $user;
        $this->config       = $config;
    }

    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     * @static
     * @access public
     */
    static public function getSubscribedEvents()
    {
        return array(
            'core.session_set_custom_ban' => 'handle_banned_user',
            'core.user_setup_after' => 'handle_user_setup_after_event'
        );
    }

    /**
     * Check if banned user is calling into REST API.  If so, we do not want the phpBB ban page to be
     * rendered instead of a JSON response.
     *
     * @param object $event The event object
     * @return null
     * @access public
     */
    public function handle_banned_user($event)
    {
        if ($this->in_rest_api())
        {
            $event['return'] = true;
            $this->user->data['is_banned'] = $event['banned'];
        }
    }

    /**
     * Check if user has pending password change and update user data accordingly
     *
     * @param $event
     */
    public function handle_user_setup_after_event($event)
    {
        // If we are in the rest api we need to check if a password change is needed and if so, prevent phpbb's redirect
        if ($this->in_rest_api() && !isset($this->user->data['mustChangePassword']))
        {
            $this->user->data['isPasswordChangeNeeded'] = Users::isPasswordChangeNeeded($this->user, $this->config);

            // Set current time as last password change time to prevent phpbb from redirecting to password change page.
            $this->user->data['user_passchg'] = time();
        }
    }

    /**
     * Indicates that a rest api controller defined by this extension is being called for the current request.
     *
     * @return bool
     */
    private function in_rest_api()
    {
        return strncmp($this->request->server('PATH_INFO'), '/restApiV', 9) == 0;
    }
}