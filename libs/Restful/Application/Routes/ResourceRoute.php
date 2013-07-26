<?php
namespace Drahak\Restful\Application\Routes;

use Drahak\Restful;
use Nette\Application;
use Nette\Application\Routers\Route;
use Nette\Http;
use Nette\Utils\Strings;

/**
 * ResourceRoute
 * @package Drahak\Restful\Routes
 * @author Drahomír Hanák
 *
 * @property array $actionDictionary
 */
class ResourceRoute extends Route implements IResourceRouter
{

	/** @var array */
	protected $actionDictionary;

	/** @var array */
	private $methodDictionary = array(
		Http\IRequest::GET => self::GET,
		Http\IRequest::POST => self::POST,
		Http\IRequest::PUT => self::PUT,
		Http\IRequest::HEAD => self::HEAD,
		Http\IRequest::DELETE => self::DELETE,
		Restful\Http\IRequest::PATCH => self::PATCH
	);

	/**
	 * @param string $mask
	 * @param array|string $metadata
	 * @param int $flags
	 */
	public function __construct($mask, $metadata = array(), $flags = 0)
	{
		parent::__construct($mask, $metadata, $flags);

		if (isset($metadata['action']) && is_array($metadata['action'])) {
			$this->actionDictionary = $metadata['action'];
		}
	}

	/**
	 * Is this route mapped to given method
	 * @param int $method
	 * @return bool
	 */
	public function isMethod($method)
	{
		$common = array(self::CRUD, self::RESTFUL);
		$isActionDefined = $this->actionDictionary && !in_array($method, $common) ?
			isset($this->actionDictionary[$method]) :
			TRUE;
		return ($this->flags & $method) == $method && $isActionDefined;
	}

	/**
	 * Get request method flag
	 * @param Http\IRequest $httpRequest
	 * @return string|null
	 */
	public function getMethod(Http\IRequest $httpRequest)
	{
		$method = $httpRequest->getMethod();
		// to allow proper cross-origin preflight request routing
		if($method === "OPTIONS")
			return $this->methodDictionary[$httpRequest->getHeader("Access-Control-Request-Method")];
		if (!isset($this->methodDictionary[$method])) {
			return NULL;
		}
		return $this->methodDictionary[$method];
	}

	/**
	 * Get action dictionary
	 * @return array|NULL
	 */
	public function getActionDictionary()
	{
		return $this->actionDictionary;
	}

	/**
	 * Set action dictionary
	 * @param array|NULL
	 * @return $this
	 */
	public function setActionDictionary($actionDictionary)
	{
		$this->actionDictionary = $actionDictionary;
		return $this;
	}

	/**
	 * @param Http\IRequest $httpRequest
	 * @return \Nette\Application\Request|NULL
	 */
	public function match(Http\IRequest $httpRequest)
	{
		$appRequest = parent::match($httpRequest);
		if (!$appRequest) {
			return NULL;
		}

		// Check requested method
		$methodFlag = $this->getMethod($httpRequest);
		if (!$this->isMethod($methodFlag)) {
			return NULL;
		}

		// If there is action dictionary, set method
		if ($this->actionDictionary) {
			$parameters = $appRequest->getParameters();
			$parameters['action'] = $this->actionDictionary[$methodFlag];
			$appRequest->setParameters($parameters);
		}

		return $appRequest;
	}

	/**
	 * @param Application\Request $appRequest
	 * @param Http\Url $refUrl
	 * @return NULL|string
	 */
	public function constructUrl(Application\Request $appRequest, Http\Url $refUrl)
	{
		$url = parent::constructUrl($appRequest, $refUrl);
		$httpUrl = new Http\Url($url);
		$httpUrl->query = Strings::replace($httpUrl->query, '/action=([a-zA-Z0-9_+%-]*)/i', '');
		return $httpUrl->getBasePath() . $httpUrl->getRelativeUrl();
	}


}