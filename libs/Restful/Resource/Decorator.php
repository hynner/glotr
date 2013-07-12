<?php
namespace Drahak\Restful\Resource;

use ArrayAccess;
use Serializable;
use Drahak\Restful\IResource;
use Nette\Object;

/**
 * Resource base decorator
 * @package Drahak\Restful\Resource
 * @author Drahomír Hanák
 */
abstract class Decorator extends Object implements IResource, Serializable, ArrayAccess
{

	/** @var IResource */
	private $resource;

	public function __construct(IResource $resource)
	{
		$this->resource = $resource;
	}

	/**
	 * Get resource data
	 * @return array|\stdClass|\Traversable
	 */
	public function getData()
	{
		return $this->resource->getData();
	}

	/**
	 * Get content type
	 * @return string
	 */
	public function getContentType()
	{
		return $this->resource->getContentType();
	}

	/**
	 * Set content type
	 * @param string $contentType
	 */
	public function setContentType($contentType)
	{
		$this->resource->setContentType($contentType);
	}

	/******************** Serializable ********************/

	/**
	 * Serialize result set
	 * @return string
	 */
	public function serialize()
	{
		return $this->resource->serialize();
	}

	/**
	 * Unserialize Resource
	 * @param string $serialized
	 */
	public function unserialize($serialized)
	{
		$this->resource->unserialize($serialized);
	}


	/******************** ArrayAccess interface ********************/

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return $this->resource->offsetExists($offset);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->resource->offsetGet($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->resource->offsetSet($offset, $value);
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		$this->resource->ofsetUnset($offset);
	}


	/******************** Magic methods ********************/

	/**
	 * Magic getter from $this->data
	 * @param string $name
	 * @param $name
	 * @throws \Exception|\Nette\MemberAccessException
	 * @return mixed
	 */
	public function &__get($name)
	{
		$this->resource->__get($name);
	}

	/**
	 * Magic setter to $this->data
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->resource->$name = $value;
	}

	/**
	 * Magic isset to $this->data
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->resource->$name);
	}

	/**
	 * Magic unset from $this->data
	 * @param string $name
	 * @throws \Exception|\Nette\MemberAccessException
	 */
	public function __unset($name)
	{
		unset($this->resource->$name);
	}


}