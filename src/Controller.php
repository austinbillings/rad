<?php

namespace Rad;

class Controller extends Base
{
	private $input = [];
	public $route;
	public $method;

	//////////////////////////////////////////////////////////////////////////////

	function __construct ($routePrefix = null) {
		// Setup
		parent::__construct();
		$this->input = json_decode(file_get_contents('php://input'),true);
		if (!empty($_SERVER["HTTP_REFERER"])) {
			self::$meta["referrer"] = $_SERVER["HTTP_REFERER"];
		}

		$route = substr($_SERVER["REQUEST_URI"], (is_string($routePrefix) ? strlen($routePrefix) : 0));
		$this->route = new Router($route);
	}

	public function serve () {
		$this->route->otherwise(function () {
			$this->deliver();
		});
	}
}
