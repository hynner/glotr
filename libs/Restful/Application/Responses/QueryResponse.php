<?php
namespace Drahak\Restful\Application\Responses;

use Drahak\Restful\Mapping\QueryMapper;
use Nette\Http;

/**
 * Text QueryResponse
 * @package Drahak\Restful\Application\Responses
 * @author Drahomír Hanák
 */
class QueryResponse extends BaseResponse
{

	/** @var array|\Traversable */
	private $data;

	/**
	 * @param $data
	 * @param string|null $contentType
	 */
	public function __construct($data, $contentType = NULL)
	{
		parent::__construct($contentType);
		$this->mapper = new QueryMapper;
		$this->data = $data;
	}

	/**
	 * Sends response to output
	 * @param Http\IRequest $httpRequest
	 * @param Http\IResponse $httpResponse
	 */
	public function send(Http\IRequest $httpRequest, Http\IResponse $httpResponse)
	{
		$httpResponse->setContentType($this->contentType ? $this->contentType : 'application/x-www-form-urlencoded');
		echo $this->mapper->stringify($this->data);
	}


}