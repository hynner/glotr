<?php
namespace GLOTR;
use Nette;

class ScoreInactivity extends GLOTRApiModel
{
	/** @var string */
	protected $tableName = "score_inactivity";

	public function cleanUp($ids)
	{
		$this->getTable()->where("NOT id_player", $ids)->delete();
	}
	public function search($paginator = NULL, $filter = array())
	{
		$playersT = $this->glotrApi->getTableName("players");
		$inactT = $this->getTableName();
		$univT = $this->glotrApi->getTableName("universe");
		$where = "where $inactT.duration > 0 and $playersT.status = ''";
		$group = "group by score_inactivity.id_player";
		$params = array();
		if(!empty($filter))
		{
			if(isset($filter["galaxy"]) && $filter["galaxy"] !== "")
			{
				$where .= " and $univT.galaxy = ?";
				$params[] = $filter["galaxy"];
			}
			if(isset($filter["system_start"]) && $filter["system_start"] !== "")
			{
				$where .= " and $univT.system >= ?";
				$params[] = $filter["system_start"];
			}
			if(isset($filter["system_end"]) && $filter["system_end"] !== "")
			{
				$where .= " and $univT.system <= ?";
				$params[] = $filter["system_end"];
			}
			if(isset($filter["score_0_position_s"]) && $filter["score_0_position_s"] !== "")
			{
				$where .= " and $playersT.score_0_position >= ?";
				$params[] = $filter["score_0_position_s"];
			}
			if(isset($filter["score_0_position_e"]) && $filter["score_0_position_e"] !== "")
			{
				$where .= " and $playersT.score_0_position <= ?";
				$params[] = $filter["score_0_position_e"];
			}
		}
		$order = "order by $inactT.duration DESC, playername ASC";
		$limit = "";
		if($paginator !== NULL)
		{
			$what = "count($inactT.id_player)";
			$query = "select $what from $inactT join $playersT on $inactT.id_player = $playersT.id_player_ogame join $univT on $inactT.id_player = $univT.id_player $where $group $order $limit;";
			$count = $this->invokeQuery($query, $params);
			if(!empty($count) )
			{
				$count = count($count);
				$paginator->setItemCount($count);
				$paginator->setItemsPerPage(50);
				$limit = "limit ".$paginator->getOffset().",".$paginator->getLength();
			}

		}


		$what = "$inactT.*, $playersT.playername as playername,$playersT.score_0_position as pos, GROUP_CONCAT(galaxy,':',system,':',position SEPARATOR ' ') as planets";
		$query = "select $what from $inactT join $playersT on $inactT.id_player = $playersT.id_player_ogame join $univT on $inactT.id_player = $univT.id_player $where $group $order $limit;";
		return $this->invokeQuery($query, $params);
	}
	/**
	 * Get score inactivity for player
	 * @param integer $id
	 * @return array|boolean
	 */
	public function getByPlayer($id)
	{
		$ret = $this->getTable()->where(array("id_player" => $id))->fetch();
		if($ret !== FALSE)
			$ret = $ret->toArray();
		return $ret;
	}


}
