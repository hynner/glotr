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
		$data = $this->container->ogameApi->getData($this->apiFile);

		if($data !== false)
		{

			$timestamp = (int)$data["timestamp"];
			// data in ogame api are always valid at the time of their creation, so I can delete all older data, because they will be replaced anyway
			// this way I also get rid of nonexisting planets
			if($data->planet)
				$res = $this->getTable()->where("last_update < ?", $timestamp)->delete();
			$start = (int) $this->container->config->load("$this->tableName-start");

			$end = $start + $this->container->parameters["ogameApiRecordsAtOnce"];

			for($i = $start; $i < $end; $i++)
			{

				if($data->planet[$i])
				{
					$planet = $data->planet[$i];
				}

				else
					{
						// this is the end of update, save the timestamp
						$this->container->config->save("$this->tableName-finished", $timestamp);
						$this->container->config->save("$this->tableName-start", 0);
						return true;
					}

				$name = (string) $planet["name"];
				$id = (int) $planet["id"];
				$player = (int) $planet["player"];


				$coords = (string) $planet["coords"];
				$coords = explode(":", $coords);
				$dbData = array(
								"id_planet_ogame" => $id,
								"name" => $name,
								"galaxy" =>  $coords[0],
								"system" =>  $coords[1],
								"position" =>  $coords[2],
								"id_player" => $player,
								"last_update" => $timestamp
								);
				if($planet->moon)
				{
					$moon = $planet->moon;
					$name = (string) $moon["name"];
					$mid = (int) $moon["id"];
					$size = (int) $moon["size"];
					$dbData["moon_name"] = $name;
					$dbData["moon_size"] = $size;
					$dbData["id_moon_ogame"] = $mid;

				}


				$this->insertPlanet($dbData);





			}
			$this->container->config->save("$this->tableName-start", $end);
			return true;
		}
		else
			return false;

	}
	public function search($values, $paginator = NULL)
	{
		$uniT = $this->tableName;
		$playersT = $this->container->players->getTableName();


		$alliances_columns = $this->container->alliances->getPrefixedColumnList("_");
		$columns = "$uniT.*, $alliances_columns $playersT.*";
		if(!is_null($paginator))
		{
			$count = $this->sendQuery("count($uniT.id_planet) as count", $values);
			$paginator->setItemCount($count[0]["count"]); // celkový počet položek (např. článků)
			$paginator->setItemsPerPage($values["results_per_page"]); // počet položek na stránce
		}

		$results = $this->sendQuery($columns, $values, $paginator);

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
		if($values["playername"] && $values["alli_tag"])
		{
			$select .= "and ($playersT.playername like ? OR $allianceT.tag like ?)";
			$params[] = $values["playername"];
			$params[] = $values["alli_tag"];
		}
		else
		{
			if($values["playername"])
			{
					$select .= " and $playersT.playername like ? ";
					$params[] = $values["playername"];
			}
			if($values["alli_tag"])
			{
				$select .= " and $allianceT.tag like ?";
					$params[] = $values["alli_tag"];
			}
		}
		if($values["galaxy"])
		{
			$select .= " and $uniT.galaxy = ?";
			$params[] = $values["galaxy"];
		}
		if($values["system_start"])
		{
			$select .= " and $uniT.system > ?";
			$params[] = $values["system_start"];
		}
		if($values["system_end"])
		{
			$select .= " and $uniT.system < ?";
			$params[] = $values["system_end"];
		}
		if($values["score_0_position_s"])
		{
			$select .= " and $playersT.score_0_position > ?";
			$params[] = $values["score_0_position_s"];
		}
		if($values["score_0_position_e"])
		{
			$select .= " and $playersT.score_0_position < ?";
			$params[] = $values["score_0_position_e"];
		}
		if($values["score_3_position_s"])
		{
			$select .= " and $playersT.score_3_position > ?";
			$params[] = $values["score_3_position_s"];
		}
		if($values["score_3_position_e"])
		{
			$select .= " and $playersT.score_0_position < ?";
			$params[] = $values["score_3_position_e"];
		}
		if($values["score_6_position_s"])
		{
			$select .= " and $playersT.score_6_position > ?";
			$params[] = $values["score_6_position_s"];
		}
		if($values["score_6_position_e"])
		{
			$select .= " and $playersT.score_6_position < ?";
			$params[] = $values["score_6_position_e"];
		}
		if($values["score_7_position_s"])
		{
			$select .= " and $playersT.score_7_position > ?";
			$params[] = $values["score_7_position_s"];
		}
		if($values["score_7_position_e"])
		{
			$select .= " and $playersT.score_7_position < ?";
			$params[] = $values["score_7_position_e"];
		}
		if($values["with_moons"])
		{
			$select .= " and  $uniT.id_moon_ogame  is not null";
		}
		$dir = "asc";
			if($values["order_direction"] == "desc")
				$dir = "desc";
		if($values["order_direction"] && $values["order_by"])
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

		$args = array();
		$args[] = $select;
		$args = array_merge($args, $params);
		$method = new \ReflectionMethod("Nette\Database\Connection", "query");

		// calls $this->container->universe->getConnection()->query() with each item from $args as a single argument
		$res = $method->invokeArgs($this->connection, $args);
		$results = array();
		while($result = $res->fetch())
		{
			$results[] = $result;
		}
		return $results;

	}

}
