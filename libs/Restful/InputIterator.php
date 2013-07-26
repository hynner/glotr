<?php
namespace Drahak\Restful;

use Drahak\Restful\IInput;
use Iterator;
use Nette\Object;

/**
 * InputIterator
 * @package Drahak\Restful\Input
 * @author Drahomír Hanák
 */
class InputIterator extends Object implements Iterator
{

	/** @var \Drahak\Restful\IInput */
	protected $input;

	/** @var array|\Traversable */
	private $data;

	/**
	 * @param IInput $input
	 */
	public function __construct(IInput $input)
	{
		$this->input = $input;
	}

	/**
	 * Get current item
	 * @return mixed
	 */
	public function current()
	{
		return current($this->data);
	}

	/**
	 * Move on next iteration
	 */
	public function next()
	{
		next($this->data);
	}

	/**
	 * Get current key
	 * @return mixed
	 */
	public function key()
	{
		return key($this->data);
	}

	/**
	 * Is current element valid
	 * @return bool
	 */
	public function valid()
	{
		return isset($this->data[$this->key()]);
	}

	/**
	 * Rewind iterator to start
	 */
	public function rewind()
	{
		$this->data = $this->input->getData();
		reset($this->data);
	}

}