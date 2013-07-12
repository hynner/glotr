<?php
namespace GLOTR;
use Nette;

class Players extends OGameApiModel
{
	/** @var string */
	protected $tableName = "players";
	/** @var string api filename */
	protected $apiFile = "players.xml";
	/**
	 * Update from OgameApi
	 * @return boolean
	 */
	public function updateFromApi()
	{
		$data = $this->ogameApi->getData($this->apiFile);
		if($data !== false)
		{
			$data->read();
			$timestamp = (int)$data->getAttribute("timestamp");

			$query = "";
			while($data->read())
			{
				if($data->name === "player" && $data->nodeType == \XMLReader::ELEMENT)
				{
				$dbData = array(
								"id_player_ogame" => (int) $data->getAttribute("id"),
								"playername" => (string) $data->getAttribute("name"),
								"status" =>  (string) $data->getAttribute("status"),
								"id_alliance" => (int) $data->getAttribute("alliance"),
								"last_update" => $timestamp
								);
				$dataFields = " id_player_ogame = $dbData[id_player_ogame], playername = '$dbData[playername]', status = '$dbData[status]',id_alliance = $dbData[id_alliance],last_update = $dbData[last_update] ";
				$query .= " insert into $this->tableName set $dataFields on duplicate key update $dataFields;";

				}

			}
			$this->chunkedMultiQuery($query);
			// this is the end of update, save the timestamp
			$this->config->save("$this->tableName-finished", $timestamp);
			$this->config->save("$this->tableName-start", 0);

			return true;

		}
		else
			return false;

	}
	/**
	 * Insert player
	 * @param array $dbData
	 * @return boolean
	 */
	public function insertPlayer($dbData)
	{
		try{

				$this->getTable()->insert($dbData);
			}

			catch(\PDOException $e)
			{
					$this->getTable()->where(array("id_player_ogame" => $dbData["id_player_ogame"]))
							->where("last_update < ? OR last_update IS NULL", $dbData["last_update"])->update($dbData);

			}
		return true;
	}
	/**
	 * Assign player to an alliance
	 * @param int $id_player
	 * @param int $id_alliance
	 * @param int $last_update timestamp
	 * @return boolean
	 */
	public function setAlliance($id_player, $id_alliance, $last_update = NULL)
	{
		if($last_update === NULL) $last_update = time();
		$this->getTable()->where("id_player_ogame" , $id_player)
				->where("last_update < ? OR last_update IS NULL", $last_update)->update(array("id_alliance" => $id_alliance));
		return true;
	}
	/**
	 * Set researches for player
	 * @param int $id_player
	 * @param array $dbData
	 * @return boolean
	 */
	public function setResearches($id_player, $dbData)
	{
		$this->getTable()->where("id_player_ogame" , $id_player)
				->where("research_updated < ? OR research_updated IS NULL", $dbData["research_updated"])->update($dbData);
		return true;
	}
	/**
	 * Search information about player
	 * @param int $id_player
	 * @return array
	 */
	public function search($id_player)
	{
		if(!$id_player)
			return $ret;

		$ret = $this->getTable()->where(array("id_player_ogame" => $id_player))->fetch();
		if($ret === FALSE)
			return $ret;
		$ret = $ret->toArray();

		if($ret["player_status_global"] == "")
			$ret["player_status_global"] = "neutral";
		return $ret;
	}
	/**
	 * Get local dimplomacy status for player
	 * @param int $id_player
	 * @param int $id_user
	 * @return string
	 */
	public function getLocalPlayerStatus($id_player, $id_user)
	{
		$tblName = $this->params["tablePrefix"]."users_players_statuses";
		$r = $this->connection->table($tblName)->where(array("id_user" => $id_user, "id_player_ogame" => $id_player))->fetch();
		$status = "";
		if($r !== FALSE)
			$status = $r->player_status_local;

		if($status == "")
			$status = "neutral";
		return $status;

	}
	/**
	 * Get relative player status
	 * @param array $player foreign player
	 * @param array $player2 own player
	 * @return array
	 */
	public function getRelativeStatus($player, $player2 = array())
	{
		$status = array();
		// first check whether player is a bandit/ star lord
		$numPlayers = $this->getCount();
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
			if(strpos($player["status"],"a") !== false)
				$status[] = "admin";
		}

		// now check relative status
		if(!empty($player2))
		{
			if($player["id_player_ogame"] == $player2["id_player_ogame"])
			{
				$status["player-self"] = true;
			}
			else
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

		}
		return $status;
	}
	/**
	 * get CSS classes for player status array
	 * @param array $status
	 * @return string
	 */
	public function getClassForPlayerStatus($status)
	{
		$ret = "";
		foreach($status as $key => $value)
		{

			if(is_integer($key))
				$ret .= " ".$value;
			elseif(is_bool($value) && $value)
				$ret .= " ".$key;
			elseif(!is_bool($value))
				$ret .= " "."$key-$value";
		}
		return $ret;
	}
	/**
	 * Check whether a player is in noob protection for player2
	 * @param array $player
	 * @param array $player2
	 * @return boolean
	 */
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
		$srvData = $this->glotrApi->getServerData();
		// check for newbie protection

		if($player["score_0"] > $srvData["newbieProtectionLimit"])
		{
			$ret = false;
		}
		else
		{
			if($player["score_0"] > $srvData["newbieProtectionHigh"])
				$ratio = 0.1;
			else
				$ratio = 0.2;
			if($player["score_0"] >= $ratio*$player2["score_0"] || ($player["score_3"] > 0.5*$player2["score_3"]) || (($player["score_3_position"] - $player2["score_3_position"]) <= 100) || $isOutlaw || $isInactive)
				$ret = false;
			else
				$ret = true;
		}
		return $ret;
	}
	/**
	 * Set player´s dimplomacy status
	 * @param int $id_player
	 * @param string $status
	 * @param string $mode global|local
	 * @param int $id_user
	 */
	public function setPlayerStatus($id_player, $status, $mode, $id_user)
	{
		if($mode == "global" && $this->glotrApi->checkPermissions("perm_diplomacy"))
			$this->getTable()->where(array("id_player_ogame" => $id_player))->update(array("player_status_global" => $status));
		elseif($mode == "local")
		{
			$tblName = $this->params["tablePrefix"]."users_players_statuses";
			$dbData = array(
				"id_user" => $id_user,
				"id_player_ogame" => $id_player,
				"player_status_local" => $status
			);
			try{
				$this->connection->table($tblName)->insert($dbData);
			}
			catch(\PDOException $e)
			{
				// settings for this user/player combination already exists, update it
				$this->connection->table($tblName)->where(array("id_user" => $id_user, "id_player_ogame" => $id_player))->update($dbData);
			}
		}
	}
}
