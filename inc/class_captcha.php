<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * This class is based from reCAPTCHA's PHP library, adapted for use in MyBB.
 *
 * Copyright (c) 2007 reCAPTCHA -- http://recaptcha.net
 * AUTHORS:
 *   Mike Crawford
 *   Ben Maurer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */

class captcha
{
	/**
	 * Type of CAPTCHA.
	 *
	 * 1 = Default CAPTCHA
	 * 4 = NoCATPCHA reCAPTCHA
	 * 5 = reCAPTCHA invisible
	 * 6 = hCaptcha
	 * 7 = hCaptcha invisible
	 * 8 = reCAPTCHA v3
	 *
	 * @var int
	 */
	public $type = 0;

	/**
	 * CAPTCHA constants declaration
	 * 
	 * @var int
	 */
	const DEFAULT_CAPTCHA = 1;
	const NOCAPTCHA_RECAPTCHA = 4;
	const RECAPTCHA_INVISIBLE = 5;
	const HCAPTCHA = 6;
	const HCAPTCHA_INVISIBLE = 7;
	const RECAPTCHA_V3 = 8;

	/**
	 * The template to display the CAPTCHA in
	 *
	 * @var string
	 */
	 public $captcha_template = '';

	/**
	 * CAPTCHA Server URL
	 *
	 * @var string
	 */
	public $server = '';

	/**
	 * CAPTCHA Verify Server
	 *
	 * @var string
	 */
	public $verify_server = '';

	/**
	 * HTML of the built CAPTCHA
	 *
	 * @var string
	 */
	public $html = '';

	/**
	 * The errors that occurred when handling data.
	 *
	 * @var array
	 */
	public $errors = array();

	/**
	 * @param bool   $build
	 * @param string $template
	 */
	function __construct($build = false, $template = "")
	{
		global $mybb, $plugins;

		$this->type = $mybb->settings['captchaimage'];

		$args = array(
			'this' => &$this,
			'build' => &$build,
			'template' => &$template,
		);

		$plugins->run_hooks('captcha_build_start', $args);

		// Prepare the build template
		if($template)
		{
			$this->captcha_template = $template;

			if($this->type == captcha::NOCAPTCHA_RECAPTCHA)
			{
				$this->captcha_template .= "_nocaptcha";
			}
			elseif($this->type == captcha::RECAPTCHA_INVISIBLE)
			{
				$this->captcha_template .= "_recaptcha_invisible";
			}
			elseif($this->type == captcha::HCAPTCHA)
			{
				$this->captcha_template .= "_hcaptcha";
			}
			elseif($this->type == captcha::HCAPTCHA_INVISIBLE)
			{
				$this->captcha_template .= "_hcaptcha_invisible";
			}
			elseif($this->type == captcha::RECAPTCHA_V3)
			{
				$this->captcha_template .= "_recaptcha_invisible";
			}
		}

		// Work on which CAPTCHA we've got installed
		if(in_array($this->type, array(captcha::NOCAPTCHA_RECAPTCHA, captcha::RECAPTCHA_INVISIBLE, captcha::RECAPTCHA_V3)) && $mybb->settings['recaptchapublickey'] && $mybb->settings['recaptchaprivatekey'])
		{
			// We want to use noCAPTCHA or reCAPTCHA invisible, set the server options
			$this->server = "//www.google.com/recaptcha/api.js";
			$this->verify_server = "https://www.google.com/recaptcha/api/siteverify";

			if($build == true)
			{
				$this->build_recaptcha();
			}
		}
		elseif(in_array($this->type, array(captcha::HCAPTCHA, captcha::HCAPTCHA_INVISIBLE)) && $mybb->settings['hcaptchapublickey'] && $mybb->settings['hcaptchaprivatekey'])
		{
			// We want to use hCaptcha or hCaptcha invisible, set the server options
			$this->server = "//js.hcaptcha.com/1/api.js";
			$this->verify_server = "https://hcaptcha.com/siteverify";

			if($build == true)
			{
				$this->build_hcaptcha();
			}
		}
		elseif($this->type == captcha::DEFAULT_CAPTCHA)
		{
			if(!function_exists("imagecreatefrompng"))
			{
				// We want to use the default CAPTCHA, but it's not installed
				return;
			}
			elseif($build == true)
			{
				$this->build_captcha();
			}
		}

		$plugins->run_hooks('captcha_build_end', $args);
	}

	/**
	 * @param bool $return Not used
	 */
	function build_captcha($return = false)
	{
		global $db, $lang, $templates, $theme, $mybb;

		// This will build a MyBB CAPTCHA
		$randomstr = random_str(5);
		$imagehash = md5(random_str(12));

		$insert_array = array(
			"imagehash" => $imagehash,
			"imagestring" => $randomstr,
			"dateline" => TIME_NOW
		);

		$db->insert_query("captcha", $insert_array);
		eval("\$this->html = \"".$templates->get($this->captcha_template)."\";");
		//eval("\$this->html = \"".$templates->get("member_register_regimage")."\";");
	}

	function build_recaptcha()
	{
		global $lang, $mybb, $templates;

		// This will build a reCAPTCHA
		$server = $this->server;
		$public_key = $mybb->settings['recaptchapublickey'];

		eval("\$this->html = \"".$templates->get($this->captcha_template, 1, 0)."\";");
		//eval("\$this->html = \"".$templates->get("member_register_regimage_recaptcha")."\";");
	}

	function build_hcaptcha()
	{
		global $lang, $mybb, $templates, $theme;

		// This will build a hCaptcha
		$server = $this->server;
		$public_key = $mybb->settings['hcaptchapublickey'];
		$captcha_theme = $mybb->settings['hcaptchatheme'];
		$captcha_size = $mybb->settings['hcaptchasize'];
		
		eval("\$this->html = \"".$templates->get($this->captcha_template, 1, 0)."\";");
	}

	/**
	 * @return string
	 */
	function build_hidden_captcha()
	{
		global $db, $mybb, $templates;

		$field = array();

		if($this->type == captcha::DEFAULT_CAPTCHA)
		{
			// Names
			$hash = "imagehash";
			$string = "imagestring";

			// Values
			$field['hash'] = $db->escape_string($mybb->get_input('imagehash'));
			$field['string'] = $db->escape_string($mybb->get_input('imagestring'));
		}
		elseif($this->type == 3)
		{
			// Are You a Human can't be built as a hidden captcha
			return '';
		}

		eval("\$this->html = \"".$templates->get("post_captcha_hidden")."\";");
		return $this->html;
	}

	/**
	 * @return bool
	 */
	function validate_captcha()
	{
		global $db, $lang, $mybb, $session, $plugins;

		$plugins->run_hooks('captcha_validate_start', $this);

		if($this->type == captcha::DEFAULT_CAPTCHA)
		{
			// We have a normal CAPTCHA to handle
			$imagehash = $db->escape_string($mybb->get_input('imagehash'));
			$imagestring = $db->escape_string(my_strtolower($mybb->get_input('imagestring')));

			switch($db->type)
			{
				case 'mysql':
				case 'mysqli':
					$field = 'imagestring';
					break;
				default:
					$field = 'LOWER(imagestring)';
					break;
			}

			$query = $db->simple_select("captcha", "*", "imagehash = '{$imagehash}' AND {$field} = '{$imagestring}'");
			$imgcheck = $db->fetch_array($query);

			if(!$imgcheck)
			{
				$this->set_error($lang->invalid_captcha_verify);
				$db->delete_query("captcha", "imagehash = '{$imagehash}'");
			}
		}
		elseif(in_array($this->type, array(captcha::NOCAPTCHA_RECAPTCHA, captcha::RECAPTCHA_INVISIBLE)))
		{
			$response = $mybb->get_input('g-recaptcha-response');
			if(!$response || strlen($response) == 0)
			{
				$this->set_error($lang->invalid_nocaptcha);
			}
			else
			{
				// We have a noCAPTCHA or reCAPTCHA invisible to handle
				// Contact Google and see if our reCAPTCHA was successful
				$response = fetch_remote_file($this->verify_server, array(
					'secret' => $mybb->settings['recaptchaprivatekey'],
					'remoteip' => $session->ipaddress,
					'response' => $response
				));

				if($response == false)
				{
					$this->set_error($lang->invalid_nocaptcha_transmit);
				}
				else
				{
					$answer = json_decode($response, true);

					if($answer['success'] != 'true')
					{
						// We got it wrong! Oh no...
						$this->set_error($lang->invalid_nocaptcha);
					}
				}
			}
		}
		elseif($this->type == captcha::RECAPTCHA_V3)
		{
			$response = $mybb->get_input('g-recaptcha-response');
			if(!$response || strlen($response) == 0)
			{
				$this->set_error($lang->invalid_nocaptcha);
			}
			else
			{
				// We have a reCAPTCHA invisible to handle
				// Contact Google and see if our reCAPTCHA was successful
				$response = fetch_remote_file($this->verify_server, array(
					'secret' => $mybb->settings['recaptchaprivatekey'],
					'score' => $mybb->settings['recaptchascore'],
					'remoteip' => $session->ipaddress,
					'response' => $response
				));

				if($response === false)
				{
					$this->set_error($lang->invalid_nocaptcha_transmit);
				}
				else
				{
					$answer = json_decode($response, true);

					if($answer['success'] != 'true' || $answer['score'] < $mybb->settings['recaptchascore'])
					{
						// We got it wrong! Oh no...
						$this->set_error($lang->invalid_nocaptcha);
					}
				}
			}
		}
		elseif(in_array($this->type, array(captcha::HCAPTCHA, captcha::HCAPTCHA_INVISIBLE)))
		{
			$response = $mybb->get_input('h-captcha-response');
			if(!$response || strlen($response) == 0)
			{
				$this->set_error($lang->invalid_hcaptcha);
			}
			else
			{
				// We have an hCaptcha or hCaptcha invisible to handle
				// Contact hCaptcha and see if our hCaptcha was successful
				$response = fetch_remote_file($this->verify_server, array(
					'secret' => $mybb->settings['hcaptchaprivatekey'],
					'remoteip' => $session->ipaddress,
					'response' => $response
				));

				if($response == false)
				{
					$this->set_error($lang->invalid_hcaptcha_transmit);
				}
				else
				{
					$answer = json_decode($response, true);
					if($answer['success'] != 'true')
					{
						// We got it wrong! Oh no...
						$this->set_error($lang->invalid_hcaptcha);
					}
				}
			}
		}
		$plugins->run_hooks('captcha_validate_end', $this);

		if(count($this->errors) > 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	function invalidate_captcha()
	{
		global $db, $mybb, $plugins;

		if($this->type == captcha::DEFAULT_CAPTCHA)
		{
			// We have a normal CAPTCHA to handle
			$imagehash = $db->escape_string($mybb->get_input('imagehash'));
			if($imagehash)
			{
				$db->delete_query("captcha", "imagehash = '{$imagehash}'");
			}
		}
		// Not necessary for reCAPTCHA or Are You a Human

		$plugins->run_hooks('captcha_invalidate_end', $this);
	}

	/**
	 * Add an error to the error array.
	 *
	 * @param string $error
	 * @param string $data
	 */
	function set_error($error, $data='')
	{
		$this->errors[$error] = array(
			"error_code" => $error,
			"data" => $data
		);
	}

	/**
	 * Returns the error(s) that occurred when handling data
	 * in a format that MyBB can handle.
	 *
	 * @return array An array of errors in a MyBB format.
	 */
	function get_errors()
	{
		global $lang;

		$errors = array();
		foreach($this->errors as $error)
		{
			$lang_string = $error['error_code'];

			if(!$lang_string)
			{
				if($lang->invalid_captcha_verify)
				{
					$lang_string = 'invalid_captcha_verify';
				}
				else
				{
					$lang_string = 'unknown_error';
				}
			}

			if(!isset($lang->$lang_string))
			{
				$errors[] = $error['error_code'];
				continue;
			}

			if(!empty($error['data']) && !is_array($error['data']))
			{
				$error['data'] = array($error['data']);
			}

			if(is_array($error['data']))
			{
				array_unshift($error['data'], $lang->$lang_string);
				$errors[] = call_user_func_array(array($lang, "sprintf"), $error['data']);
			}
			else
			{
				$errors[] = $lang->$lang_string;
			}
		}

		return $errors;
	}

	/**
	 * @param array $data
	 *
	 * @return string
	 */
	private function _qsencode($data)
	{
		$req = '';
		foreach($data as $key => $value)
		{
			$req .= $key.'='.urlencode(stripslashes($value)).'&';
		}

		$req = substr($req, 0, (strlen($req) - 1));

		return $req;
	}
}
