# RESTApiForPhpBB
An unofficial REST API for PhpBB with example client.

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
  * You will find that it doesn't protect against replay attacks or other things to be concerned about when creating an API.  It was originally designed to be accessed by a site that resides on the same box, so it currently checks that the remote host is localhost and that https is being used.  If you want to integrate the API with an off server client such as a mobile app, you will need to handle these issues yourself.
  * It doesn't alter the way authentication works.  It mearly exposes the PhpBB's existing cookie-based auth system via a json interface.
  * Only a handful of user properties are exposed in the interface.  If you need more you will want to fork the repo and add them.
  * It only handles auth and getting some basic details about the currently logged-in user.
* It does not try to be qualify for upload to the PhpBB extension database. I did my best to to adhere to the [extension guidelines](https://www.phpbb.com/extensions/rules-and-policies/validation-policy/), but in the end there are some issues I couldn't avoid:
  * _"15. login_forum_box() or login_box() is used for login."_ is obviously not a reasonable restriction for an JSON API.
  * _"For privacy reasons it is not allowed to send private information (including but not limited to posts, user information, etc.) to any remote website or remote server. Any extension that does send information to a remote website or remote server will be denied for this reason. Exceptions to this rule, although rare, will be handled on a case-by-case basis."_ 
  The extension doesn't phone home to a remote server somewhere, but it is giving user data to a requesting client due to the nature of being an API.  Probably could get an exception for this.
  * It isn't explicitly called out as an issue that would block me, but I had to instantiate an instance of type_cast_helper due to the absence of untrimmed_variable() on the request interface.  This seems likely to be a place in the code to break in the future.

#### To any PhpBB devs who may be reading this
* Consider exposing untrimmed_variable on request_interface.  Using login_box() is not an option and this method is needed to work with login info.
* Consider giving extensions the option of preventing PhpBB's redirects when the user is banned or has an expired password.  Returning non-JSON data breaks APIs.  (I was able to workaround this but it could have been handled much more cleanly/robustly.)

### Known Issues
* None at this time, but there are likely to exist some.

### How to install
Extension:
1) Install the extension as you would [any other extension](https://www.phpbb.com/extensions/installing/).

Example client:
1) Copy the client files to a directory on your server
2) [Install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
3) [Use composer](https://getcomposer.org/doc/01-basic-usage.md#installing-dependencies) to download the GuzzleHttp dependency
    * Command will look something like "php composer.phar install" and needs to be run from the directory with the composer.json file.

### Using the API
The root path of the API will be:  https://\<hostname\>\<forum path\>/app.php/restApiV1/  If you have url rewriting enabled (in General->Server Settings of the control panel) and working for your forum, the path can be shortened to https://<hostname><forum path>/restApiV1/

NOTE: Due to PhpBB's cookie based auth, you will need to handle cookies passed to and returned by the api.  You shouldn't directly store these.  Your client should act as a proxy for these cookies.  I recommend looking at the example client and reusing the code if you are working with PHP on the client side.

#### Logging in

**Request**

method: POST

url: \<apiRoot\>/login

Parameters:

Current cookies and the following form parameters in the POST body:

'username' : (string) The user's name

'password' : (string) The user's password

'persistLogin' : (boolean) Flag indicating if inactivity should cause the user to be logged out.


**Response**

401 status code on auth failure, otherwise user data json for logged in user. (See current user API)

### Logging out

**Request**

method: POST

url: \<apiRoot\>/logout

parameters: Current cookies


**Response**

User data json for anonymous user. (See current user API)

### Current user

**Request**

method: GET

url: \<apiRoot\>/users/me

parameters: Current cookies


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
