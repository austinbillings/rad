<?php

namespace Rad;

class Tools
{
	/*============================================================
	// 	Names
	============================================================*/

	// Returns firstname from string
	public static function firstName ($input){
		if (strpos($input,' ') !== false){
			$nameArray = explode(' ', $input);
			return $nameArray[0];
		} else {
			return $input;
		}
	}

	// returns lastname from string
	public static function lastName ($input){
		if (strpos($input,' ') !== false) {
			$nameArray = explode(' ', $input);
			return $nameArray[1];
		} else {
			return $input;
		}
	}

	/*============================================================
	// 	Paths, URIs, and URLs
	============================================================*/

	public static function getSiteURL () {
		return "http" . (isset($_SERVER["HTTPS"]) ? "s" : "") . "://" . $_SERVER["HTTP_HOST"];
	}

	public static function getFullURL () {
		return static::getSiteUrl()."/".$_SERVER["REQUEST_URI"];
	}

	// if string contains period rtn true
	public static function containsPeriod($input) {
		return (is_string($input) && strpos($input, ".") !== false);
	}

	// returns just the file extension
	public static function getFileExtension($input) {
		return (static::containsPeriod($input) ? strtolower(substr($input, (strrpos($input, ".") + 1))) : false);
	}

	// returns everything up to file extension
	public static function removeFileExtension ($input) {
		return (static::containsPeriod($input) ? substr($input, 0, strrpos($input, ".")) : false);
	}

	public static function parseQuery ($queryString) {
		$queryArray = explode('&', $queryString);
		$output = [];

		foreach ($queryArray as $set) {
			$x = explode('=', $set);
			$key = $x[0];
			$val = (isset($x[1]) ? $x[1] : null);

			if ($val === "") $val = null;
			elseif ($val === "true") $val = true;
			elseif ($val === "false") $val = false;
			elseif (is_numeric($val)) $val = $val + 0;
			else $val = urldecode($val);

			if (!empty($key)) $output[$key] = $val;
		}
		return $output;
	}

	public static function sizeWriter ($bytes = null) {
		if (!is_numeric($bytes)) return "?";
		else $bytes = intval($bytes);

		if ($bytes <= (1024 * 1024) && $bytes >= 1024) {
			$div = $bytes / 1024;
			return number_format($div, 0) . " KB";
		} else if ($bytes >= (1024 * 1024) && $bytes < (1024 * 1024 * 1024)) {
			$div = $bytes / (1024 * 1024);
			return number_format($div, 1) . " MB";
		} else if ($bytes >= (1024 * 1024 * 1024)) {
			$div = $bytes / (1024 * 1024 * 1024);
			return number_format($div, 2) . " GB";
		} else {
			return $bytes . " B";
		}
	}

	public static function pathify ($path) {
		# strip slashes from ends
		$path = (substr($path, 0, 1) === "/" ? substr($path, 1) : $path);
		$path = (substr($path, -1) === "/" ? substr($path, 0, -1) : $path);
		# return exploded
		return explode("/", $path);
	}

	public static function filenameFromPath ($path, $force = false) {
		if (!$force && !static::containsPeriod($path)) {
			return false;
		}
		$pathParts = pathify($path);
		return $pathParts[count($pathParts) - 1];
	}

	public static function pathWithoutFilename ($path, $force = false) {
		if (static::containsPeriod($path) || $force) {
			$pathParts = static::pathify($path);
			array_pop($pathParts);
			return implode('/', $pathParts).'/';
		} else {
			return $path;
		}
	}

	public static function relativePath ($in, $out) {
		# arrays of dirnames
		$inParts = static::pathify($in);
		$outParts = static::pathify($out);
		# used to count directories to drill up and down
		$leadingDirectories = 0;
		$levelsUp = 0;
		foreach ($inParts as $index => $chunk) {
			if ($outParts[$index] === $chunk) {
				$leadingDirectories++;
			} else {
				if (strpos($chunk, '.') === false) {
					$levelsUp++;
				}
			}
		}
		$export = array_slice($outParts, $leadingDirectories);
		$prefix = ".";
		while ($levelsUp > 0) {
			$prefix .= "/..";
			$levelsUp--;
		}
		return $prefix . '/' . implode('/', $export);
	}

	public static function autopath () { // Util to automatically build a path from given args
		$pathParts = func_get_args();
		$output = "";
		$slash = DIRECTORY_SEPARATOR;
		foreach ($pathParts as $idx => $part) {
			if ($idx == 0 && substr($part, 0, 1) === "." && substr($part, 0, 2) === "./") {
				$output .= "." . $slash;
				$part = substr($part, 2);
			} elseif ($idx === 0 && strpos($part, 'http://') === 0) {
				$output .= "http://";
				$part = substr($part, 7);
			} elseif ($idx === 0 && strpos($part, 'https://') === 0) {
				$output .= "https://";
				$part = substr($part, 8);
			} elseif ($idx === 0 && strpos($part, ':') === 1) {
				$output .= "";
			} elseif (substr($output, -1) !== '/') {
				$output .= $slash;
			}
			if (substr($part, 0, 1) === "/") {
				$part = substr($part, 1);
			}
			if (substr($part, -1) === "/") {
				$part = substr($part, 0, -1);
			}
			$output .= $part;
		}
		return $output;
	}

	public static function slugify ($in, $spacer = '-', $maxLength = false) {
		$removals = str_split(" !@#$%^&*()_-`~+=[]{}|\\;:'\"<,>.?/");
		$out = str_replace($removals, $spacer, $in);
		$redundancy = 5;
		$redundants = [];
		$temp = $spacer;
		while (count($redundants) <= $redundancy) {
			$redundants[] = $temp;
			$temp .= $spacer;
		}
		$out = str_replace(array_reverse($redundants), $spacer, $out);
		$out = trim(strtolower($out), $spacer);
		if ($maxLength !== false && is_integer($maxLength)) {
			$out = trim(substr($out, 0, $maxLength), $spacer);
		}
		return $out;
	}

	public static function injectStyle ($styleObj, $useAttribute = true) {
		$out = "";
		if ($useAttribute) {
			$out .= "style=\"";
		};
		foreach ($styleObj as $prop => $val) {
			$out .= $prop . ":" . $val . ";";
		}
		if ($useAttribute) {
			$out .= "\"";
		}
		return $out;
	}

	public static function integrate ($coat, $base) {
		if (!is_array($coat) || !is_array($base)) {return false;}
		foreach ($coat as $key => $val) {
			$base[$key] = $val;
		}
		return $base;
	}

	public static function jsonify ($in) {
		return json_encode($in, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}

	// Cookies
	public static function cook ($name, $val, $expiration = null) {
		$expiration = ($expiration === null ? time() + (60 * 60 * 24) : $expiration);
		setcookie($name, $val, $expiration, '/');
	}

	public static function uncook ($name) {
		setcookie($name, '', 1, '/');
	}

	/*============================================================
	// 	Miscellaneous
	============================================================*/

	// Makes text more HTML-like.
	// TODO: Make this more comprehensive.
	public static function textToHtml ($text) {
		return str_replace("\n", "<br />", $text);
	}

	// ID generation (i.e. 57d5e285ba436d8cc7f55e85ff22fe59)
	public static function generateUniqueID ($length = 32) {
		return substr(md5(mt_rand(1,999999999)."_".mt_rand(1,999999999)), 0, $length);
	}

	// Like empty() but for an array of values
	public static function empties ($in) {
		if (empty($in)) { return true; }
		if (is_array($in)) {
			foreach ($in as $x) {
				if (empty($x)) { return true; }
			}
		} else {
			$collection = func_get_args();
			foreach ($collection as $x) {
				if(empty($x)) { return true; }
			}
		}
		return false;
	}

	// Pulls first object from a collection where obj.property === needle
	// returns the object, or false on err
	// ex.
	// 	$fruits = array(["name"=>"apple"],["name"=>"orange"]);
	// 	$apple = wherein ($fruits, "name", "apple");
	// 	$apple: {name: 'apple'}
	public static function wherein ($haystack, $property, $needle) {
		foreach ($haystack as $candidate) {
			if (isset($candidate[$property]) && $candidate[$property] === $needle) {
				return $candidate;
			}
		}
		return false;
	}

	// removes all normal space characters " "
	public static function despace ($input) {
		return implode('', explode(' ', $input));
	}

	// gets time of day, either from unix timestamp or current time
	// one of: morning, afternoon, evening, night
	public static function timeOfDay ($timestamp = null) {
		$x = intval(is_null($timestamp) ? date('G') : date('G', $timestamp));
		if ($x >= 4 && $x < 12) {
			return "morning";
		} elseif ($x < 17) {
			return "afternoon";
		} elseif ($x >= 17 && $x < 22) {
			return "evening";
		} elseif ($x < 4 || $x >= 22) {
			return "night";
		}
	}

	// returns index where $item[$property] === $needle or false if not found
	public static function getIndex($inputArray, $property, $needle){
		if (!is_array($inputArray) || count($inputArray) === 0) {
			return false;
		}
		foreach ($inputArray as $idx => $candidate) {
			if (is_array($candidate)) {
				if (!empty($candidate[$property]) && $candidate[$property] === $needle) {
					return $idx;
				}
			} elseif (is_object($candidate)) {
				foreach ($candidate as $key => $value) {
					if ($key === $property && $value === $needle) {
						return $idx;
					}
				}
			}
		}
		return false;
	}

	// Sorts by ["date"] property, newest first
	public static function sortByDate ($a, $b) {
		if (empty($a["date"]) || empty($b["date"])) return 0;
		if ($a["date"] === $b["date"]) return 0;
		return ($a["date"] < $b["date"] ? 1 : -1);
	}

	public static function sortBy ($property, $ascending = true) {
		return function ($a, $b) use ($property, $ascending) {
			if ($a[$property] === $b[$property]) return 0;
			$factor = (empty($ascending) ? 1 : -1);
			return $a[$property] < $b[$property] ? (1 * $factor) : (-1 * $factor);
		};
	}

	/*----------------------------------------------------
	#saltyHash()
	Generates auth-friendly, encrypted Creds

	Passing just $pass will return an object like this:
	@return: [
		"pass" => (base64 encoded, hashed, salted password),
		"salt" => (base64 encoded salt applied to password)
	]

	Passing $pass AND non-null (base64) $existingSalt will return a similar object:
	@return: [
		"pass" => (base64 encoded, hashed password salted with provided base64-encoded salt),
		"salt" => (the existing salt you passed, base64-encoded)
	]

	Passing $pass, $existingSalt, and a $mode will provide an object like the following:
	--------------------------
	// if	$mode is "pass":
	// @return: base64 encoded, hashed password, salted with $existingSalt (or new generated salt if $existingSalt is null)
	--------------------------
	// if $mode is "salt":
	// @return base64 encoded salt you sent as $existingSalt, unless $existingSalt was null, in which case it's a new generated salt
	--------------------------
	// if $mode is "obj" or anything else
	// @return object exactly as described above
	-------------------------

	Example Usage
	--------------------------
	Registering a user:
	// User provides password as $userPassword.
	// 	$creds = saltyHash($userPassword);
	// Save creds to user object on DB or whatever.
	--------------------------
	Checking a password:
	// User provides password as $userPassword.
	// You already have a saved $creds object attached to the user with pass and salt set at registration.
	// if (saltyHash($userPassword, $creds["salt"], "pass") === $creds["pass"]) {
	//  allowLogin();
	// } else {
	//  loginError();
	// }
	---------------------------------------------------- */
	public static function saltyHash ($pass, $existingSalt = null, $mode = 'obj') {
		$salt = (is_null($existingSalt) ? mcrypt_create_iv(22, MCRYPT_DEV_URANDOM) : base64_decode($existingSalt));
		$options = ['cost' => 11, 'salt' => $salt];
		$return = [
			"pass" => base64_encode(password_hash($pass, PASSWORD_DEFAULT, $options)),
			"salt" => (is_null($existingSalt) ? base64_encode($salt) : $salt)
		];
		switch($mode){
			case 'pass':
			return $return["pass"];
			break;
			case 'salt':
			return $return["salt"];
			break;
			case 'obj':
			default:
			return $return;
		}
	}

	/*============================================================
	// 	Peace, fam
	============================================================*/
}
