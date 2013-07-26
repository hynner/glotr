<?php
namespace Drahak\Restful\Application\Routes;

/**
 * IRouteListFactory
 * @package Drahak\Restful
 * @author Drahomír Hanák
 */
interface IRouteListFactory
{

	/**
	 * Create resources route list
	 * @param string|null $module
	 * @return ResourceRouteList
	 */
	public function create($module = NULL);

}