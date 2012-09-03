<?php
namespace GLOTR;
use Nette;
use Nette\Diagnostics\Debugger as DBG;
class Espionages extends Table
{
	/** @var string */
	protected $tableName = "espionages";
	/** @var string api filename */
	protected $apiFile = "";
	protected $researches;
	protected $planet_buildings, $planet_resources, $planet_defence, $planet_fleet, $moon_resources, $moon_defence, $moon_buildings, $moon_fleet;
	protected $planet, $moon;
	protected $allInfo;
	public function __construct(Nette\Database\Connection $database, Nette\DI\Container $container)
	{
		parent::__construct($database, $container);

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
			"nanite_factory"

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
		$this->moon_fleet = $this->addPrefixToKeys("moon_", $this->planet_fleet);
		$this->moon_resources =  $this->addPrefixToKeys("moon_",$this->planet_resources);
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
	public function insertEspionage($dbData, $id_player)
	{

		try{
			$this->getTable()->insert($dbData);
		}
		catch(\PDOException $e)
		{
				// just to be sure, there could be some crash or whatever
				$this->getTable()->where(array("id_message_ogame" => $dbData["id_message_ogame"]))->update($dbData);

		}
		// update research info for player
		if($dbData["scan_depth"] == "research")
		{
			$this->setResearchesFromData($id_player, $dbData);
		}
		$this->setPlanetInfo($dbData);
		return true;
	}
	public function setResearchesFromData($id_player, $dbData)
	{

		$tmp = array();
		foreach($dbData as $key => $value)
		{
			if(in_array($key, $this->researches))
			{
				$tmp[$key] = $value;
			}
		}
		if(!empty($tmp))
		{
			$tmp["research_updated"] = $dbData["timestamp"];
			$this->container->players->setResearches($id_player, $tmp);
		}
	}
	public function setPlanetInfo($dbData, $empty = true)
	{


		$tmp = array();
		foreach($dbData as $key => $value)
		{
			if($dbData["moon"])
			{
				$k = "moon_".$key;
				if(in_array($k, $this->moon))
						$tmp[$k] = $value;

				// this way, multiple of them will be set

			}
			elseif(in_array($key, $this->planet))
			{
				$tmp[$key] = $value;
			}
		}
		$prefix = "planet";
		if($dbData["moon"])
			$prefix = "moon";

		switch($dbData["scan_depth"]):
				case "research":
				case "building":
					$key = $prefix."_build_updated";
					$tmp[$key] = $dbData["timestamp"];
					// select planet by id, don´t overwrite newer data
					// for buildings there is $empty parameter, because from planetinfo I get this data in parts (resources + factories), so I don´t want to overwrite mines when i update factories
					$this->container->universe->getTable()->where("id_planet", $dbData["id_planet"])->where("$key < ? OR $key IS NULL", $tmp[$key])->update(array_merge($this->filterData($tmp, $this->{$prefix."_buildings"}, $empty), array($key => $tmp[$key])));
					if(isset($dbData["planetinfo"])) // another workaround, for GTP planetinfo update
						goto fleet;
				case "defence":

					$key = $prefix."_defence_updated";
					$tmp[$key] = $dbData["timestamp"];
					$this->container->universe->getTable()->where("id_planet", $dbData["id_planet"])->where("$key < ? OR $key IS NULL", $tmp[$key])->update(array_merge($this->filterData($tmp, $this->{$prefix."_defence"}), array($key => $tmp[$key])));
					if(isset($dbData["planetinfo"])) // another workaround, for GTP planetinfo update
						goto resources;
				fleet:
				case "fleet":
					// a little workaround, because in GTP update of buildings(resources) is also solar satellite and this caused erasing of the fleet
					$prf = "";

					if($prefix == "moon")
						$prf = $prefix."_";
					if($empty == false && $dbData["scan_depth"] == "building"):
						$empty = false;
					elseif($dbData["scan_depth"] == "fleet" && !isset($dbData[$prf."solar_satellite"])): // this means that this is update from fleet page, not espionage
							unset( $dbData[$prf."solar_satellite"]);
							$empty = true;
					else:
						$empty = true;
					endif;
					$key = $prefix."_fleet_updated";
					$tmp[$key] = $dbData["timestamp"];
					$this->container->universe->getTable()->where("id_planet", $dbData["id_planet"])->where("$key < ? OR $key IS NULL", $tmp[$key])->update(array_merge($this->filterData($tmp, $this->{$prefix."_fleet"}, $empty), array($key => $tmp[$key])));
				resources:
				default:
					$key = $prefix."_res_updated";
					$tmp[$key] = $dbData["timestamp"];
					$this->container->universe->getTable()->where("id_planet", $dbData["id_planet"])->where("$key < ? OR $key IS NULL", $tmp[$key])->update(array_merge($this->filterData($tmp, $this->{$prefix."_resources"}), array($key => $tmp[$key])));

			endswitch;
	}
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
		if(!$empty)
			foreach($tmp as $key => $value)
				if(!$value)
					unset($tmp[$key]);
		return $tmp;

	}
	public function getAllInfo()
	{
		return $this->allInfo;
	}
	public function getScanDepthByData($data)
	{

		$tmp = array();
		$tmp = $this->filterData($data, $this->researches, false);
		if(!empty($tmp))
			return "research";
		$tmp = $this->filterData($data, array_merge($this->planet_buildings, $this->moon_buildings), false);
		if(!empty($tmp))
			return "building";

		$tmp = $this->filterData($data,array_merge($this->planet_defence, $this->moon_defence), false);
		if(!empty($tmp))
			return "defence";
		$tmp = $this->filterData($data,array_merge($this->planet_fleet, $this->moon_fleet), false);
		if(!empty($tmp))
			return "fleet";
		$tmp = $this->filterData($data,array_merge($this->planet_resources, $this->moon_resources) , false);
		if(!empty($tmp))
			return "resources"; // resources have to be last, because they are on every page
		return false;
	}
	public function addPrefixToKeys($prefix, $data, $exceptions = array())
	{
		$tmp  = array();
		foreach($data as $key => $value)
		{
			if(!in_array($key, $exceptions))
				$tmp[$prefix.$key] = $value;
		}
		return $tmp;
	}
}
