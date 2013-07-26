<?php
namespace Drahak\Restful;

/**
 * IResourceFactory
 * @package Drahak\Restful
 * @author Drahomír Hanák
 */
interface IResourceFactory
{

	/**
	 * Create new API resource
	 * @return IResource
	 */
	public function create();

}