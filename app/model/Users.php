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
		return count($this->getAdmins());
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
	public function setPermissions($id, $permissions)
	{
		$this->getTable()->where(array("id_user" => $id))->update($permissions);
    }
    public function addPermissionColumn($name)
    {
        $cols = array_keys($this->getColumns());
        if(!in_array($name,$cols))
        {
            $this->getConnection()->query("Alter table $this->tableName ADD COLUMN $name tinyint(1) NOT NULL DEFAULT 0 ");
        }
    }
	public function getApiKeyByUsername($username)
	{
		$ret = $this->getTable()->where(array("username" => $username))->select("logon_key")->fetch();
		if($ret === FALSE) return $ret;
		return $ret->logon_key;
	}
}
