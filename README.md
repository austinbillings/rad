# Rad

_A PHP nanoframework for building APIs at light speed._

## Classes

Rad provides the following basic classes to get you started building an API that does whatever you want.

### Rad\Base

A base class that does a whole lot of stuff I don't have time to write about.

### Rad\Router

A basic but robust router, which takes a HTTP-Request-URI as the constructor argument: `__construct($route)`.

Provides various methods to perform actions based on the given URI.

Example:

	$router = Rad\Router($currentURI);
	$entity = Rad\Base;

	$router->get('/users', function ($params, $query) {
		$entity->deliver($userClass->getList());
	});

	$router->get('/users/:userID', function ($params, $query) {
		$entity->deliver($userClass->getUser($params["userID"]));
	});

### Rad\Controller

A class which provides a basic API controller. Automatically gathers data sent to `php://input` as `$this->input`. Automatically instantiates a `Rad\Router` as `$this->route` with the current request URI as .

### Rad\Tools

A class which provides a collection of commonly-used (in my experience) utilities, all accessible as static methods.

Recommended usage:

	use Rad\Tools as Tools;

	class MyClass {
		public function example () {
			echo Tools::firstname('Austin Billings');
			# Returns "Austin"
		}
	}


### Rad\Courier

A class which extends `Rad\Base` and provides tools for preparing and sending email server-side.
