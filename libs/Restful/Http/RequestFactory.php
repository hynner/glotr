<?php
namespace Drahak\Restful\Http;

use Nette;

/**
 * RequestFactory
 * @package Drahak\Restful\Http
 * @author Drahomír Hanák
 */
class RequestFactory extends Nette\Http\RequestFactory
{

	/** @var string|null|bool */
	private $jsonpKey;

	/** @var string|null|bool */
	private $prettyPrintKey;

	public function __construct($jsonpKey, $prettyPrintKey)
	{
		$this->jsonpKey = $jsonpKey;
		$this->prettyPrintKey = $prettyPrintKey;
	}

	/**
	 * Create HTTP request
	 * @return Request
	 */
	public function createHttpRequest()
	{
		$netteRequest = parent::createHttpRequest();
		$request = new Request(
			$netteRequest->url, $netteRequest->query, $netteRequest->post, $netteRequest->files, $netteRequest->cookies, $netteRequest->headers,
			$netteRequest->method, $netteRequest->remoteAddress, $netteRequest->remoteHost
		);
		$request->setJsonpKey($this->jsonpKey);
		$request->setPrettyPrintKey($this->prettyPrintKey);
		return $request;
	}

}