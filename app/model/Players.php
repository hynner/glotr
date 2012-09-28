<?php
namespace GLOTR;
use Nette;

class Players extends Table
{
	/** @var string */
	protected $tableName = "players";
	/** @var string api filename */
	protected $apiFile = "players.xml";
	protected $numPlayers;
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
		if(!$id_player)
			return array();
		$ret["activity"]  = $this->container->activities->search($id_player);
		$ret["activity_all"]  = $this->container->activities->searchUngrouped($id_player);
		$ret["player"] = $this->getTable()->where(array("id_player_ogame" => $id_player))->fetch()->toArray();
		$ret["fs"] = $this->container->fs->search($id_player);
		if($ret["player"]["id_alliance"])
		{
			$ret["alliance"] = $this->container->alliances->find($ret["player"]["id_alliance"])->toArray();
		}
		else
		{
			$ret["alliance"] = false;
		}
		$res = $this->container->universe->findBy(array("id_player" => $id_player));
		while($r = $res->fetch())
			$ret["planets"][] = $r->toArray();

		return $ret;
	}
	/**
	 * @param type $player
	 * @param type $player2
	 */
	public function getRelativeStatus($player, $player2 = array())
	{
		$status = array();
		// first check whether player is a bandit/ star lord
		$numPlayers = $this->getNumPlayers();
		$isBandit = false;
		$isOutlaw = false;
		if(($player["score_7_position"] >= ($numPlayers - 10)) && $player["score_7"] <= -15000)
		{
			$status[] = "bandit_king";
			$isBandit = true;
		}
		elseif(($player["score_7_position"] >= ($numPlayers - 100)) && $player["score_7"] <= -2500)
		{
			$status[] = "bandit_lord";
			$isBandit = true;
		}
		elseif(($player["score_7_position"] >= ($numPlayers - 250)) && $player["score_7"] <= -500)
		{
			$status[] = "bandit";
			$isBandit = true;
		}
		elseif(($player["score_7_position"] <= 10) && $player["score_7"] >= 15000)
			$status[] = "grand_emperor";
		elseif(($player["score_7_position"] <= 100) && $player["score_7"] >= 2500)
			$status[] = "emperor";
		elseif(($player["score_7_position"] <= 250) && $player["score_7"] >= 500)
			$status[] = "star_lord";
		if($player["status"])
		{
			// now check the newbie protection
			if(strpos( $player["status"], "o") !== false)
			{
				$status[] = "outlaw";
				$isOutlaw = true;
			}

			if(strpos( $player["status"], "i") !== false)
				$status[] = "inactive";
			if(strpos($player["status"], "I") !== false)
				$status[] = "long_inactive";

			if(strpos( $player["status"],"v") !== false)
				$status[] = "v_mode";
			if(strpos($player["status"],"b") !== false)
				$status[] = "banned";
		}

		// now check relative status
		if(!empty($player2))
		{
			//check whether it is honorable target
			if($isBandit)
				$status["plunder"] = 100;
			elseif((($player["score_3"] > 0.5*$player2["score_3"]) || (($player["score_3_position"] - $player2["score_3_position"]) <= 100) || (($player2["score_3"] - $player["score_3"]) <= 10)) && !in_array("inactive", $status) && !in_array("long_inactive", $status))
				$status["plunder"] = 75;
			else
				$status["plunder"] = 50;
			$status["newbie"] = $this->getNewbieProtection($player, $player2, $status);
			// player may also be stronger
			if(!$status["newbie"])
			{
				$status["stronger"] = $this->getNewbieProtection($player2, $player);
			}

		}
		return $status;


	}
	public function getNumPlayers()
	{
		if(!$this->numPlayers)
		{
			$this->numPlayers = $this->getTable()->count("id_player");
		}
		return $this->numPlayers;
	}
	public function getNewbieProtection($player, $player2)
	{
		$isOutlaw = false;
		$isInactive = false;
		if(strpos( $player["status"], "o") !== false)
		{

			$isOutlaw = true;
		}
		if(strpos( $player["status"], "i") !== false || strpos($player["status"], "I") !== false)
				$isInactive = true;
		$srvData = $this->container->server->data;
		// check for newbie protection

		if($player["score_1"] > $srvData["newbieProtectionLimit"])
		{
			$ret = false;
		}
		else
		{
			if($player["score_1"] > $srvData["newbieProtectionHigh"])
				$ratio = 0.1;
			else
				$ratio = 0.05;
			if($player["score_1"] > $ratio*$player2["score_1"] || ($player["score_3"] > 0.5*$player2["score_3"]) || (($player["score_3_position"] - $player2["score_3_position"]) <= 100) || $isOutlaw || $isInactive)
				$ret = false;
			else
				$ret = true;
		}
		return $ret;
	}
}
