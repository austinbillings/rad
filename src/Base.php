<?php

namespace Rad;

class Base
{
	public static $meta = [];
	public static $warnings;
	public static $errorCode = 400;
	public $logFailures = false;
	public $verbose = false;

	public function __construct ($logFailures = null, $verbose = null) {
		static::$meta["timestamp"] = time();
		static::$meta["uri"] = $_SERVER["REQUEST_METHOD"] .' '. $_SERVER["REQUEST_URI"];
		static::$meta["requestID"] = Tools::generateUniqueID(12);
		if (!is_null($logFailures)) {
			$this->logFailures = $logFailures;
		}
		if (!is_null($verbose)) {
			$this->verbose = $verbose;
		}
		$this->checkQueryString();
	}

	public function setLogFailures ($val) {
		$this->logFailures = $val;
	}

	public function setVerbose ($val) {
		$this->verbose = $val;
		$this->checkQueryString();
	}

	public function checkQueryString () {
		$query = Tools::parseQuery($_SERVER['QUERY_STRING']);
		if (isset($query["envelope"])) {
			$this->verbose = $query["envelope"];
		}
	}

	//////////////////////
	//                  //
	//  Error Handling  //
	//                  //
	//////////////////////

	public function rage ($message = "An unknown error occured.", $code = null) {
		$e = [
			"success" => false,
			"error" => $message
		];

		$diagnostic = Tools::integrate($e, static::$meta);

		if ($this->logFailures) { error_log(Tools::jsonify($diagnostic)); }
		if (!is_null($code)) { static::$errorCode = $code; }

		http_response_code(static::$errorCode);

		if ($this->verbose) {
			$this->render($diagnostic);
		} else {
			$this->deliver($e);
		}
	}

	public function warn ($message) {
		if (!empty($message)) {
			if (empty(static::$warnings)) {
				static::$warnings = [];
			}
			static::$warnings[] = $message;
		}
	}


	//////////////////////
	//                  //
	//      UTILS       //
	//                  //
	//////////////////////

	// CSS Preprocessors
	public function toCSS ($filePath, $mode = "less") {
		if (empty($filePath)) {
			$this->rage("No target file specified.");
		} elseif (!file_exists($filePath)) {
			$this->rage("Target file not found: '{$filePath}'.");
		}
		$compiler = null;
		switch (strtolower($mode)) {
			case "scss":
				$compiler = new \scssc;
			case "less":
			default:
				$compiler = new \lessc;
				break;
		}
		header('Content-Type: text/css');
		try {
			$this->render($compiler->compileFile($filePath));
		} catch (\Exception $e) {
			$this->rage(strtoupper($mode)." error: ".$e->getMessage());
		}
	}

	// ===========================================================================
	// FILE UTILS
	// ===========================================================================

	public function listDirectory ($directoryPath, $types = null, $noExclusions = false) {
		$list = [];
		$excludes = [".", "..", ".DS_STORE", ".DS_Store", ".htaccess",".brackets.json","Icon","Icon\r"];

		if (is_dir($directoryPath)){
			if ($openDir = opendir($directoryPath)) {
				while (false !== ($filenamePointer = readdir($openDir))){
					$extension = Tools::getFileExtension($filenamePointer);
					if (is_null($types) || (is_array($types) && in_array($extension, $types)) || (is_string($types) && $extension === $types) || (is_dir($filenamePointer) && $types === false)) {
						if ($noExclusions || !in_array($filenamePointer, $excludes)) {
							$list[] = $filenamePointer;
						}
					}
				}
			} else {
				$this->warn("Couldn't open directory: '{$directoryPath}'.");
			}
		} else {
			$this->warn("Invalid directory path supplied: '{$directoryPath}'.");
		}
		return $list;
	}

	public function writeFile ($path, $content, $force = false) {
		$isNewFile = (file_exists($path) ? false : true);

		if (empty($content) && !$force){
			$this->rage("Attempted to write empty content to file: '{$path}'.");
		}

		if ($isNewFile) {
			$parent_dir = dirname($path);
			if (!is_dir($parent_dir) && !mkdir($parent_dir, 0777, true)) {
				$this->rage("Unable to create directory: {$parent_dir}");
			}
		}

		if (file_put_contents($path, $content)) {
			if ($isNewFile) { chmod($path, 0777); }
			return true;
		} else {
			if ($force) {
				$this->rage("Couldn't write file to path: '{$path}'.");
			}
			$this->warn("Couldn't write file to path: '{$path}'.");
			return false;
		}
	}


	// ===========================================================================
	// Working With Data
	// ===========================================================================

	public function loadData ($filePath, $force = true) {
		if (!file_exists($filePath)) { $this->warn("File not found at '{$filePath}'."); return []; }
		$load = json_decode(file_get_contents($filePath), true);
		if (!$load && $force) { $this->rage("Error decoding JSON file at {$filePath}."); }
		if (!$load && !$force) { $this->warn("Error decoding JSON file at {$filePath}."); }
		return $load;
	}

	public function saveJSON ($path, $obj, $force = true, $pretty = true) {
		if (empty($obj) && !$force){
			$this->rage("Attempted to write empty JSON object to file: '{$path}'.");
		}
		if ($pretty) {
			$content = Tools::jsonify($obj);
		} else {
			$content = json_encode($obj, JSON_UNESCAPED_SLASHES);
		}
		return $this->writeFile($path, $content, $force);
	}

	// ===========================================================================
	// Output & Responses
	// ===========================================================================

	// Pass text or html to echo to screen,
	// or pass an object/array/whatever to render JSON
	public function render ($content) {
		if (is_string($content) || is_numeric($content)) {
			echo $content;
		} else {
			header('Content-Type: application/json');
			echo $this->jsonify($content);
		}
		die();
	}

	// Pass a file path to stream()
	// the script acts as the file (displays image, loads video, etc)
	public function stream ($filePath = null) {
		if (is_null($filePath)) {
			$this->rage("Can't stream file: No path provided.");
		} elseif (!file_exists($filePath)) {
			$this->rage("Can't stream file: '{$filePath}' not found.");
		}
		$fp = fopen($filePath, 'rb');
		header("Content-type:".mime_content_type($filePath));
		header("Content-Length: " . filesize($filePath));
		fpassthru($fp);
		die();
	}

	// send an array or whatever to ->respond() and it will respond as
	// json with only $payload, OR static::$output if no payload given
	public function deliver ($payload = null) {
		$output = [];

		if (!is_null($payload) && $this->verbose) {
			$output["body"] = $payload;
		} elseif (!is_null($payload)) {
			$output = $payload;
		}
		if ($this->verbose) {
			$output = Tools::integrate(static::$meta, $output);
		}

		# TODO make this work with numeric indexed arrays
		if (!empty(static::$warnings) && is_array($output)) {
			$output["warnings"] = static::$warnings;
		}

		header('Content-Type: application/json');
		echo Tools::jsonify($output);
		die();
	}
}
