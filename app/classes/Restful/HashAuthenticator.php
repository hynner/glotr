<?php
namespace GLOTR\Restful\Security\Authentication;
use Drahak\Restful\Security\Authentication\IRequestAuthenticator;
use \Drahak\Restful\IInput;
use \Drahak\Restful\Security\IAuthTokenCalculator;
use \Drahak\Restful\Security\AuthenticationException;
use \Drahak\Restful\Http\IRequest;
use \Nette\Object;

/**
 * Custom hash authenticator to allow setting privateKey dynamically
 */
class HashAuthenticator extends Object implements IRequestAuthenticator
{
	/** Auth token request header name */
	const AUTH_HEADER = 'X-HTTP-AUTH-TOKEN';
	/** @var array */
	private $privateKey;

	/** @var IRequest */
	protected $request;

	/** @var IAuthTokenCalculator */
	protected $calculator;

	public function __construct(IRequest $request, IAuthTokenCalculator $calculator)
	{
		$this->request = $request;
		$this->calculator = $calculator;
	}

	/**
	 * @param IInput $input
	 * @return bool
	 * @throws AuthenticationException
	 */
	public function authenticate(IInput $input)
	{
		$requested = $this->getRequestedHash();
		if (!$requested) {
			throw new AuthenticationException('Authentication header not found.', 401);
		}

		$expected = $this->getExpectedHash($input);
		if ($requested !== $expected) {
			throw new AuthenticationException('Authentication tokens do not match.', 403);
		}
		return TRUE;
	}
	public function setPrivateKey($key)
	{
		$this->privateKey = $key;
	}

	/**
	 * Get request hash
	 * @return string
	 */
	protected function getRequestedHash()
	{
		return $this->request->getHeader(self::AUTH_HEADER);
	}

	/**
	 * Get expected hash
	 * @param IInput $input
	 * @return string
	 */
	protected function getExpectedHash(IInput $input)
	{
		$this->calculator->setPrivateKey($this->privateKey);
		return $this->calculator->calculate($input);
	}

}