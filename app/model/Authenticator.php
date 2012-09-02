<?php
namespace GLOTR;
use Nette\Security as NS;
use Nette;

/**
 * Users authenticator.
 */
class Authenticator extends Nette\Object implements NS\IAuthenticator
{
	/** @var \GLOTR\Users */
	private $database;



	public function __construct(Users $database)
	{
		$this->database = $database;
	}



	/**
	 * Performs an authentication
	 * @param  array
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;
		$row = $this->database->findOneBy(array("username" => $username));

		if (!$row) {
			throw new NS\AuthenticationException("User '$username' not found.", self::IDENTITY_NOT_FOUND);
		}

		if ($row->password !== $this->calculateHash($password, $row->password)) {
			throw new NS\AuthenticationException("Invalid password.", self::INVALID_CREDENTIAL);
		}

		unset($row->password);
		return new NS\Identity($row->id_user, null, $row->toArray());
	}
	/**
	 * Performs an authentication
	 * @param  array
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticateByLogonKey( $logon_key)
	{

		$row = $this->database->findOneBy(array("logon_key" => $logon_key));

		if (!$row) {
			throw new NS\AuthenticationException("User  not found.", self::IDENTITY_NOT_FOUND);
		}
		unset($row->password);
		return new NS\Identity($row->id_user, null, $row->toArray());
	}




	/**
	 * Computes salted password hash.
	 * @param  string
	 * @return string
	 */
	public static function calculateHash($password, $salt = null)
	{
		if($salt === NULL)
			$salt = "$2a$07$".Nette\Utils\Strings::random (32)."$";
		return crypt($password, $salt);
	}

}
