<?php
namespace Drahak\Restful\Security\Authentication;

use Drahak\Restful\IInput;
use Drahak\Restful\Security\RequestTimeoutException;
use Nette\Object;

/**
 * Verify request timeout to avoid replay attack (needs to be applied with any HashAuthenticator)
 * @package Drahak\Restful\Security
 * @author Drahomír Hanák
 */
class TimeoutAuthenticator extends Object implements IRequestAuthenticator
{

	/** @var string */
	private $requestTimeKey;

	/** @var int */
	private $timeout;

	/**
	 * @param string $requestTimeKey in user request data
	 * @param int $timeout in milliseconds
	 */
	public function __construct($requestTimeKey, $timeout)
	{
		$this->requestTimeKey = $requestTimeKey;
		$this->timeout = $timeout;
	}

	/**
	 * Authenticate request timeout
	 * @param IInput $input
	 * @return bool
	 *
	 * @throws RequestTimeoutException
	 */
	public function authenticate(IInput $input)
	{
		$timestamp = time();
		$data = $input->getData();
		if (!isset($data[$this->requestTimeKey]) || !$data[$this->requestTimeKey]) {
			throw new RequestTimeoutException('Request time not found in requested data.',400);
		}

		$diff = $timestamp - $data[$this->requestTimeKey];
		if ($diff > $this->timeout) {
			throw new RequestTimeoutException('Request timeout',408);
		}

		return TRUE;

	}

}