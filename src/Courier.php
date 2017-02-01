<?php

namespace Rad;

class Courier extends Base
{
	public $settings;
	public $message;

	public function __construct ($settings = null) {
		parent::__construct();
		$this->settings = [
			"system" => "php",
			"to" => null,
			"from" => null,
			"name" => null,
			"cc" => null,
			"sendGridKey" => null,
			"mandrillKey" => null,
			"sesAccessKey" => null,
			"sesSecretKey" => null
		];
		$this->message = [
			"subject" => null,
			"html" => null,
			"text" => null
		];
		
		if (!empty($settings)) $this->applySettings($settings);
		
	}

	// ================================================================
	//  GETTERS / SETTERS
	// ================================================================

	public function setMessage ($message, $setBoth = true, $html = true) {
		if (empty($message)) {
			$this->warn("setMessage() called with no message");
		}
		if ($html) {
			$this->message["html"] = $message;
			if ($setBoth) {
				$converter = new \Html2Text\Html2Text($message);
				$this->message["text"] = $converter->getText();
			}
		} else {
			$this->message["text"] = $message;
			if ($setBoth) { $this->message["html"] = Tools::textToHtml($message); }
		}
		return true;
	}

	public function assemble ($obj) {
		foreach ($this->settings as $key => $val) {
			if (isset($obj[$key])) {
				$this->settings[$key] = $obj[$key];
			}
		}
		foreach ($this->message as $key => $val) {
			if (isset($obj[$key])) {
				$this->message[$key] = $obj[$key];
			}
		}
		if (isset($obj["message"]) && is_string($message)) {
			$this->setMessage($obj["message"], true, substr($obj["message"], 0, 1) === "<");
		} elseif (isset($obj["message"]) && is_array($message)) {
			if (isset($obj["message"]["html"])) {
				$this->setMessage($obj["message"]["html"], false, true);
			}
			if (isset($obj["message"]["text"])) {
				$this->setMessage($obj["message"]["text"], false, false);
			}
		}
	}

	public function applySettings ($settings) {
		$this->settings = Tools::integrate($settings, $this->settings);
	}

	public function setSubject ($subject) {
		$this->message["subject"] = $subject;
	}

	public function setTo ($email) {
		$this->settings["to"] = $email;
	}

	public function setFrom ($email) {
		$this->settings["from"] = $email;
	}

	public function setFromName ($name) {
		$this->settings["name"] = $name;
	}

	public function addCC ($email) {
		if (is_array($email)) {
			$this->settings["cc"] = Tools::integrate($email, $this->settings["cc"]);
		} else {
			$this->settings["cc"][] = $email;
		}
	}


	// ================================================================
	//  Actions
	// ================================================================

	public function send () {
		switch (strtolower($this->settings["system"])) {
			case "ses":
				return $this->sendViaSes();
			case "mandrill":
				return $this->sendViaMandrill();
			case "sendgrid":
			case "sg":
				return $this->sendViaSendgrid();
			case "php":
			default:
				return $this->sendViaPhp();
		}
	}

	public function sendViaMandrill () {
		if (empty($this->settings["mandrillKey"])) {
			$this->rage("Can't send email via mandrill, no mandrillKey provided.");
		}
		$mandrill = new \Mandrill($this->settings["mandrillKey"]);
		$message = array(
			'html' => $this->message["html"],
			'text' => $this->message["text"],
			'subject' => $this->message["subject"],
			'from_email' => $this->settings["from"],
			'from_name' => $this->settings["name"],
			'to' => []
		);
		if (is_array($this->settings["to"])) {
			foreach ($this->settings["to"] as $email) {
				$message["to"][] = ["email" => $email, "type" => "to"];
			}
		} elseif (is_string($this->settings["to"])) {
			$message["to"][] = ["email" => $this->settings["to"], "type" => "to"];
		}

		$async = false;
		$result = $mandrill->messages->send($message, $async);
		return ($result[0]["status"] === "sent");
	}

	public function sendViaSendgrid () {
		if (empty($this->settings["sendGridKey"])) {
			$this->rage("Can't send via SendGrid: no API key provided.");
		}
		$sg = new \SendGrid($this->settings["sendGridKey"]);
		$from = new \SendGrid\Email($this->settings["name"], $this->settings["from"]);
		$content = new \SendGrid\Content("text/html", $this->message["html"]);

		if (is_string($this->settings["to"])) {
			$to = new \SendGrid\Email(null, $this->settings["to"]);
			$mail = new \SendGrid\Mail($from, $this->message["subject"], $to, $content);

			try {
				$response = $sg->client->mail()->send()->post($mail);
			} catch (\Exception $e) {
				error_log("SendGrid Exception:".$e->getMessage());
			}
			return $response->statusCode() == 202;
		} elseif (is_array($this->settings["to"])) {
			$success = true;
			foreach ($this->settings["to"] as $currentTo) {
				$to = new \SendGrid\Email(null, $currentTo);
				$mail = new \SendGrid\Mail($from, $this->message["subject"], $to, $content);

				try {
					$response = $sg->client->mail()->send()->post($mail);
				} catch (\Exception $e) {
					error_log("SendGrid Exception:".$e->getMessage());
				}
				if ($response->statusCode() != 202) {
					$success = false;
				};
			}
			return $success;
		}
	}


	public function sendViaPhp () {
		$headers = "From: {$this->settings["name"]}<{$this->settings["from"]}>\r\n";
		$headers = "Reply-To: {$this->settings["from"]}\r\n";
		$headers = "Return-Path: {$this->settings["from"]}\r\n";
		$headers .= "MIME-version: 1.0"."\r\n";
		$message = null;
		if (!is_null($this->message["html"])) {
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			$message = $this->message["html"];
		} else {
			$headers .= "Content-type: text/plain; charset=UTF-8" . "\r\n";
			$message = $this->message["text"];
		}
		if (!empty($this->settings["cc"])) {
			foreach ($this->settings["cc"] as $cc) {
				$headers .= "CC: {$cc}\r\n";
			}
		}
		if (is_string($this->settings["to"])) {
			return mail($this->settings["to"], $this->message["subject"], $message, $headers);
		} elseif (is_array($this->settings["to"])) {
			$success = true;
			foreach ($this->settings["to"] as $currentTo) {
				if (!mail($currentTo, $this->message["subject"], $message, $headers)) {
					$success = false;
				}
			}
			return $success;
		}
	}


	public function sendViaSes () {
		if (empty($this->settings["sesAccessKey"]) || empty($this->settings["sesSecretKey"])) {
			$this->rage("Can't send mail via SES, both keys not provided.");
		}
		$sesMail = new \SimpleEmailService($this->settings["sesAccessKey"],$this->settings["sesSecretKey"]);
		$m = new \SimpleEmailServiceMessage();
		if (is_string($this->settings["to"])) {
			$m->addTo($this->settings["to"]);
		} elseif (is_array($this->settings["to"])) {
			foreach ($this->settings["to"] as $currentTo) {
				$m->addTo($this->settings["to"]);
			}
		}
		$m->setFrom($this->settings["from"]);
		$m->setSubject($this->message["subject"]);
		$m->setMessageFromString($this->message["text"], $this->message["html"]);
		if (!empty($this->settings["cc"])) {
			foreach ($this->settings["cc"] as $address) {
				$m->addCC($address);
			}
		}
		$response = $sesMail->sendEmail($m);
		return (!empty($response["MessageId"]) && !empty($response["RequestId"]));
	}

}
