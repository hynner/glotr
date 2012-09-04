<?php
namespace GLOTR;
use Nette;
use Nette\Diagnostics\Debugger as DBG;
class Fleetsaves extends Table
{
	/** @var string */
	protected $tableName = "fleetsave";

	public function insertFS($data)
	{
		try
		{
			$this->getTable()->insert($data);
		}
		catch(\PDOException $e)
		{
			echo $e->getMessage();
		}
	}
	public function search($id_player)
	{
		$ret = array();
		$res = $this->findBy(array("id_player" => $id_player));
		while($r = $res->fetch())
			$ret[] = $r->toArray();
		return $ret;
	}

}
