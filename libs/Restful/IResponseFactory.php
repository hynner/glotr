<?php
namespace Drahak\Restful;

use Nette\Application\IResponse;

/**
 * IResponseFactory
 * @package Drahak\Restful
 * @author Drahomír Hanák
 */
interface IResponseFactory
{

	/**
	 * Create new API response
	 * @param IResource $resource
	 * @return IResponse
	 */
	public function create(IResource $resource);

}