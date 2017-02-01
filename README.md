![Rad Logo](http://austinbillings.com/projects/Rad.png)

_A PHP nanoframework for building APIs at light speed._

## Documentation

Rad provides the following basic classes to get you started building an API.

#### `Rad\Base`

A base class that does a whole lot of stuff I don't have time to write about.

#### `Rad\Router`
A basic but robust router, which takes a HTTP-Request-URI as the constructor argument: `__construct($route)`.

Provides various methods to perform actions based on the given URI.

Example:

```php
$router = Rad\Router($currentURI);
$entity = Rad\Base;

$router->get('/users', function ($params, $query) {
	$entity->deliver($userClass->getList());
});

$router->get('/users/:userID', function ($params, $query) {
	$entity->deliver($userClass->getUser($params["userID"]));
});
```

#### `Rad\Controller`

A class which provides a basic API controller. Automatically gathers data sent to `php://input` as `$this->input`, and other cool things I'll write about sooner or later.

#### `Rad\Tools`

A class which provides a collection of commonly-used (in my experience) utilities, all accessible as static methods.

Recommended usage:
```php
use Rad\Tools as Tools;

class MyClass {
	public function example () {
		echo Tools::firstName('Austin Billings');
		# Returns "Austin"
	}
}
```

See more [at their documentation](https://github.com/austinbillings/Rad/blob/master/doc/Rad\Tools.md).

### Rad\Courier

A class which extends `Rad\Base` and lets you easily prepare and send email server-side via **Sendgrid**, **Mandrill**, **Amazon SES**, or vanilla PHP with little to zero effort.

#### Quick CLI Example (can easily also be used as a web service)

From terminal:
```bash
composer require austinbillings/rad
touch MySendScript.php
```

Now, the fun part:

```php
<?php 
# MySendScript.php

require('./vendor/autoload.php');

# $blast is the HTML content of our email message
# As long as the first character of the HTML is '<',
# a plaintext version is automatically generated and sent as well.
#
# It can be specified separately by the #setMessage() function,
# or by using an associative array here, like this:
#
# $blast = ["text" => $myTextMessage, "html" => $myHtmlVersion];
#
$blast = file_get_contents(__DIR__ . '/blast.html');
echo "Blast size is ".strlen($blast)." chars\n\n";

# Let's use dat Courier.
$sender = new Rad\Courier([
  "system" => "SendGrid", # case insensitive, accepts sendgrid, mandrill, ses, defaults to PHP
  "sendGridKey"=> "SG.abcdefghijklm_abcdefgh.1-gabby0123456789abcdefghijklmnopqrstuvwxyz",
  
  "to" => $someListOfEmails, # You can use a string OR an array here
  "from" => "austin@awesome-marketing.net", # Address of the SENDER
  "name" => "GroundUP Music Festival", # Name of the SENDER
  
  "subject" => "GroundUp Music Festival lands on Miami Beach next week!",
]);

$sender->setMessage($blast);

# ->send() synchronously returns a boolean: true if all sent, false if any failure.
echo ($sender->send() ? 'Sent successfully!' : 'Send failed.')."\n";

# ----------------------------------------------------------------------
#                       yeah, it's that easy!
# ----------------------------------------------------------------------

```

Then, simply run your script to send out the email blast.
```bash
php MySendScript.php
> Blast size is 3175 chars
>
> Sent successfully!
```

Nice!