<?php
namespace Drahak\Restful\Security\Process;

use Drahak\Restful\IInput;
use Nette\Object;

/**
 * Request AuthenticationProcess template
 * @package Drahak\Restful\Security\Process
 * @author Drahomír Hanák
 */
abstract class AuthenticationProcess extends Object
{

	/**
	 * Authenticate process
	 * @param IInput $input
	 * @return bool
	 */
	public final function authenticate(IInput $input)
	{
		$this->authRequestData($input);
		$this->authRequestTimeout($input);
		return TRUE;
	}

	/**
	 * Authenticate request data
	 * @param IInput $input
	 * @return bool
	 */
	abstract protected function authRequestData(IInput $input);

	/**
	 * Authenticate request timeout
	 * @param IInput $input
	 * @return bool
	 */
	abstract protected function authRequestTimeout(IInput $input);

}