<?php
namespace Drahak\Restful\Application\Routes;

use Drahak\Restful\InvalidStateException;
use Nette\Application\Routers\RouteList;

/**
 * ResourceRouteList
 * @package Drahak\Restful\Route
 * @author Drahomír Hanák
 */
class ResourceRouteList extends RouteList
{

	/**
	 * Set offset
	 * @param mixed $index
	 * @param mixed $route
	 * @throws \Drahak\Restful\InvalidStateException
	 */
	public function offsetSet($index, $route)
	{
		if (!($route instanceof IResourceRouter)) {
			throw new InvalidStateException('ResourceRouteList expects IResourceRoute');
		}
		parent::offsetSet($index, $route);
	}

}