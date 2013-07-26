<?php
namespace GLOTR;

use Nette;
use Nette\Diagnostics\Debugger as DBG;
class Galaxyplugin extends Nette\Object
{
	protected $url;
	protected $container;
	protected $connection;
	public function __construct(Nette\Database\Connection $database, Nette\DI\Container $container)
	{
		$this->container = $container;
		$this->connection = $database;

	}

	public function update($data, $time = NULL)
	{
		if($time === NULL)
			$time = time();
		//GTP sends times in server timezone
		date_default_timezone_set($this->container->server->timezone);
		//DBG::log($data);
		$xml = simplexml_load_string($data);
		$type = $xml->header["content_type"];
		switch($type):
			case "galaxyview":
					$gal = (int) $xml->galaxyview["galaxy"];
					$sys = (int) $xml->galaxyview["system"];
					foreach($xml->galaxyview->position as $position):
						$coords = array(
									"galaxy" => $gal,
									"system" => $sys,
									"position" => (int) $position["pos"]
								);
						// position is not empty => insert/update it in db
						if($position->planetname)
						{
							// first handle planet
							$dbData = array(
									"name" => (string) $position->planetname,
									"id_player" => (int) $position->player["playerid"],
									"last_update" => $time

							);
							if($position->debris)
							{
								$dbData["debris_metal"] = (string) $position->debris["metal"]; // int may not be big enough
								$dbData["debris_crystal"] = (string) $position->debris["crystal"];
							}
							else // to overwrite old values
							{
								$dbData["debris_metal"] = 0;
								$dbData["debris_crystal"] = 0;
							}

							if($position->moon)
							{
								$dbData["moon_size"] = (int) $position->moon["size"];
							}
							$dbData = array_merge($dbData, $coords);
							$this->container->universe->insertPlanet($dbData);
							//now handle activity

							// don´t record activity for obviously inactive players
							if(!preg_match("/[i|v|b|a]/i", (string) $position->player["status"]))
							{
								$planets = $this->container->universe->getTable()->select("id_planet")->where(array("id_player" => $dbData["id_player"]))->count();
								if($planets !== FALSE)
								{
									$act = array(
										"id_player" => $dbData["id_player"],
										"planets" => $planets
									);

									$act = array_merge($act, $coords);

								if($position->activity)
								{
									$act["timestamp"] = mktime((int) $position->activity["hour"], (int) $position->activity["minute"], 0, (int) $position->activity["month"], (int) $position->activity["day"], (int) $position->activity["year"]);
									$act["type"] = "galaxyview";
									$this->container->activities->insertActivity($act);
									// activity can also be something like 25 minutes, that means that the player was active 23minutes ago,
									// but 8 minutes ago went to inactivity, so insert this inactivities
									$inactivities = floor(($time - $act["timestamp"])/(15*60));

									if($inactivities > 0)
									{
										for($i = 1; $i <= $inactivities; $i++)
										{
											$tmp = $act;
											$tmp["timestamp"] += $i*(15*60); // add x*15 minutes to timestamp
											$tmp["type"] = "inactivity";
											$this->container->activities->insertActivity($tmp);
										}
									}

								}
								// insert inactivity
								else
								{
									$act["type"] = "inactivity";

									$act["timestamp"] = $time+(15*60); // add 15 minutes, I will be substracting it again
									// inactvity shows after 60minutes => 4*15
									for($i = 1; $i <= 4; $i++)
									{
										$act["timestamp"] -= 15*60;

										$this->container->activities->insertActivity($act);
									}


								}

								}

							}
							// now handle players
							$player = array(
								"id_player_ogame" => $dbData["id_player"],
								"playername" => (string) $position->player["playername"],
								"last_update" => $time

							);
							if($position->player["status"])
								$player["status"] = str_replace("n", "",  str_replace("iI", "I", (string) $position->player["status"]));
							else
								$player["status"] = "";
							$this->container->players->insertPlayer($player);
							if($position->alliance)
							{

								$alliance = array(
									"id_alliance_ogame" =>  (int) $position->alliance["allyid"],
									"tag" =>  (string) $position->alliance["allyname"],
									"last_update" => $time
								);
								$this->container->alliances->insertAlliance($alliance);
								$this->container->players->setAlliance($player["id_player_ogame"], $alliance["id_alliance_ogame"], $time);
							}


							//DBG::log($position->asXml());
						}
						// position is empty, delete planet
						else
						{
							$this->container->universe->deletePlanet($coords);
						}

					endforeach;
					return 601; // galaxyview updated
				break;

			case "espionage":
					$code = 651; // espionage updated
					foreach($xml->espionage as $espionage): // GTP espionage update could theoretically contain more than one espionage
						$coords = array(
							"galaxy" => (int) $espionage->source["galaxy"],
							"system" => (int) $espionage->source["system"],
							"position" => (int) $espionage->source["planet"]
						);
						$id_player = $this->container->players->getIdByPlanet($coords);

						if($id_player === FALSE)
						{
							$code = 652; // espionage not updated
							return $code;
							break;
						}
						$act = array(
							"id_player" => $id_player,
							"timestamp" => mktime((int) $espionage->activity["hour"], (int) $espionage->activity["minute"], 0, (int) $espionage->activity["month"], (int) $espionage->activity["day"], (int) $espionage->activity["year"]),
							"type" => "scan"
						);

						$this->container->activities->insertActivity($act);
					endforeach;

					return $code;
				break;
				case "allypage":
					$code = 631; // allypage updated
					foreach($xml->player as $player):

						$id_player = (int) $player["playerid"];
						if($player->activity)
						{
							$act = array(
								"id_player" => $id_player,
								"timestamp" => mktime((int) $player->activity["hour"], (int) $player->activity["minute"], 0, (int) $player->activity["month"], (int) $player->activity["day"], (int) $player->activity["year"]),
								"type" => "alliance_page"
							);
							$inactivities = floor(($time - $act["timestamp"])/(15*60));

							if($inactivities > 0)
							{
								for($i = 1; $i <= $inactivities; $i++)
								{
									$tmp = $act;
									$tmp["timestamp"] += $i*(15*60); // add x*15 minutes to timestamp
									$tmp["type"] = "apg_inactivity";
									$this->container->activities->insertActivity($tmp);
								}
							}
						}
						// save inactivity
						else
						{
								$act = array(
								"id_player" => $id_player,
								"timestamp" => $time,
								"type" => "apg_inactivity"
							);


									$act["timestamp"] = $time+(15*60); // add 15 minutes, I will be substracting it again
									// inactvity shows after 60minutes => 4*15
									for($i = 1; $i <= 4; $i++)
									{
										$act["timestamp"] -= 15*60;

										$this->container->activities->insertActivity($act);
									}
						}


						$this->container->activities->insertActivity($act);
					endforeach;

					return $code;
				break;
				case "planetinfo":
					$code = 651; // planetinfo updated - the same code as for espionage
					foreach($xml->planetinfo as $planetinfo): // GTP planetinfo update could theoretically contain more than one planetinfo
						$coords = array(
							"galaxy" => (int) $planetinfo["galaxy"],
							"system" => (int) $planetinfo["system"],
							"position" => (int) $planetinfo["planet"]
						);
						$id_player = (int) $planetinfo["playerid"];
						$id_planet = $this->container->universe->getTable()->select("id_planet")->where($coords)->where("id_player", $id_player)->limit(1)->fetch();
						$id_planet = $id_planet["id_planet"];
						if($id_planet === FALSE)
						{
							$code = 652; // planetinfo not updated
							return $code;
							break;
						}
						foreach($planetinfo->entry as $entry)
						{
							$name = (string) $entry["name"];
							$amount = (string) $entry["amount"];
							$dbData[$this->getColumnName($name)] = $amount;
						}

						$dbData["scan_depth"] = $this->container->espionages->getScanDepthByData($dbData);
						$dbData["timestamp"] = $time;
						$dbData["moon"] = (((string) $planetinfo["moon"]) == "false") ? false : true ;

						$dbData["id_planet"] = $id_planet;
						if($dbData["scan_depth"] == "research")
						{
							 $this->container->espionages->setResearchesFromData($id_player, $dbData);
							 $dbData["scan_depth"] = "resources";

						}
						if($dbData["moon"])
						{
							$dbData = $this->container->espionages->addPrefixToKeys("moon_", $dbData, array("timestamp", "scan_depth", "id_planet", "moon"));
						}

						$dbData["planetinfo"] = 1;
						$this->container->espionages->setPlanetInfo($dbData, false);


					endforeach;

					return $code;
				break;
				case "message":
					$code = 661; // message updated
					foreach($xml->message as $message): // GTP espionage update could theoretically contain more than one espionage


						$id_player = (int) $message->from["playerid"];

						$act = array(
							"id_player" => $id_player,
							"timestamp" => mktime((int) $message->activity["hour"], (int) $message->activity["minute"], 0, (int) $message->activity["month"], (int) $message->activity["day"], (int) $message->activity["year"]),
							"type" => "message"
						);

						$this->container->activities->insertActivity($act);
					endforeach;

					return $code;
				break;
				// espionage reports
				case "reports":
					$code = 611; // reports updated
					foreach($xml->report as $report):
						$coords = array(
							"galaxy" => (int) $report["galaxy"],
							"system" => (int) $report["system"],
							"position" => (int) $report["planet"]
						);
						$playername = (string) $report["playername"];
						$moon = ((string) $report["moon"] == "false") ? 0 : 1;
						if($moon) // planet must have moon!
							$planet = $this->container->universe->findBy($coords)->where("moon_size IS NOT NULL")->fetch();
						else // planet can but don´t have to have a moon
							$planet = $this->container->universe->findBy($coords)->fetch();

						$player = $this->container->players->findOneBy(array("playername" => $playername));
						if($planet === FALSE || $player === FALSE || $planet->id_player != $player->id_player_ogame)
						{
							$code = 612; // at least one report is wrong
							continue;
						}
						$dbData = array(
							"scan_depth" => (string) $report["scandepth"],
							"moon" => $moon,
							"id_planet" => $planet->id_planet,
							"id_message_ogame" => (int) $report["msg_id"],
							"timestamp" => $this->dateTime2Timestamp((string) $report["datetime"])
						);
						foreach($report->entry as $entry)
						{
							$dbData[$this->getColumnName((string) $entry["name"])] = (int) $entry["amount"];
						}
						//DBG::log(serialize($dbData));

						$this->container->espionages->insertEspionage($dbData, $player->id_player_ogame);
					endforeach;
					return $code;
				break;
			case "fleet_movement":
				$code = 641;
				foreach($xml->fleet as $fleet)
				{
					$dbData = array(
						"mission" => (string) $fleet["mission"],
						"arrival" => $this->dateTime2Timestamp((string) $fleet["arrival_time"]),
						"last_updated" => $this->dateTime2Timestamp((string) $fleet["scantime"]),
						"returning" => ((((string) $fleet["returning"]) == "false") ? "0" : "1"),
						"origin_moon" => ((((string) $fleet->origin["moon"]) == "false") ? "0" : "1"),
						"destination_moon" => ((((string) $fleet->destination["moon"]) == "false") ? "0" : "1")
					);
					if(isset($fleet["sub_fleet_id"]))
					{
						$dbData["id_fleet_ogame"] = (string) $fleet["sub_fleet_id"];// just to be safe
						$dbData["id_parent"] = (string) $fleet["fleet_id"];
					}
					else
					{
						$dbData["id_fleet_ogame"] = (string) $fleet["fleet_id"];
					}
					$o_coords = array(
						"galaxy" => (int) $fleet->origin["galaxy"],
						"system" => (int) $fleet->origin["system"],
						"position" => (int) $fleet->origin["planet"]
					);
					$d_coords = array(
						"galaxy" => (int) $fleet->destination["galaxy"],
						"system" => (int) $fleet->destination["system"],
						"position" => (int) $fleet->destination["planet"]
					);
					$origin = $this->container->universe->findOneBy($o_coords);
					$dest = $this->container->universe->findOneBy($d_coords);
					if(!$origin || !$dest) // planet not found
					{
						return 642; // not updated
					}
					$dbData["id_origin"] = $origin->id_planet; // using GLOTR´s internal id, because planet might not be in API yet
					$dbData["id_destination"] = $dest->id_planet;
					// add ships and also resources - only visible for your own fleets
					foreach($fleet->entry as $e)
					{
						$dbData[$this->getColumnName((string) $e["name"])] = (string) $e["amount"];
					}
					try{
						$this->container->fleetMovements->insertFleetMovement($dbData);
					}
					catch(\PDOException $e)
					{
						return 642;
					}

				}
				return $code;
				break;
		endswitch;
	}
	/**
	 * Convert GTP date format to UNIX Timestamp
	 * @param type $date
	 * @return type
	 */
	public function dateTime2Timestamp($date)
	{
		$tmp = explode(" ", $date);
		$tmp[0] = explode(".", $tmp[0]);
		$tmp[1] = explode(":", $tmp[1]);
		return mktime($tmp[1][0], $tmp[1][1], $tmp[1][2], $tmp[0][1], $tmp[0][2], $tmp[0][0]);
	}
	/**
	 * Map galaxyplugin fields to database columns
	 * @param string $item
	 * @return string
	 */
	public function getColumnName($item)
	{
		return str_replace(" ", "_", str_replace("-", "", strtolower($item)));
	}
	public function prefixColumns($data, $prefix)
	{
		$tmp = array();
		foreach($data as $key => $value)
			$tmp[$prefix.$key] = $value;
		return $tmp;
	}


}

