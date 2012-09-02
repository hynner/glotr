<?php
namespace GLOTR;
use Nette;
class Users extends Table
{
	/** @var string */
	protected $tableName = "users";

	public function getAdmins()
	{
		return $this->findBy(array("is_admin" => "1"));
	}
	public function getAdminCount()
	{
		return count($this->getAdmins);
	}

	public function getNewKey(Nette\Security\Identity $user)
	{
		if(!$user)
			return false;
		$id = $user->id;
		$key = Nette\Utils\Strings::random (32, "0-9a-f");



		try
		{
			$this->getTable()->where(array("id_user" =>$id))->update(array("logon_key" => $key));
			$user->__set("logon_key", $key);
		}
		catch(\PDOException $e)
		{$this->getPresenter()->flashMessage("Error while updating logon key", "error");}

	}

}
