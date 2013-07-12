<?php
namespace Drahak\Restful\Http;

use Nette;

/**
 * HTTP API request interface
 * @package Drahak\Restful\Http
 * @author Drahomír Hanák
 */
interface IRequest extends Nette\Http\IRequest
{

	/** Patch request method for partial updates */
	const PATCH = 'PATCH';

	/**
	 * Since method could be overridden, this returns a true method name
	 * @return string
	 */
	public function getOriginalMethod();

	/**
	 * Is this request JSONP
	 * @return bool
	 */
	public function isJsonp();

	/**
	 * Get JSONP parameter value
	 * @return string|NULL
	 */
	public function getJsonp();

	/**
	 * Is pretty print enabled
	 * @return bool
	 */
	public function isPrettyPrint();

	/**
	 * Get preferred request content type
	 * @return string
	 */
	public function getPreferredContentType();

}