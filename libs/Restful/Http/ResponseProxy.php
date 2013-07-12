<?php
namespace Drahak\Restful\Http;

use Nette\Http\IResponse;
use Nette\InvalidArgumentException;
use Nette\Object;
use Nette\Http\Response;

/**
 * ResponseProxy
 * @package Drahak\Restful\Http
 * @author Drahomír Hanák
 */
class ResponseProxy extends Object implements IResponse
{

	/** @var Response */
	private $response;

	/** @var int */
	private $code;

	public function __construct()
	{
		$this->response = new Response();
	}

	/**
	 * Set respomse
	 * @param IResponse $response
	 * @return ResponseProxy
	 */
	public function setResponse(IResponse $response)
	{
		$this->response = $response;
		return $this;
	}

	/**
	 * Sets HTTP response code.
	 * @param  int
	 * @return void
	 */
	public function setCode($code)
	{
		try {
			$this->response->setCode($code);
			$this->code = $code;
		} catch (InvalidArgumentException $e) {
			if ($code === 422 || $code === 429) {
				$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
				header($protocol . ' ' . $code, TRUE, $code);
				$this->code = $code;
			} else {
				throw $e;
			}
		}
		return $this;
	}

	/**
	 * Returns HTTP response code.
	 * @return int
	 */
	public function getCode()
	{
		return $this->response->getCode();
	}

	/**
	 * Sends a HTTP header and replaces a previous one.
	 * @param  string  header name
	 * @param  string  header value
	 * @return void
	 */
	public function setHeader($name, $value)
	{
		$this->response->setHeader($name, $value);
		return $this;
	}

	/**
	 * Adds HTTP header.
	 * @param  string  header name
	 * @param  string  header value
	 * @return void
	 */
	public function addHeader($name, $value)
	{
		$this->response->addHeader($name, $value);
		return $this;
	}

	/**
	 * Sends a Content-type HTTP header.
	 * @param  string  mime-type
	 * @param  string  charset
	 * @return void
	 */
	public function setContentType($type, $charset = NULL)
	{
		$this->response->setContentType($type, $charset);
		return $this;
	}

	/**
	 * Redirects to a new URL.
	 * @param  string  URL
	 * @param  int     HTTP code
	 * @return void
	 */
	public function redirect($url, $code = self::S302_FOUND)
	{
		$this->response->redirect($url, $code);
		return $this;
	}

	/**
	 * Sets the number of seconds before a page cached on a browser expires.
	 * @param  mixed  timestamp or number of seconds
	 * @return void
	 */
	public function setExpiration($seconds)
	{
		$this->response->setExpiration($seconds);
		return $this;
	}

	/**
	 * Checks if headers have been sent.
	 * @return bool
	 */
	public function isSent()
	{
		return $this->response->isSent();
	}

	/**
	 * Returns a list of headers to sent.
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->response->getHeaders();
	}

	/**
	 * Sends a cookie.
	 * @param  string name of the cookie
	 * @param  string value
	 * @param  mixed expiration as unix timestamp or number of seconds; Value 0 means "until the browser is closed"
	 * @param  string
	 * @param  string
	 * @param  bool
	 * @param  bool
	 * @return void
	 */
	public function setCookie($name, $value, $expire, $path = NULL, $domain = NULL, $secure = NULL, $httpOnly = NULL)
	{
		$this->response->setCookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
		return $this;
	}

	/**
	 * Deletes a cookie.
	 * @param  string name of the cookie.
	 * @param  string
	 * @param  string
	 * @param  bool
	 * @return void
	 */
	public function deleteCookie($name, $path = NULL, $domain = NULL, $secure = NULL)
	{
		$this->response->deleteCookie($name, $path, $domain, $secure);
	}


	public function removeDuplicateCookies()
	{
		$this->response->removeDuplicateCookies();
	}

}