<?php
namespace Drahak\Restful\Application\Responses;

use Drahak\Restful\InvalidArgumentException;
use Drahak\Restful\Mapping\XmlMapper;
use Nette\Http;

/**
 * XmlResponse
 * @package Drahak\Restful\Responses
 * @author Drahomír Hanák
 */
class XmlResponse extends BaseResponse
{

	/** @var array|\stdClass|\Traversable */
	private $data;

	/**
	 * @param null|string $data
	 * @param string|null $contentType
	 * @param string $rootElement
	 */
	public function __construct($data, $contentType = NULL, $rootElement = 'root')
	{
		parent::__construct($contentType);
		$this->mapper = new XmlMapper($rootElement);
		$this->data = $data;
	}

	/**
	 * Sends response to output
	 * @param Http\IRequest $httpRequest
	 * @param Http\IResponse $httpResponse
	 * @throws InvalidArgumentException
	 */
	public function send(Http\IRequest $httpRequest, Http\IResponse $httpResponse)
	{
		$this->checkRequest($httpRequest);
		$httpResponse->setContentType($this->contentType ? $this->contentType : 'application/xml');
		echo $this->mapper->stringify($this->data, $httpRequest->isPrettyPrint());
	}

}