<?php

namespace Rad;

class Router
{
	public $currentRoute;
	public $currentMethod;
	public $queryString;
	public $query = [];
	public $completed = false;

	function __construct ($route = null) {
	  $this->currentRoute = $route;

	  $q = strpos($route, '?');
	  $this->currentRoute = ($q !== false ? substr($route, 0, $q) : $route);
	  $this->currentMethod = strtolower($_SERVER["REQUEST_METHOD"]);

		if ($q !== false) {
			$this->query = Tools::parseQuery(substr($route, $q+1));
		}
	}

	public function get ($route, $callback) {
	  if ($this->currentMethod !== "get") { return; }
	  $this->run($route, $callback);
	}

	public function post ($route, $callback) {
	  if ($this->currentMethod !== "post") { return; }
	  $this->run($route, $callback);
	}

	public function put ($route, $callback) {
	  if ($this->currentMethod !== "put") { return; }
	  $this->run($route, $callback);
	}

	public function patch ($route, $callback) {
	  if ($this->currentMethod !== "patch") { return; }
	  $this->run($route, $callback);
	}

	public function delete ($route, $callback) {
	  if ($this->currentMethod !== "delete") { return; }
	  $this->run($route, $callback);
	}

	public function otherwise ($callback) {
	  if (!$this->completed) {
	    $callback($this->query);
	  }
	}

	public function segmentPath ($route) {
	  $route = (substr($route,0,1) === "/" ? substr($route, 1) : $route);
	  $route = (substr($route,-1) === "/" ? substr($route,0,-1) : $route);
	  return explode('/', substr($route, 1));
	}

	public function run ($route, $callback) {
	  $params = [];
	  $currentBits = $this->segmentPath($this->currentRoute);
	  $targetBits = $this->segmentPath($route);

	  if (count($currentBits) !== count($targetBits)) {
	    return;
	  }

	  foreach ($targetBits as $idx => $bit) {
	    if (substr($bit,0,1) === ":" && !empty($currentBits[$idx])) {
	      # it's a param
	      $paramName = substr($bit,1);
	      $params[$paramName] = $currentBits[$idx];
	    } else {
	      # it's not
	      if ($currentBits[$idx] !== $bit) {
	        return;
	      }
	    }
	  }

	  # add query string vars to params array
	  if (!empty($this->query)) {
	    foreach ($this->query as $key => $val) {
	      $params[$key] = $val;
	    }
	  }

	  $this->completed = true;
	  $callback($params, $this->query);
	}
}
