<?php
namespace Drahak\Restful\Application\Responses;

use Nette\Http;
use Drahak\Restful\Mapping\JsonMapper;
use Drahak\Restful\InvalidArgumentException;

/**
 * JsonResponse with pretty print support
 * @package Drahak\Restful\Application\Responses
 * @author Drahomír Hanák
 */
class JsonResponse extends BaseResponse
{

	/** @var array|\stdClass|\Traversable */
	private $data;

	/**
	 * @param null|string $data
	 * @param string|null $contentType
	 */
	public function __construct($data, $contentType = NULL)
	{
		parent::__construct($contentType);
		$this->mapper = new JsonMapper();
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
		$httpResponse->setContentType($this->contentType ? $this->contentType : 'application/json');
		echo $this->mapper->stringify($this->data, $httpRequest->isPrettyPrint());
	}

}