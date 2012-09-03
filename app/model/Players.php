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
		$activities = $this->container->activities->search($id_player);

		$ret["activity"] = $activities;
		return $ret;
	}
}
