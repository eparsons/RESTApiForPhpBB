# RESTApiForPhpBB
An unofficial REST API for [PhpBB](https://www.phpbb.com/) with example client.

### Licenses:
* PhpBB extension: GPLv2
* Example client: MIT

### Preamble
Project is provided as-is.  It was built to support a particular website and will not see regular updates.  If you want to try to add this to the PhpBB extension database, add new features, or fix integration with future versions of PhpBB, please feel free to clone the repo and do with it as you please.

### What this project intended to solve
* Single sign-on with PhpBB
* Auth integration with sites not written in PHP.
* Avoid conflicts when PhpBB and the integrated site use different versions of Symfony components.

### What this project does not intend to solve
* It is not a replacement for whatever PhpBB ultimately releases for their [REST API](https://wiki.phpbb.com/Proposed_REST_API).  It is just a workaround until PhpBB releases something that can allow for integrating with their auth system more cleanly.
* It does not try to do more than was needed for the website I help with:
  * It is currently meant to be accessed via localhost:443, and so some security features that you find in some public apis may be missing.
  * It doesn't alter the way authentication works.  It mearly exposes PhpBB's existing cookie-based auth system via a json interface.
* It does not try to qualify for upload to the PhpBB extension database. I did my best to to adhere to the [extension guidelines](https://www.phpbb.com/extensions/rules-and-policies/validation-policy/), but in the end there are some issues I couldn't avoid such as _"15. login_forum_box() or login_box() is used for login."_ for obvious reasons.
  
### Known Issues
* None at this time, but there are likely some which exist.

### How to install
Extension:
1) Install the extension as you would [any other extension](https://www.phpbb.com/extensions/installing/).

Example client:
1) Copy the client files to a directory on your server
2) [Install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
3) [Use composer](https://getcomposer.org/doc/01-basic-usage.md#installing-dependencies) to download the GuzzleHttp dependency
    * Command will look something like "php composer.phar install" and needs to be run from the directory with the composer.json file.

### Using the API
The root path of the API will be:  https://\<hostname\>\<forum path\>/app.php/restApiV1/  If you have url rewriting enabled (in General->Server Settings of the control panel) and working for your forum, the path can be shortened to https://\<hostname\>\<forum path\>/restApiV1/

NOTE: Due to PhpBB's cookie based auth, you will need to handle cookies passed to and returned by the api.  You shouldn't directly store these.  Your client should act as a proxy for these cookies.  I recommend looking at the example client and reusing the code if you are working with PHP on the client side.

### Logging in

**Request**

Request Property | Value
--- | ---
method | POST
url | \<apiRoot\>/login
Parameters | Current cookies and the following form parameters in the POST body:<ul><li>**'username'** : (string) The user's name</li><li>**'password'** : (string) The user's password</li><li>**'persistLogin'** : (boolean) Flag indicating if inactivity should cause the user to be logged out.</li></ul>

**Response**

401 status code on auth failure, otherwise user data json for logged in user. (See [current user API](#current-user))

### Logging out

**Request**

Request Property | Value
--- | ---
method | POST
url | \<apiRoot\>/logout
parameters | Current cookies

**Response**

User data json for anonymous user. (See [current user API](#current-user))

### Current user

**Request**

Request Property | Value
--- | ---
method | GET
url | \<apiRoot\>/users/me
parameters | Current cookies

**Resonse** 

Received cookies should be proxied to browser

JSON:
```javascript
{
    "isRegistered":true,
    "isBanned":true,
    "isPasswordChangeNeeded":true,
    "userId":15,
    "userName":"Banned"
}
```
Field | Value
------------ | -------------
**isRegistered** | Will be true if the user is logged in.
**isBanned** | True if user is banned from forum.
**isPasswordChangeNeeded** | If true, you should notify or redirect user to the forum's user control panel so they can change their password.
**userId** | The user's forum id
**userName** | The user's user name.
