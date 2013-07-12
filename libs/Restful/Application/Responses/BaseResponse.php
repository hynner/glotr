<?php
namespace Drahak\Restful\Application\Responses;

use Drahak;
use Drahak\Restful\InvalidArgumentException;
use Drahak\Restful\Mapping\IMapper;
use Nette\Application\IResponse;
use Nette\Http\IRequest;
use Nette\Object;

/**
 * BaseResponse
 * @package Drahak\Restful\Application\Responses
 * @author Drahomír Hanák
 *
 *  @property-read string $contentType
 *  @property-write IMapper $mapper
 */
abstract class BaseResponse extends Object implements IResponse
{

	/** @var IMapper */
	protected $mapper;

	/** @var string */
	protected $contentType;

	/**
	 * @param string|NULL $contentType
	 */
	public function __construct($contentType = NULL)
	{
		$this->contentType = $contentType;
	}

	/**
	 * Get response content type
	 * @return string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * Set mapper
	 * @param IMapper $mapper
	 * @return BaseResponse
	 */
	public function setMapper(IMapper $mapper)
	{
		$this->mapper = $mapper;
		return $this;
	}

	/**
	 * Check if given request is valid
	 * @param IRequest $request
	 * @throws InvalidArgumentException
	 */
	protected function checkRequest(IRequest $request)
	{
		if (!$request instanceof Drahak\Restful\Http\IRequest) {
			throw new InvalidArgumentException(
				get_class($this) . ' expects Drahak\Restful\Http\IRequest as a first parameter, ' . get_class($request) . ' given'
			);
		}
	}

}