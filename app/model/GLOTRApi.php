<?php
namespace GLOTR;

class GLOTRApi extends \Nette\Object
{
	/** @var \Nette\DI\Container */
	protected $container;
	public function __construct(\Nette\DI\Container $container)
	{
		// It´s a break of DI rules, but this class needs to communicate with everything
		$this->container = $container;
	}
	/**
	 * Get table name for service
	 * @param string $service
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function getTableName($service)
	{
		if($this->container->$service instanceof Table)
		{
			return $this->container->$service->getTableName();
		}
		else
		{
			throw new \InvalidArgumentException("Requested service doesn´t have a table!");
		}
	}
	/**
	 * Get table for service
	 * @param string $service service name
	 * @return \Nette\Database\Table\Selection
	 * @throws \InvalidArgumentException
	 */
	public function getTable($service)
	{
		if($this->container->$service instanceof Table)
		{
			return $this->container->$service->getTable();
		}
		else
		{
			throw new \InvalidArgumentException("Requested service doesn´t have a table!");
		}
	}
	/**
	 * Get row count for the service
	 * @param string $service
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function getCount($service)
	{
		if($this->container->$service instanceof Table)
		{
			return $this->container->$service->getCount();
		}
		else
		{
			throw new \InvalidArgumentException("Requested service doesn´t have a table!");
		}
	}
	/**
	 * Get player data
	 * @param int $id_player
	 * @param array $userPlayer for local and relative status
	 * @param array $keys which keys do you want to include - player, alliance, activity, activity_all, fs, planets, score_inactivity or all - default is player only
	 * @param array $actFilter activity filter
	 * @return array
	 */
	public function getPlayerDetail($id_player, $userPlayer = array(), $keys = array(), $actFilter = array())
	{
		if(!$keys) $keys = array("player");
		$all = in_array("all", $keys);
		$ret = array();
		if($all || in_array("player", $keys))
		{
			$ret["player"] = $this->container->players->search($id_player);
			if($ret["player"] !== FALSE)
			{
				// find user´s personal status for this player
				if(isset($userPlayer["id_user"]))
				{
					$ret["player"]["player_status_local"] = $this->container->players->getLocalPlayerStatus($id_player, $userPlayer["id_user"]);
				}
				if($all || in_array("alliance", $keys))
				{
					if($ret["player"]["id_alliance"])
					{
						$ret["alliance"] = $this->container->alliances->find($ret["player"]["id_alliance"])->toArray();
					}
					else
					{
						$ret["alliance"] = false;
					}
				}
				$ret["player"]["relative_status"] = $this->container->players->getRelativeStatus($ret["player"], $userPlayer);
				$ret["player"]["_computed_status_class"] = $this->container->players->getClassForPlayerStatus($ret["player"]["relative_status"]);
			}

		}
		if($all || in_array("activity", $keys))
		{
			$ret["activity"]  = $this->container->activities->search($id_player, $actFilter);
		}
		if($all || in_array("activity_all", $keys))
		{
			$ret["activity_all"]  = $this->container->activities->searchUngrouped($id_player, $actFilter);
		}
		if($all || in_array("fs", $keys))
		{
			$ret["fs"] = $this->container->fs->search($id_player);
		}
		if($all || in_array("score_inactivity", $keys))
		{
			$ret["score_inactivity"] = $this->container->scoreInactivity->getByPlayer($id_player);
		}
		if($all || in_array("planets", $keys))
		{
			$ret["planets"] = array();

			$res = $this->container->universe->findBy(array("id_player" => $id_player));
			while($r = $res->fetch())
			{
				$ret["planets"][] = $r->toArray();
			}
			if($ret["planets"])
			{
				$ret["moons"] = $this->getMoonsFromPlanets($ret["planets"]);
			}
		}
		return $ret;
	}

	/**
	 * Get alliance detail by id
	 * @param int $id_alliance
	 * @param array $userPlayer
	 * @param array $keys which keys do you want to include - alliance, players, planets, all - default = alliance
	 * @return array
	 */
	public function getAllianceDetail($id_alliance, $userPlayer = array(), $keys = array())
	{
		$ret  = array();
		if(!$keys) $keys = array("player");
		$all = in_array("all", $keys);
		if($all || in_array("alliance", $keys))
		{
			$res = $this->container->alliances->find($id_alliance);
			if($res !== FALSE)
				$ret["alliance"] = $res->toArray();
			else
				$ret["alliance"] = FALSE;
		}
		if($all || in_array("players", $keys))
		{
			$res = $this->container->players->findBy(array("id_alliance" => $id_alliance));
			while($r = $res->fetch())
			{
				$ret["players"][$r->id_player_ogame] = $r->toArray();
				$ret["players"][$r->id_player_ogame]["relative_status"] = $this->container->players->getRelativeStatus($ret["players"][$r->id_player_ogame], $userPlayer);
				$ret["players"][$r->id_player_ogame]["_computed_status_class"] = $this->container->players->getClassForPlayerStatus($ret["players"][$r->id_player_ogame]["relative_status"]);
			}

			if($all || in_array("planets", $keys))
			{
					$res = $this->container->universe->getTable()->where("id_player", array_keys($ret["players"]))->order("galaxy ASC, system ASC, position ASC");
					while($r = $res->fetch())
						$ret["planets"][] = $r->toArray();
					$ret["moons"] = $this->getMoonsFromPlanets($ret["planets"]);
			}

		}
		return $ret;
	}
	/**
	 * Get current fleet movements
	 * @param array $userPlayer
	 * @param \Nette\Utils\Paginator $paginator
	 * @return array
	 */
	public function getFleetMovements($userPlayer = array(), $paginator = NULL)
	{
		return $this->container->fleetMovements->search($userPlayer, $paginator);
	}
	/**
	 * Get score history for players/alliances
	 * @param array $search
	 * @return array
	 */
	public function getScoreHistory($search)
	{
		return $this->container->highscore->scoreHistory($search);
	}
	/**
	 * Get Ogame server data
	 * @param string $key optional, if provided only value for specified key will be returned
	 * @return mixed
	 */
	public function getServerData($key = NULL)
	{
		$tmp = $this->container->server->getData();
		if($key === NULL) return $tmp;
		return $tmp[$key];
	}
	/**
	 * get planet by id
	 * @param integer $id_planet
	 * @return \Nette\Database\Table\ActiveRow|FALSE
	 */
	public function getPlanetDetail($id_planet)
	{
		return $this->container->universe->find($id_planet);
	}
	/**
	 * @param array $coords keys galaxy, system, position
	 * @return \Nette\Database\Table\ActiveRow|FALSE
	 */
	public function getPlanetByCoords($coords)
	{
		return $this->container->universe->getTable()->where($coords)->limit(1)->fetch();
	}
	/**
	 * Check if logged user has permissions
	 * @param mixed $perm
	 * @return boolean
	 */
	public function checkPermissions($perm)
	{
		return $this->container->authenticator->checkPermissions($perm);
	}

	/**
	 * Given the array of planets return the array of planets that have moon
	 * @param array $planets
	 * @return array
	 */
	protected function getMoonsFromPlanets($planets)
	{
		$moons = array();
			foreach($planets as $planet)
				if($planet["moon_size"] || $planet["moon_res_updated"])
					$moons[] = $planet;
		return $moons;
	}
	/**
	 * Updates planet/moon data
	 * @param integer $id_planet
	 * @param string $what - resources,buildings,fleet,defence
	 * @param array $dbData data to be updated in form column_name => value, calling method must ensure that only relevant data are passed
	 * @param integer $timestamp
	 * @param boolean $moon
	 * @param string $id_field ogame workaround id_planet_ogame|id_moon_ogame
	 * @return integer|boolean
	 * @throws \Nette\InvalidArgumentException
	 */
	public function updatePlanet($id_planet,$what, $dbData, $timestamp,  $moon, $id_field = "id_planet_ogame")
	{
		$prefix = ($moon) ? "moon" : "planet";
		switch($what)
		{
			case "resources":
				$key = $prefix."_res_updated";
				break;
			case "buildings":
				$key = $prefix."_build_updated";
				break;
			case "fleet":
				$key = $prefix."_fleet_updated";
				break;
			case "defence":
				$key = $prefix."_defence_updated";
				break;
			default:
				throw new \Nette\InvalidArgumentException("Unknown \$what parameter '$what' . Allowed values are resources, buildings, fleet and defence!");
				break;
		}
		$dbData[$key] = $timestamp;
		return $this->container->universe->getTable()->where($id_field, $id_planet)
						->where("$key < ? OR $key IS NULL", $timestamp)
						->update($dbData);
	}
	/**
	 * Update player - playername, researches and alliance
	 * @param integer $id_player
	 * @param array $data
	 */
	public function updatePlayer($id_player, $data)
	{
		if(!empty($data["researches"]))
		{
			$res = $data["researches"];
			$res["research_updated"] = $data["timestamp"];
			$this->container->players->setResearches($id_player, $res);
		}
		if(isset($data["id_alliance"]))
		{
			$this->container->players->setAlliance($id_player, $data["id_alliance"], $data["timestamp"]);
		}
		if(isset($data["playername"]))
		{
			$this->container->players->insertPlayer(array("id_player_ogame" => $id_player, "playername" => $data["playername"], "last_update" => $data["timestamp"]));
		}

	}
	public function updateAlliance($id_alliance, $data)
	{
		if($data["alliance"])
		{
			$ali = $data["alliance"];
			$ali["last_update"] = $data["timestamp"];
			$ali["id_alliance_ogame"] = $id_alliance;
			$this->container->alliances->insertAlliance($ali);
		}
		if($data["members"])
		{
			foreach($data["members"] as $id_player => $activity)
			{
				$this->container->players->setAlliance($id_player, $id_alliance, $data["timestamp"]);
				// NULL means that current player can´t see online statuses
				if($activity !== NULL)
				{
					$act =  array("id_player" => $id_player);
					if($activity)
					{
						$act["timestamp"] = $activity;
						$this->container->activities->insertActivityMultiple($act, $data["timestamp"], "alliance_page", "apg_inactivity");
					}
					else
					{
						$act["timestamp"] = $data["timestamp"];
						$this->container->activities->insertInactivity($act, "apg_inactivity");
					}
				}
			}
		}

	}
	/**
	 * Get score inactivity records
	 * @param \Nette\Utils\Paginator $paginator
	 * @param array $filter
	 * @return array
	 */
	public function searchScoreInactivity($paginator = NULL, $filter = array())
	{
		return $this->container->scoreInactivity($paginator, $filter);
	}
	/**
	 * search planets/moons by given parameters, include neccessary information like players, alliances
	 * @param array $values search form values
	 * @param Nette\Utils\Paginator $paginator
	 * @return array
	 */
	public function searchUniverse($values, $paginator = NULL)
	{
		$uniT = $this->container->universe->getTableName();
		$playersT = $this->container->players->getTableName();
		$tmp = array();
		$escaped = array("playername", "alli_tag");
		foreach($escaped as $key)
			if(isset($values["$key"]) && $values["$key"])
			{
				$tmp["$key"] = $values["$key"];

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
			$count = $this->sendUniverseSearchQuery("count($uniT.id_planet_ogame) as count", $values);
			$paginator->setItemCount($count[0]["count"]);
			$paginator->setItemsPerPage(((isset($values["results_per_page"]) && $values["results_per_page"] !== "")) ? $values["results_per_page"] : 20);
		}

		$results = $this->sendUniverseSearchQuery($columns, $values, $paginator);
		foreach($escaped as $key)
		if(isset($values["$key"]) && $values["$key"])
		{
			$values["$key"] =$tmp["$key"];
		}
		return $results;

	}
	/**
	 * runs universe search query
	 * @param string $what what to select
	 * @param array $values filter
	 * @param Nette\Utils\Paginator $paginator
	 * @return array
	 */
	protected function sendUniverseSearchQuery($what, $values, $paginator = NULL)
	{
		$uniT = $this->container->universe->getTableName();
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
					$select .= "$uniT.id_planet_ogame $dir";
					break;
			}
		}
		if(!is_null($paginator))
		{
			$select .= " limit ?, ?";
			$params[] =$paginator->getOffset();
			$params[] = $paginator->getLength();
		}

		return $this->container->universe->invokeQuery($select, $params);

	}
	public function insertEspionage($data)
	{
		$this->container->espionages->insertEspionage($data);
	}
	/**
	 * Get array with espionage keys
	 * @param string|NULL $key optional, if filled it will return only $key subarray
	 * @return array
	 */
	public function getEspionageKeys($key = NULL)
	{
		$ret = $this->container->espionages->getAllInfo();
		if($key === NULL)
			return $ret;
		else
			return $ret[$key];
	}
	/**
	 * return fleet keys without solar satellite
	 * @return array
	 */
	public function getFleetKeys()
	{
		return $this->container->fleetMovements->getFleetKeys();
	}
	/**
	 * @return array
	 */
	public function getActivityTypes()
	{
		return $this->container->activities->getTypes();
	}
	/**
	 * Get espionage archive for given planet
	 * @param integer $id_planet this is GLOTR´s internal ID
	 * @param integer $moon 0|1
	 * @return array
	 */
	public function getEspionageArchive($id_planet, $moon = 0, $userPlayer = array())
	{
		$ret = array(
			"labels" => $this->getEspionageKeys(),
			"reports" => $this->container->espionages->getTable()->where(array("id_planet" => $id_planet, "moon" => $moon))->select("*")->fetchPairs("id_message_ogame"),
			"planet" => $this->container->universe->getTable()->where(array("id_planet_ogame" => $id_planet))->fetch()
		);
		$ret = array_merge($ret, $this->getPlayerDetail($ret["planet"]->id_player, $userPlayer, array("player")));

		if($ret["reports"])
		{
			foreach($ret["reports"] as $id => $r)
			{
				$ret["reports"][$id] = $r->toArray();
			}
		}
		if($ret["planet"])
		{
			$ret["planet"] = $ret["planet"]->toArray();
		}
		return $ret;
	}
	/**
	 * @return \Nette\Database\Table\Selection
	 */
	public function getSyncServersNeedingUpdate()
	{
		return $this->container->syncServers->getServersNeedingUpdate();
	}
	/**
	 * Update sync servers parameters
	 * @param integer $id_server
	 * @param array $data
	 * @return integer
	 */
	public function updateSyncServer($id_server, $data)
	{
		return $this->container->syncServers->find($id_server)->update($data);
	}
	/**
	 * TransferPacket Factory
	 * @param array $availComp available compressions
	 * @param array $targetComp target compressions
	 * @return \TransferPacker
	 */
	public function createTransferPacket($availComp, $targetComp)
	{
		return $this->container->createTransferPacket($availComp, $targetComp);
	}
	/**
	 * httpSocket Factory
	 * @param string $host
	 * @param string $file
	 * @param integer $port
	 * @param integer $conn_timeout
	 * @param integer $timeout
	 * @return \GLOTR\httpSocket
	 */
	public function createHttpSocket($host, $file, $port = 80, $conn_timeout = NULL,$timeout = NULL )
	{
		return $this->container->createHttpSocket($host, $file, $port, $conn_timeout, $timeout);
	}
	/**
	 * Get array of available compressions
	 * @return array
	 */
	public function getCompressions()
	{
		return $this->container->sync->getCompressions();
	}
	/**
	 * Upload updates to sync server
	 * @param array $server
	 * @param string $data
	 * @return boolean
	 */
	public function syncUpload($server, $data)
	{
		return $this->container->syncServers->upload($server, $data);
	}
	public function syncVerify($id_server)
	{
		return $this->container->syncServers->verify($id_server);
	}
	/**
	 * Download updates from server
	 * @param array $server
	 * @return array downloaded updates
	 */
	public function syncDownload($server)
	{
		return $this->container->syncServers->download($server);
	}
	/**
	 * Applies update
	 * @param array $args
	 * @return boolean
	 */
	public function syncApply($args)
	{
		if(!isset($args["action"]) || !isset($args["data"]) || !isset($args["compression"]) || !isset($args["timestamp"]))
			return false;
		switch($args["action"])
		{
			case "Galaxyplugin":
				return $this->container->gtp->update($args["data"], $args["timestamp"]);
				break;
			default:
				return false;
				break;
		}
		return true;
	}
	/**
	 * Get player1 status relatively to player2
	 * @param array $player1
	 * @param array $player2
	 * @return array
	 */
	public function getRelativePlayerStatus($player1, $player2)
	{
		return $this->container->players->getRelativeStatus($player1, $player2);
	}
	/**
	 * Get CSS classes string for player status
	 * @param array $status
	 * @return string
	 */
	public function getClassForPlayerStatus($status)
	{
		return $this->container->players->getClassForPlayerStatus($status);
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
		return $this->container->players->setPlayerStatus($id_player, $status, $mode, $id_user);
	}
	/**
	 * Insert fleetsave
	 * @param array $data
	 * @return boolean
	 */
	public function insertFS($data)
	{
		return $this->container->fs->insertFS($data);
	}
	/**
	 * @param array $data
	 * @return boolean
	 */
	public function insertActivity($data)
	{
		return $this->container->activities->insertActivity($data);
	}
	/**
	 * Insert data from galaxy
	 * @param array $data
	 * @return boolean
	 * @throws \Nette\Application\BadRequestException
	 */
	public function insertSystem($data)
	{
		if(
			!isset($data["galaxy"], $data["system"], $data["planets"], $data["timestamp"])
			|| intval($data["galaxy"]) > $this->getServerData("galaxies") || intval($data["system"]) > $this->getServerData("systems")
			|| intval($data["system"]) < 1 || intval($data["galaxy"]) < 1 || empty($data["planets"])
		)
		{
			throw new \Nette\Application\BadRequestException("Wrong arguments for system insertion!");
		}
		$time = $data["timestamp"];
		// to avoid updating these uselessly
		$updated_allis = array();
		$update_players = array();
		$player_planet_count = array();
		foreach($data["planets"] as $position => $planet)
		{
			$coords = array(
				"galaxy" => $data["galaxy"],
				"system" => $data["system"],
				"position" => $position,
			);
			// if position is empty delete any potential planet
			if(empty($planet))
			{
				$this->container->universe->deletePlanet($coords);
			}
			else
			{
				// first update planet
				$dbData = array(
					"name" => $planet["name"],
					"id_planet_ogame" => $planet["id"],
					"galaxy" => $data["galaxy"],
					"system" => $data["system"],
					"position" => $position,
					"debris_metal" => ((isset($planet["df"]["metal"]) ? $planet["df"]["metal"] : 0)),
					"debris_crystal" => ((isset($planet["df"]["crystal"]) ? $planet["df"]["crystal"] : 0)),
					"id_player" => $planet["player"]["id"],
					"id_moon_ogame" => ((isset($planet["moon"]["id"])) ? $planet["moon"]["id"] : NULL),
					"moon_size" => ((isset($planet["moon"]["size"])) ? $planet["moon"]["size"] : NULL),
					"moon_name" => ((isset($planet["moon"]["name"])) ? $planet["moon"]["name"] : NULL),
					"last_update" => $time
				);
				$this->container->universe->insertPlanet($dbData);
				// if player is already updated, alliance is updated too
				if(!in_array($planet["player"]["id"], $update_players))
				{
					// now update the player
					$player = $planet["player"];
					$alliance = $player["alliance"];
					$dbData = array(
						"id_player_ogame" => $player["id"],
						"playername" => $player["playername"],
						"status" => $player["status"],
						"id_alliance" => ((!empty($alliance) ? $alliance["id"] : NULL)),
						"last_update" => $time
					);
					$this->container->players->insertPlayer($dbData);
					$update_players[] = $player["id"];
					// now update alliance
					if(!empty($alliance) && !in_array($alliance["id"], $updated_allis))
					{
						$dbData = array(
							"id_alliance_ogame" => $alliance["id"],
							"tag" => $alliance["tag"],
							"name" => $alliance["name"],
							"last_update" => $time
						);
						$this->container->alliances->insertAlliance($dbData);
						$updated_allis[] = $alliance["id"];
					}
				}
				// handle activities
				// only players with empty status are active
				if($this->playerActive($player["status"]))
				{
					if(!isset($player_planet_count[$player["id"]]))
					{
						$player_planet_count[$player["id"]] = $planets = $this->container->universe->getPlanetsMoonsCountForPlayer($player["id"]);
					}
					else
					{
						$planets = $player_planet_count[$player["id"]];
					}

					if($planets !== FALSE)
					{
						// first handle planet
						$act = array(
							"id_player" => $player["id"],
							"planets" => $planets,
							"moon" => 0
						);
						$act = array_merge($act, $coords);
						// NULL is when player has detailed activity disabled
						if($planet["activity"] !== NULL)
						{
							if($planet["activity"])
							{
								$act["timestamp"] = $planet["activity"];
								$this->container->activities->insertActivityMultiple($act, $time, "galaxyview", "inactivity");
							}
							else
							{
								$act["timestamp"] = $time;
								$this->container->activities->insertInactivity($act, "inactivity");
							}
						}

						if(!empty($planet["moon"]))
						{
							$act["moon"] = 1;
							if($planet["moon"]["activity"] !== NULL)
							{
								if($planet["moon"]["activity"])
								{
									$act["timestamp"] = $planet["moon"]["activity"];
									$this->container->activities->insertActivityMultiple($act, $time, "galaxyview", "inactivity");
								}
								else
								{
									$act["timestamp"] = $time;
									$this->container->activities->insertInactivity($act, "inactivity");
								}
							}

						}
					}

				}


			}
		}
		return true;
	}
	public function playerActive($status)
	{
		// player can be active even if he is in v-mode!!!
		return $status == "" || ((stripos("i", $status) === FALSE) && (strpos("a", $status) === FALSE) && (strpos("b", $status) === FALSE));
	}
	/**
	 * Add prefix to array keys
	 * @param string $prefix
	 * @param array $data
	 * @param array $exceptions array of keys you don´t want to add prefix to
	 * @return array
	 */
	public function addPrefixToKeys($prefix, $data, $exceptions = array())
	{
		return $this->container->espionages->addPrefixToKeys($prefix, $data, $exceptions);
	}
	/**
	 * Update service from OgameApi
	 * @param string $service
	 * @return boolean
	 * @throws \InvalidArgumentException
	 */
	public function updateFromOgameApi($service)
	{
		if($this->container->$service instanceof OGameApiModel)
		{
			return $this->container->$service->updateFromApi();
		}
		else
		{
			throw new \InvalidArgumentException("Requested service isn´t updated from OgameApi!");
		}
	}
	/**
	 * Determine whether $service needs update
	 * @param string $service
	 * @return boolean
	 * @throws \InvalidArgumentException
	 */
	public function needUpdate($service)
	{
		if($this->container->$service instanceof OGameApiModel)
		{
			return $this->container->$service->needApiUpdate();
		}
		elseif($service == "sync")
		{
			return $this->container->$service->needUpdate();
		}
		else
		{
			throw new \InvalidArgumentException("Requested service doesn´t support updating!");
		}
	}
	/**
	 * Runs synchronization
	 * @return boolean
	 */
	public function syncUpdate()
	{
		return $this->container->sync->update();
	}
	/**
	 * Filter array by keys
	 * @param array $data input
	 * @param array $filter list of allowed keys
	 * @param boolean $empty add keys from filter which is not in data
	 * @return array
	 */
	public function filterData($data, $filter, $empty = true)
	{
		$tmp = array();

		foreach($filter as $f)
		{
			$tmp[$f] = 0; // set it to 0, so there will be a difference between unknown and known but empty
		}


		foreach($data as $key => $value)
		{

			if(in_array($key, $filter))
			{
				$tmp[$key] = $value;

			}
		}
		// it must be this way, because for example I don´t want to display that there are 0 LF on planet
		if(!$empty)
			foreach($tmp as $key => $value)
				if(!$value)
					unset($tmp[$key]);
		return $tmp;
	}
	/**
	 * @param array $data
	 */
	public function insertMessages($data)
	{
		if(!empty($data["espionage_reports"])){
			foreach($data["espionage_reports"] as $id_msg => &$report)
			{
				// for now (Ogame v5.5.2) id_player and id_planet can´t be determined from espionage report
				if(!isset($report["id_player"]))
				{
					$report["id_player"] = $this->container->players->getTable()->select("id_player_ogame")->where(array("playername" => $report["playername"]))->fetch();
					if($report["id_player"] === FALSE) continue;
					$report["id_player"] = $report["id_player"]->id_player_ogame;
				}
				if(!isset($report["id_planet"]))
				{
					$coords = array(
						"galaxy" => $report["coordinates"]["galaxy"],
						"system" => $report["coordinates"]["system"],
						"position" => $report["coordinates"]["position"]
					);
					$report["id_planet"] = $this->container->universe->getTable()->select("id_planet_ogame")->where($coords)->fetch();
					if($report["id_planet"] === FALSE) continue;
					$report["id_planet"] = $report["id_planet"]->id_planet_ogame;
				}
				$moon = $report["coordinates"]["moon"];
				$dbData = array(
					"moon" => ($moon) ? "1" : "0",
					"id_planet" => $report["id_planet"],
					"scan_depth" => $report["scan_depth"],
					"id_message_ogame" => $id_msg,
					"timestamp" => $report["timestamp"],
					"resources" => $report["resources"],
					"fleet" => $report["fleet"],
					"defence" => $report["defence"],
					"building" => $report["building"],
					"research" => $report["research"],
				);
				$this->container->espionages->insertEspionage($dbData, $report["id_player"]);
			}
		}
		if(!empty($data["messages"]))
		{
			foreach($data["messages"] as $id_msg => &$msg)
			{
				if(!isset($msg["id_player"]))
				{
					$msg["id_player"] = $this->container->players->getTable()->select("id_player_ogame")->where(array("playername" => $msg["playername"]))->fetch();
					if($msg["id_player"] === FALSE) continue;
					$msg["id_player"] = $msg["id_player"]->id_player_ogame;
				}
				$dbData = array(
					"id_player" => $msg["id_player"],
					"timestamp" => $msg["timestamp"],
					"type" => "message"
				);
				var_dump($dbData);
				$this->insertActivity($dbData);
			}
		}
	}
}
