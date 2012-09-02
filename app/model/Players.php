<?php
namespace GLOTR;
use Nette;

class Players extends Table
{
	/** @var string */
	protected $tableName = "players";
	/** @var string api filename */
	protected $apiFile = "players.xml";
	public function updateFromApi()
	{
		$data = $this->container->ogameApi->getData($this->apiFile);
		if($data !== false)
		{
			$timestamp = (int)$data["timestamp"];
			/* this can´t be done at the moment
			 * // data in ogame api are always valid at the time of their creation, so I can delete all older data, because they will be replaced anyway
			// this way I also get rid of nonexisting players
			if($data->player)
				$res = $this->getTable()->where("last_update < ?", $timestamp)->delete();*/

			$start = (int) $this->container->config->load("$this->tableName-start");

			$end = $start + $this->container->parameters["ogameApiRecordsAtOnce"];

			for($i = $start; $i < $end; $i++)
			{

				if($data->player[$i])
				{
					$player = $data->player[$i];
				}

				else
					{
						// this is the end of update, save the timestamp
						$this->container->config->save("$this->tableName-finished", $timestamp);
						$this->container->config->save("$this->tableName-start", 0);
						return true;
					}

				$playername = (string) $player["name"];
				$id = (int) $player["id"];
				$status = (string) $player["status"];
				$alliance = (int) $player["alliance"];
				$dbData = array(
								"id_player_ogame" => $id,
								"playername" => $playername,
								"status" =>  $status,
								"id_alliance" => $alliance,
								"last_update" => $timestamp
								);
				$this->insertPlayer($dbData);




			}
			$this->container->config->save("$this->tableName-start", $end);
			return true;
		}
		else
			return false;

	}
	public function insertPlayer($dbData)
	{
		try{

				$this->getTable()->insert($dbData);
			}

			catch(\PDOException $e)
			{
				//if player already exists update it
				$this->getTable()->where(array("id_player_ogame" => $dbData["id_player_ogame"]))->update($dbData);
			}
		return true;
	}
	public function setAlliance($id_player, $id_alliance)
	{
		$this->getTable()->where("id_player_ogame" , $id_player)->update(array("id_alliance" => $id_alliance));
		return true;
	}
	public function setResearches($id_player, $dbData)
	{
		$this->getTable()->where("id_player_ogame" , $id_player)->update($dbData);
		return true;
	}
	public function getIdByPlanet($coords)
	{

		return $this->container->universe->getTable()->where($coords)->limit(1)->fetch()->id_player;
	}
	public function search($id_player)
	{
		$activities = $this->container->activities->getTable()->select("id, type, id_player, timestamp, ((hour(from_unixtime(timestamp))*3600 + minute(from_unixtime(timestamp))*60) DIV (15*60)) AS period, count(id) AS records, max(planets) AS planet_factor")
				->where(array("id_player" => $id_player))
				->group("type, period")

				->order("period ASC");

		$results = array();
		while($r = $activities->fetch())
		{
			$results[] = $r;
		}
		$activities = array();
		$ret = array();
		// there are 4*15minutes in a hour and 24hours/day = 96
		for($i = 1; $i <= 96; $i++)
		{
			// initialize activities
			foreach($this->container->activities->types as $type)
			{
				$activities[$i][$type] = 0;
				$activities[$i]["label"] = floor(($i-1)/4);
				if((15*(($i-1)%4)) != 0)
					$activities[$i]["label"] .= ":".(15*(($i-1)%4));
				else
					$activities[$i]["label"] .= ":00";
			}

		}
		foreach($results as $result)
		{
			$value = 0;
			switch($result["type"]):
				case "galaxyview": case "inactivity":
					$value = $result["records"]/$result["planet_factor"];
					break;
				default:
					$value = $result["records"];
			endswitch;
			$activities[$result["period"]][$result["type"]] += $value;



		}
		$ret["activity"] = $activities;
		return $ret;
	}
}
