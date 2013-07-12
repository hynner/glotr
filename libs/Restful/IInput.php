<?php
namespace Drahak\Restful;

use Drahak\Restful\Mapping\IMapper;

/**
 * REST client request Input interface
 * @package Drahak\Restful
 * @author Drahomír Hanák
 */
interface IInput
{

	/**
	 * Get parsed input data
	 * @return mixed
	 */
	public function getData();

	/**
	 * Set input mapper
	 * @param IMapper $mapper
	 * @return IInput
	 */
	public function setMapper(IMapper $mapper);

}