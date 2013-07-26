<?php
namespace GLOTR;
use Nette;
class FleetMovements extends GLOTRApiModel
{
	/** @var string */
	protected $tableName = "fleet_movements";
	/** @var string */
	protected $historyTable = "fleet_movements_history";

	protected function setup()
	{
		$this->historyTable = $this->params["tablePrefix"].$this->historyTable;
	}
	/**
	 * Insert fleet movement to database
	 * @param array $dbData data for the fleet movement itself
	 * @param array $obs observation point - for history
	 * @return boolean
	 */
	public function insertFleetMovement($dbData, $obs)
	{
		try {
			$this->getTable()->insert($dbData);
		}
		catch(\PDOException $e)
		{
			$this->getTable()->where(array("id_fleet_ogame" => $dbData["id_fleet_ogame"]))->update($dbData);
		}
		$this->insertHistory($obs, $dbData["last_updated"], $dbData["id_fleet_ogame"], ((isset($dbData["arrival"])) ? $dbData["arrival"] : $dbData["return_time"]));
		return true;
	}
	/**
	 * Insert fleet movements history
	 * @param array $obs - id_planet and moon keys
	 * @param int $time
	 * @param int $id_fleet
	 */
	public function insertHistory($obs, $time, $id_fleet = NULL, $arrival = NULL)
	{
		$history = array(
			"id_observation_planet" => $obs["id_planet"],
			"observation_moon" => (($obs["moon"]) ? "1" : "0"),
			"timestamp" => $time,
			"id_fleet" => $id_fleet,
			"arrival" => $arrival
		);
		$this->connection->table($this->historyTable)->insert($history);
	}
	public function search($userPlayer = array(), $paginator = NULL)
	{
		$query = $this->getTable()->where("arrival > ?", time());
		if($paginator !== NULL)
		{
			$count = $query->where("id_parent IS NULL OR id_parent = id_fleet_ogame")->count("id_fleet_ogame");
			$paginator->setItemCount($count);
			$paginator->setItemsPerPage(20);
			// I must do this, so that ACS counts as 1 fleet movement
			$query = $query->limit($paginator->getLength(), $paginator->getOffset())
					->where("id_parent IS NULL OR id_parent = id_fleet_ogame")
					->select("id_fleet_ogame");
            $ids = array_keys($query->fetchPairs("id_fleet_ogame"));
            if(!$ids)
                return array();
			$ids = join(",", $ids);
			$query = $this->getTable()->where("id_fleet_ogame IN (?) OR id_parent IN (?)", $ids, $ids);
		}
		$data = $query->order("arrival ASC")->fetchPairs("id_fleet_ogame");
		$ret = array();
		foreach($data as $id => $row)
		{
			$player_or = $player_dest = false;
			$origin = $this->glotrApi->getPlanetDetail($row->id_origin);
			if($origin)
			{
				$origin = $origin->toArray();
				$player_or = $this->glotrApi->getPlayerDetail($origin["id_player"], $userPlayer, array("player"));
				$player_or = $player_or["player"];
			}

			$dest = $this->glotrApi->getPlanetDetail($row->id_destination);
			if($dest)
			{
				$dest = $dest->toArray();
				$player_dest = $this->glotrApi->getPlayerDetail($dest["id_player"], $userPlayer, array("player"));
				$player_dest = $player_dest["player"];
			}
			$init = false;
			if(isset($row->id_parent))
			{
				if(!isset($ret[$row->id_parent]))
				{
					$ret[$row->id_parent] = $row->toArray();
					$ret[$row->id_parent]["id_fleet_ogame"] = $row->id_parent;
					$unset = array("last_updated", "id_origin");
					foreach($unset as $key)
						unset($ret[$row->id_parent][$key]);
					$ret[$row->id_parent]["children"] = array($row->id_fleet_ogame => $row->toArray());
					$init = true;
				}
				if(!$init)
				{
					$loop = array_merge($this->getFleetKeys(), array("metal", "crystal", "deuterium"));
					foreach($loop as $key)
					{
						if(isset($row->$key) && $row->$key !== NULL)
						{
							$ret[$row->id_parent][$key] += $row->$key;
						}
					}
					$ret[$row->id_parent]["children"][$row->id_fleet_ogame] = $row->toArray();
				}
				$ret[$row->id_parent]["children"][$row->id_fleet_ogame]["origin"] = $origin;
				$ret[$row->id_parent]["children"][$row->id_fleet_ogame]["dest"] = $dest;
				$ret[$row->id_parent]["children"][$row->id_fleet_ogame]["player_or"] = $player_or;
				$ret[$row->id_parent]["children"][$row->id_fleet_ogame]["player_dest"] = $player_dest;
			}
			else
			{
				$ret[$row->id_fleet_ogame] = $row->toArray();
			}
			if(!isset($row->id_parent) || $row->id_parent == $row->id_fleet_ogame)
			{
				$ret[$row->id_fleet_ogame]["origin"] = $origin;
				$ret[$row->id_fleet_ogame]["dest"] = $dest;
				$ret[$row->id_fleet_ogame]["player_or"] = $player_or;
				$ret[$row->id_fleet_ogame]["player_dest"] = $player_dest;
			}

		}
		return $ret;
	}
	public function getFleetKeys()
	{
		$tmp = $this->glotrApi->getEspionageKeys("planet_fleet");
		unset($tmp[array_search("sollar_sattelite", $tmp)]);
		return $tmp;
	}
}
