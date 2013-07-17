<?php
namespace GLOTR;
use Nette;
class Espionages extends GLOTRApiModel
{
	/** @var string */
	protected $tableName = "espionages";

	protected $researches;
	protected $planet_buildings, $planet_resources, $planet_defence, $planet_fleet, $moon_resources, $moon_defence, $moon_buildings, $moon_fleet;
	protected $planet, $moon;
	protected $allInfo;
	protected function setup()
	{
		$this->researches = array(
			"intergalactic_research_network",
			"impulse_drive",
			"combustion_drive",
			"hyperspace_drive",
			"armour_technology",
			"shielding_technology",
			"weapons_technology",
			"graviton_technology",
			"astrophysics",
			"computer_technology",
			"espionage_technology",
			"plasma_technology",
			"hyperspace_technology",
			"ion_technology",
			"laser_technology",
			"energy_technology"

		);
		$this->planet_resources = array(
			"metal"   ,
			"crystal"   ,
			"deuterium"   ,
			"energy"
		);
		$this->planet_buildings = array(
			"metal_mine"   ,
			"crystal_mine"   ,
			"deuterium_synthesizer"   ,
			"solar_plant"   ,
			"fusion_reactor"   ,
			"metal_storage"   ,
			"crystal_storage"   ,
			"missile_silo"   ,
			"alliance_depot"   ,
			"deuterium_tank"   ,
			"robotics_factory"   ,
			"shipyard"   ,
			"research_lab"   ,
			"terraformer"   ,
			"nanite_factory",
			"shielded_metal_den",
			"underground_crystal_den",
			"seabed_deuterium_den"

		);
		$this->planet_defence = array(
			"antiballistic_missiles"   ,
			"interplanetary_missiles"   ,
			"ion_cannon",
			"large_shield_dome"   ,
			"small_shield_dome"   ,
			"light_laser"   ,
			"heavy_laser"   ,
			"gauss_cannon"   ,
			"plasma_turret"   ,
			"rocket_launcher"
		);
		$this->planet_fleet = array(
			"solar_satellite"   ,
			"large_cargo"   ,
			"heavy_fighter"   ,
			"recycler"   ,
			"espionage_probe"   ,
			"small_cargo"   ,
			"colony_ship" ,
			"destroyer"  ,
			"cruiser"   ,
			"battleship"   ,
			"battlecruiser"   ,
			"bomber"  ,
			"deathstar"   ,
			"light_fighter"
		);
		$this->moon_fleet = array_flip($this->addPrefixToKeys("moon_", array_flip($this->planet_fleet)));
		$this->moon_resources =  array_flip($this->addPrefixToKeys("moon_",array_flip($this->planet_resources)));
		$this->moon_defence = array(
			"moon_large_shield_dome"  ,
			"moon_small_shield_dome"  ,
			"moon_light_laser"  ,
			"moon_heavy_laser"  ,
			"moon_gauss_cannon"  ,
			"moon_plasma_turret"  ,
			"moon_rocket_launcher",
			"moon_ion_cannon"
		);
		$this->moon_buildings = array(
			"moon_metal_storage"  ,
			"moon_crystal_storage"  ,
			"moon_deuterium_tank"  ,
			"moon_robotics_factory"  ,
			"moon_shipyard"  ,
			"moon_lunar_base"  ,
			"moon_jumpgate"  ,
			"moon_sensor_phalanx"
		);
		$this->planet = array_merge($this->planet_resources, $this->planet_buildings, $this->planet_fleet, $this->planet_defence);
		$this->moon = array_merge($this->moon_resources, $this->moon_buildings, $this->moon_defence, $this->moon_fleet);
		$this->allInfo = array(
			"planet_buildings" => $this->planet_buildings,
			"planet_resources" => $this->planet_resources,
			"planet_defence" => $this->planet_defence,
			"planet_fleet" => $this->planet_fleet,
			"researches" => $this->researches,
			"moon_resources" => $this->moon_resources,
			"moon_buildings" => $this->moon_buildings,
			"moon_fleet" => $this->moon_fleet,
			"moon_defence" => $this->moon_defence
		);
	}
	/**
	 * Insert espionage into database
	 * @param array $dbData - contains all the neccessary info about planet
	 * @return bool
	 */
	public function insertEspionage($data, $id_player)
	{
		$dbData = array_merge(array(
			"moon" => $data["moon"],
			"id_planet" => $data["id_planet"],
			"scan_depth" => $data["scan_depth"],
			"id_message_ogame" => $data["id_message_ogame"],
			"timestamp" => $data["timestamp"],
		), $data["resources"], $data["fleet"], $data["defence"], $data["building"], $data["research"]);

		try{
			$this->getTable()->insert($dbData);
		}
		catch(\PDOException $e)
		{
				// just to be sure, there could be some crash or whatever
				$this->getTable()->where(array("id_message_ogame" => $dbData["id_message_ogame"]))->update($dbData);
		}
		// update research info for player
		if($data["scan_depth"] == "research")
		{
			$this->glotrApi->updatePlayer($id_player,array("timestamp" => $data["timestamp"], "researches" => $data["research"]));
		}
		$this->setPlanetInfo($data);
		return true;
	}

	/**
	 * set planet/moon info from espionage
	 * @param array $dbData
	 */
	public function setPlanetInfo($dbData)
	{
		//now if it is moon, it is neccessary to add moon_ prefix
		if($dbData["moon"])
		{
			foreach(array("resources", "fleet", "defence", "building") as $key)
			{
				$dbData[$key] = $this->addPrefixToKeys("moon_", $dbData[$key]);
			}
		}
		switch($dbData["scan_depth"]):
			case "research":
			case "building":
				$this->glotrApi->updatePlanet($dbData["id_planet"], "buildings",$dbData["building"], $dbData["timestamp"], $dbData["moon"] == "1");
			case "defence":
				$this->glotrApi->updatePlanet($dbData["id_planet"], "defence",$dbData["defence"], $dbData["timestamp"], $dbData["moon"] == "1");
			case "fleet":
				$this->glotrApi->updatePlanet($dbData["id_planet"], "fleet", $dbData["fleet"], $dbData["timestamp"], $dbData["moon"] == "1");
			default:
				$this->glotrApi->updatePlanet($dbData["id_planet"], "resources",$dbData["resources"], $dbData["timestamp"], $dbData["moon"] == "1");
		endswitch;
	}

	public function getAllInfo()
	{
		return $this->allInfo;
	}
	/**
	 * Determines scan depth of espionage by given data
	 * @param array $data
	 * @return string|boolean
	 */
	public function getScanDepthByData($data)
	{

		$tmp = array();
		$tmp = $this->glotrApi->filterData($data, $this->researches, false);
		if(!empty($tmp))
			return "research";
		$tmp = $this->glotrApi->filterData($data, array_merge($this->planet_buildings, $this->moon_buildings), false);
		if(!empty($tmp))
			return "building";

		$tmp = $this->glotrApi->filterData($data,array_merge($this->planet_defence, $this->moon_defence), false);
		if(!empty($tmp))
			return "defence";
		$tmp = $this->glotrApi->filterData($data,array_merge($this->planet_fleet, $this->moon_fleet), false);
		if(!empty($tmp))
			return "fleet";
		$tmp = $this->glotrApi->filterData($data,array_merge($this->planet_resources, $this->moon_resources) , false);
		if(!empty($tmp))
			return "resources"; // resources have to be last, because they are on every page
		return false;
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
		$tmp  = array();
		foreach($data as $key => $value)
		{
			if(!in_array($key, $exceptions))
				$tmp[$prefix.$key] = $value;
			else
				$tmp[$key] = $value;
		}
		return $tmp;
	}
}
