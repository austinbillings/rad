<?php

namespace Rad;

class Auth extends Base
{
	public $users;
	public $idKey;
	public $tests;
	protected $signatureKey;

	public function __construct ($key = null) {
		parent::__construct();
		$this->user = [];
		$this->users = [];
		$this->idKey = "id";
		$this->tests = [
			"email" => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
			"name" => '/^[a-zA-Z ]{1,40}$/',
			"pass" => '/^[a-zA-Z0-9!@#`~%^&_-]{8,24}$/'
		];
		if (!is_null($key)) {
			$this->signatureKey = $key;
		}
	}

	/*----------------------------------------------------------------------------
	// Getters & Setters
	----------------------------------------------------------------------------*/

	public function getIdKey () {
		return $this->idKey;
	}
	public function setIdKey ($key) {
		if (!is_string($key)) {
			$this->warn("Attempted to set non-string as ID Key.");
			return false;
		} else {
			$this->idKey = $key;
			return $this->idKey;
		}
	}

	public function getUsers () {
		return $this->users;
	}
	public function setUsers ($collection) {
		if (!is_array($collection)) {
			$this->warn("Attempted to set non-array as users collection.");
			return false;
		} else {
			$this->users = $collection;
			return $this->users;
		}
	}
	public function getUser ($id) {
		if (empty($this->getUsers)) return false;
		$user = Tools::wherein($this->users, $this->idKey, $id);
		if ($user === false) return false;
		return $user;
	}

	/*----------------------------------------------------------------------------
	// JSON WEB TOKENS
	----------------------------------------------------------------------------*/

	public function buildToken ($options, $claims) {
		if (empty($this->signatureKey)) $this->rage("Can't create JWT: no signature key provided.");

		$config = new \Lcobucci\JWT\Builder;
		$signer = new \Lcobucci\JWT\Signer\Hmac\Sha256;
		$reservedClaims = ["iss","aud","jti","iat","nbf","exp"];

		$config
		->setIssuer(Tools::getSiteURL())
		->setIssuedAt(time())
		->setExpiration(time() + 10800);
		foreach ($options as $key => $val) {
			switch (strtolower($key)) {
				case "expiration":
				case "expires":
				case "expire":
				case "exp":
					if (is_numeric($val)) $config->setExpiration(intval($val));
					break;
				case "notbefore":
				case "start":
				case "nbf":
					if (is_numeric($val)) $config->setNotBefore(intval($val));
					break;
				case "audience":
				case "aud":
					if (is_string($val)) $config->setAudience($val);
					break;
			}
		}
		foreach ($claims as $key => $val) {
			if (!in_array(strtolower($key), $reservedClaims) && is_string($key)) $config->set($key, $val);
		}
		$token = $config->sign($signer, $this->signatureKey)->getToken();
		return $token;
	}

	public function verifyToken ($token) {
		if (empty($this->signatureKey)) $this->rage("Can't verify JWT: no signature key provided.");
		if (!is_string($token)) $this->rage("Can't verify JWT: non-string token provided.");
		$config = new \Lcobucci\JWT\Builder;
		$signer = new \Lcobucci\JWT\Signer\Hmac\Sha256;
		$inspector = new \Lcobucci\JWT\ValidationData;
		$parser = new \Lcobucci\JWT\Parser;
		$token = $parser->parse($token);
		$inspector->setIssuer(Tools::getSiteURL());
		return ($token->validate($inspector) && $token->verify($signer, $this->signatureKey));
	}

	/*----------------------------------------------------------------------------
	// JSON WEB TOKENS
	----------------------------------------------------------------------------*/

	public function test ($mode, $input, $confirm = null) {
		switch ($mode) {
			case "name":
			return (preg_match($this->tests["name"], $input) === 1);
			break;
			case "email":
			return (preg_match($this->tests["email"], $input) === 1);
			break;
			case "pass":
			return (preg_match($this->tests["pass"], $input) === 1);
			break;
			case "confirm":
			return ($input === $confirm);
			break;
			default:
			return false;
		}
	}
}
