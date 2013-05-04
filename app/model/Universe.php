<?php
namespace GLOTR;
use Nette;
use Nette\Diagnostics\Debugger as DBG;
class Universe extends Table
{
	/** @var string */
	protected $tableName = "universe";
	/** @var string api filename */
	protected $apiFile = "universe.xml";
	public function updateFromApi()
	{
		$mc = microtime(1);
		$data = $this->container->ogameApi->getData($this->apiFile);

		if($data !== false)
		{
			$data->read();
			$timestamp = (int)$data->getAttribute("timestamp");

			$start = (int) $this->container->config->load("$this->tableName-start");

			$end = $start + $this->container->parameters["ogameApiRecordsAtOnce"];

			$query = "";
			$i = 0;
			while($data->read())
			{
			next_planet:
				if($data->name === "planet" && $data->nodeType == \XMLReader::ELEMENT)
				{
					$i++;

					$coords = (string) $data->getAttribute("coords");
					$coords = explode(":", $coords);
					$dbData = array(
									"id_planet_ogame" => (int) $data->getAttribute("id"),
									"name" => (string) $data->getAttribute("name"),
									"galaxy" =>  $coords[0],
									"system" =>  $coords[1],
									"position" =>  $coords[2],
									"id_player" => (int) $data->getAttribute("player"),
									"last_update" => $timestamp
								);
						$dataFields = " id_planet_ogame = $dbData[id_planet_ogame], name = '$dbData[name]',
							galaxy = $dbData[galaxy], system = $dbData[system],
							position = $dbData[position], id_player = $dbData[id_player],
							last_update = $dbData[last_update] ";


						$data->read();
						if($data->name == "moon")
						{
							$dbData["moon_name"] = (string) $data->getAttribute("name");
							$dbData["moon_size"] = (int) $data->getAttribute("size");
							$dbData["id_moon_ogame"] = (int) $data->getAttribute("id");
							$dataFields .= " , moon_name = '$dbData[moon_name]' , moon_size = $dbData[moon_size] , id_moon_ogame = $dbData[id_moon_ogame] ";
						}
						$query .= "delete from $this->tableName where position = $dbData[position] and system = $dbData[system] and galaxy = $dbData[galaxy] and id_player != $dbData[id_player];";
						$query .= " insert into $this->tableName set $dataFields on duplicate key update $dataFields;";
						// if $data->read() has moved to another planet element, it must be stored before, reading again
						if($data->name == "planet")
							goto next_planet;
				}
			}

			$this->chunkedMultiQuery($query);
			// this is the end of update, save the timestamp
			$this->container->config->save("$this->tableName-finished", $timestamp);
			$this->container->config->save("$this->tableName-start", 0);
			return true;

		}
		else
			return false;

	}
	public function search($values, $paginator = NULL)
	{
		$uniT = $this->tableName;
		$playersT = $this->container->players->getTableName();
		$tmp = array();
		$escaped = array("playername", "alli_tag");
		foreach($escaped as $key)
			if(isset($values["$key"]) && $values["$key"])
			{
				$tmp["$key"] =$values["$key"];

				// escape MySQL wildcards
				$values["$key"] = str_replace("_", "\\_", $values["$key"]);
				$values["$key"] = str_replace("%", "\\%", $values["$key"]);

				// convert wildcards to MySQL wildcards
				$values["$key"] = str_replace("*", "%", $values["$key"]);
				$values["$key"] = str_replace("?", "_", $values["$key"]);
			}

		$alliances_columns = $this->container->alliances->getPrefixedColumnList("_");
		$columns = "$uniT.*, $alliances_columns $playersT.*";
		if(!is_null($paginator))
		{
			$count = $this->sendQuery("count($uniT.id_planet) as count", $values);
			$paginator->setItemCount($count[0]["count"]);
			$paginator->setItemsPerPage(((isset($values["results_per_page"]) && $values["results_per_page"] !== "")) ? $values["results_per_page"] : 20);
		}

		$results = $this->sendQuery($columns, $values, $paginator);
		foreach($escaped as $key)
		if(isset($values["$key"]) && $values["$key"])
		{
			$values["$key"] =$tmp["$key"];
		}
		return $results;

	}
	/**
	 * Insert planet data into database
	 * @param array $dbData - contains all the neccessary info about planet
	 * @return bool
	 */
	public function insertPlanet($dbData)
	{
		$coords = array("galaxy" => $dbData["galaxy"], "system" => $dbData["system"], "position" => $dbData["position"]);

		try{ // i can´t safely delete the old records, because some dbData might be associated with it via old GLOTR id_planet
			$this->getTable()->insert($dbData);

		}
		catch(\PDOException $e)
		{
			// exception means, there is already planet on that position in dbDatabase
			// first I try to delete planet with the same coordinates but different id_player
			$this->getTable()->where($coords)->where("NOT id_player",$dbData["id_player"] )->delete();

			try{ // problem could be solved, try insert again
				$this->getTable()->insert($dbData);
			}
			catch(\PDOException $e)
			{
				// exception means there is a planet on this coordinates and it is the planet of the same player, update it
				$this->getTable()->where($coords)->update($dbData);

			}
		}
		return true;
	}
	/**
	 * deletes the planet with given coordinates from database
	 * @param array $coords required indexes galaxy, system, position
	 * @return bool
	 */
	public function deletePlanet($coords)
	{
		$this->getTable()->where($coords)->delete();
		return true;
	}

	protected function sendQuery($what, $values, $paginator = NULL)
	{
		$uniT = $this->tableName;
		$playersT = $this->container->players->getTableName();
		$allianceT = $this->container->alliances->getTableName();
		$scoreT = $this->container->highscore->getTableName();
		$select = "select $what from $uniT left join $playersT on $uniT.id_player = $playersT.id_player_ogame left join $allianceT on $allianceT.id_alliance_ogame = $playersT.id_alliance where 1  ";
		$params = array();

		// if both values are set search with OR
		if((isset($values["playername"]) && $values["playername"] !== "") && (isset($values["alli_tag"]) && $values["alli_tag"] !== ""))
		{

			$select .= "and ($playersT.playername like ? OR $allianceT.tag like ?)";
			$params[] = $values["playername"];
			$params[] = $values["alli_tag"];
		}
		else
		{

			if((isset($values["playername"]) && $values["playername"] !== ""))
			{

					$select .= " and $playersT.playername like ? ";
					$params[] = $values["playername"];
			}
			if((isset($values["alli_tag"]) && $values["alli_tag"] !== ""))
			{
				$select .= " and $allianceT.tag like ?";
					$params[] = $values["alli_tag"];
			}
		}
		if((isset($values["galaxy"]) && $values["galaxy"] !== ""))
		{
			$select .= " and $uniT.galaxy = ?";
			$params[] = $values["galaxy"];
		}
		if((isset($values["system_start"]) && $values["system_start"] !== ""))
		{
			$select .= " and $uniT.system >= ?";
			$params[] = $values["system_start"];
		}
		if((isset($values["system_end"]) && $values["system_end"] !== ""))
		{
			$select .= " and $uniT.system <= ?";
			$params[] = $values["system_end"];
		}
		if((isset($values["score_0_position_s"]) && $values["score_0_position_s"] !== ""))
		{
			$select .= " and $playersT.score_0_position > ?";
			$params[] = $values["score_0_position_s"];
		}
		if((isset($values["score_0_position_e"]) && $values["score_0_position_e"] !== ""))
		{
			$select .= " and $playersT.score_0_position < ?";
			$params[] = $values["score_0_position_e"];
		}
		if((isset($values["score_3_position_s"]) && $values["score_3_position_s"] !== ""))
		{
			$select .= " and $playersT.score_3_position > ?";
			$params[] = $values["score_3_position_s"];
		}
		if((isset($values["score_3_position_e"]) && $values["score_3_position_e"] !== ""))
		{
			$select .= " and $playersT.score_0_position < ?";
			$params[] = $values["score_3_position_e"];
		}
		if((isset($values["score_6_position_s"]) && $values["score_6_position_s"] !== ""))
		{
			$select .= " and $playersT.score_6_position > ?";
			$params[] = $values["score_6_position_s"];
		}
		if((isset($values["score_6_position_e"]) && $values["score_6_position_e"] !== ""))
		{
			$select .= " and $playersT.score_6_position < ?";
			$params[] = $values["score_6_position_e"];
		}
		if((isset($values["score_7_position_s"]) && $values["score_7_position_s"] !== ""))
		{
			$select .= " and $playersT.score_7_position > ?";
			$params[] = $values["score_7_position_s"];
		}
		if((isset($values["score_7_position_e"]) && $values["score_7_position_e"] !== ""))
		{
			$select .= " and $playersT.score_7_position < ?";
			$params[] = $values["score_7_position_e"];
		}
		if((isset($values["with_moons"]) && $values["with_moons"] ))
		{
			$select .= " and  $uniT.id_moon_ogame  is not null";
		}
		if((isset($values["statuses"]) && $values["statuses"] ))
		{

			if(!$values["statuses"]["all"])
			{
				$select .= " and ( 0 "; // 0 so I don´t have to care about OR statement
				$not = " and ( 1  ";
				if($values["statuses"]["inactives"])
				{
					$select .= " or $playersT.status  like \"%i%\" ";
				}
				else // if it is false it means, players can´t be inactives!
				{
					$not .= " and $playersT.status not like \"%i%\" ";
				}

				if($values["statuses"]["vmode"])
				{
					$select .= " or $playersT.status  like \"%v%\" ";
				}
				else
				{
					$not .= " and $playersT.status not  like \"%v%\"  ";
				}

				if($values["statuses"]["banned"])
				{
					$select .= " or $playersT.status  like \"%b%\" ";
				}
				else
				{
					$not .= " and $playersT.status not  like \"%b%\"  ";
				}
				$not .= " ) ";
				$select .=  ") $not" ;

			}

		}

		if((isset($values["order_direction"]) && $values["order_direction"] !== "") && (isset($values["order_by"]) && $values["order_by"] !== ""))
		{
			$dir = "asc";
			if($values["order_direction"] == "desc")
				$dir = "desc";
			$select .= " order by ";

			switch($values["order_by"])
			{
				case "coords":
					$select .= "$uniT.galaxy $dir, $uniT.system $dir, $uniT.position $dir";
					break;
				case "player":
					$select .= "$playersT.playername $dir";
					break;
				case "tag":
					$select .= "$allianceT.tag $dir";
					break;
				default:
					$select .= "$uniT.id_planet $dir";
					break;
			}
		}
		if(!is_null($paginator))
		{
			$select .= " limit ?, ?";
			$params[] =$paginator->getOffset();
			$params[] = $paginator->getLength();
		}

		return $this->invokeQuery($select, $params);

	}

}
