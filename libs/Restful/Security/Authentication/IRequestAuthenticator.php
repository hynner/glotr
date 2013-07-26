<?php
namespace Drahak\Restful\Security\Authentication;

use Drahak\Restful\IInput;

/**
 * IRequestAuthenticator
 * @package Drahak\Restful
 * @author Drahomír Hanák
 */
interface IRequestAuthenticator
{

	/**
	 * Authenticate request
	 * @param IInput $input
	 * @return bool
	 */
	public function authenticate(IInput $input);

}