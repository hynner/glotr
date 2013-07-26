<?php
namespace Drahak\Restful\Utils;

/**
 * IQueryList
 * @package Drahak\Restful\Utils
 * @author Drahomír Hanák
 */
interface IQueryList
{

	const DESC = 'DESC';
	const ASC = 'ASC';

	/**
	 * Contains given element (even with '-' character prefix)
	 * @param string $element
	 * @return bool
	 */
	public function contains($element);

	/**
	 * Is given element inverted (starts with '-' character)
	 * @param string $element
	 * @return bool
	 */
	public function isInverted($element);

	/**
	 * Converts query list to simple array
	 * @return array
	 */
	public function toArray();

	/**
	 * Converts query list to array of key => DESC|ASC
	 * @return array
	 */
	public function toSortArray();

}