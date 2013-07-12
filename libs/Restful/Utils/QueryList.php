<?php
namespace Drahak\Restful\Utils;

use Countable;
use ArrayIterator;
use IteratorAggregate;
use Nette\Object;
use Nette\Utils\Strings;

/**
 * QueryList
 * @package Drahak\Restful\Utils
 * @author Drahomír Hanák
 */
class QueryList extends Object implements IteratorAggregate, Countable, IQueryList
{

	/** @var array */
	private $list;

	/**
	 * @param array $list
	 */
	public function __construct(array $list)
	{
		$this->list = $list;
	}

	/******************** IQueryList ********************/

	/**
	 * Does list contain given item
	 * @param string $element
	 * @return bool
	 */
	public function contains($element)
	{
		return in_array($element, $this->list) || in_array('-' . $element, $this->list);
	}

	/**
	 * Is item inverted (contains '-' at start)
	 * @param string $element
	 * @return bool
	 */
	public function isInverted($element)
	{
		return Strings::substring($element, 0, 1) !== '-' ?
			in_array('-' . $element, $this->list) :
			$this->contains($element);
	}

	/**
	 * Convert query list to array
	 * @return aray
	 */
	public function toArray()
	{
		return $this->list;
	}

	/**
	 * Converts query list to array of key => DESC|ASC
	 * @return array
	 */
	public function toSortArray()
	{
		$array = array();
		foreach ($this->list as $element) {
			$key = Strings::substring($element, 0, 1) === '-' ? Strings::substring($element, 1) : $element;
			$array[$key] = $this->isInverted($element) ? self::DESC : self::ASC;
		}
		return $array;
	}

	/******************** Countable ********************/

	/**
	 * Get list size
	 * @return int
	 */
	public function count()
	{
		return count($this->list);
	}

	/******************** Iterator aggregate ********************/

	/**
	 * Create iterator
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->list);
	}


}