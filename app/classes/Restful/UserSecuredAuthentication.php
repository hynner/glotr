<?php
namespace GLOTR\Restful\Security\Process;

use \Drahak\Restful\IInput;
use \GLOTR\Restful\Security\Authentication\HashAuthenticator;
use \Drahak\Restful\Security\Authentication\TimeoutAuthenticator;
use \Drahak\Restful\Security\AuthenticationException;

/**
 * SecuredAuthentication process
 * @package Drahak\Restful\Security\Process
 * @author Drahomír Hanák
 */
class UserSecuredAuthentication extends \Drahak\Restful\Security\Process\AuthenticationProcess
{

	/** @var HashAuthenticator */
	private $hashAuth;

	/** @var TimeoutAuthenticator */
	private $timeAuth;
	/** @var \GLOTR\users */
	protected $users;
	const AUTH_USER_FIELD = "X-HTTP-AUTH-USER";
	public function __construct(HashAuthenticator $hashAuth, TimeoutAuthenticator $timeAuth, \GLOTR\Users $users)
	{
		$this->hashAuth = $hashAuth;
		$this->timeAuth = $timeAuth;
		$this->users = $users;
	}
	public function getPrivateKeyForRequest(\Nette\Http\Request $req)
	{
		$user = $req->getHeader(self::AUTH_USER_FIELD);
		if(!$user)
		{
			throw new AuthenticationException('User header not found.', 401);
		}
		else
		{
			$key = $this->users->getApiKeyByUsername($user);
			if(!$key)
			{
				throw new AuthenticationException('User api key not set.', 403);
			}
			else
			{
				$this->setPrivateKey($key);
			}
		}
	}
	/**
	 * Set api key for authentication
	 * @param string $key
	 */
	protected function setPrivateKey($key)
	{
		$this->hashAuth->setPrivateKey($key);
	}
	/**
	 * Authenticate request data
	 * @param IInput $input
	 * @return bool
	 */
	protected function authRequestData(IInput $input)
	{
		return $this->hashAuth->authenticate($input);
	}

	/**
	 * Authenticate request time
	 * @param IInput $input
	 * @return bool
	 */
	protected function authRequestTimeout(IInput $input)
	{
		return $this->timeAuth->authenticate($input);
	}

}