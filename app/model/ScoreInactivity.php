<?php
namespace GLOTR;
use Nette;

class ScoreInactivity extends Table
{
	/** @var string */
	protected $tableName = "score_inactivity";

	public function cleanUp($ids)
	{
		$this->getTable()->where("NOT id_player", $ids)->delete();
	}
	public function search($paginator = NULL)
	{
		$playersT = $this->container->players->getTableName();
		$inactT = $this->getTableName();;
		$where = "where $inactT.duration > 0 and $playersT.status = ''";
		$order = "order by $inactT.duration DESC";
		$limit = "";
		if($paginator !== NULL)
		{
			$what = "count($inactT.id_player) as c";
			$query = "select $what from $inactT join $playersT on $inactT.id_player = $playersT.id_player_ogame $where $order $limit;";
			$count = $this->connection->query($query)->fetch();
			if($count !== FALSE)
			{
				$paginator->setItemCount($count->c);
				$paginator->setItemsPerPage(50);
				$limit = "limit ".$paginator->getOffset().",".$paginator->getLength();
			}

		}
		$what = "$inactT.*, $playersT.playername as playername";
		$query = "select $what from $inactT join $playersT on $inactT.id_player = $playersT.id_player_ogame $where $order $limit;";
		$res = $this->connection->query($query);
		$ret = array();
		while($r = $res->fetch())
		{
			$ret[$r->id_player] = $r;
		}
		return $ret;
	}


}
