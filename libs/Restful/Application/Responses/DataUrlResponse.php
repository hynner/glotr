<?php
namespace Drahak\Restful\Application\Responses;

use Drahak\Restful\Mapping\DataUrlMapper;
use Nette\Http;

/**
 * DataUrlResponse
 * @package Drahak\Restful\Application\Responses
 * @author Drahomír Hanák
 */
class DataUrlResponse extends BaseResponse
{

	/** @var array|\stdClass|\Traversable */
	private $data;

	/**
	 * @param string|null $data
	 * @param string|null $contentType
	 */
	public function __construct($data, $contentType = NULL)
	{
		parent::__construct($contentType);
		$this->data = $data;
		$this->mapper = new DataUrlMapper;
	}

	/**
	 * Sends response to output
	 * @param Http\IRequest $httpRequest
	 * @param Http\IResponse $httpResponse
	 */
	public function send(Http\IRequest $httpRequest, Http\IResponse $httpResponse)
	{
		$httpResponse->setContentType($this->contentType ? $this->contentType : 'text/plain');
		echo $this->mapper->stringify($this->data);
	}


}