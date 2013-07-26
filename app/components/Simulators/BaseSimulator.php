<?php
namespace GLOTR\Components\Simulators;
/**
 * Base class for all simulators
 */
class BaseSimulator extends \Nette\Object
{
	/** @var array Mapping between GLOTR´s lang and simulator´s lang */
	protected $langs;
	/** @var string current language */
	protected $lang;
	/** @var array */
	protected $userPlayer;
	/** @var array maps GLOTR keys to Simulator keys*/
	protected $propertyMap;
	/**
	 * Base url to simulator website
	 */
	const BASE_URL = "http://";
	const REFERENCE = "glotr";
	const REF_PARAMETER = "ref";
	const LANG_PARAMETER = "lang";
	public function __construct($player = array())
	{
		$this->userPlayer = $player;
		$this->setup();
	}
	/**
	 * Init all neccessary things
	 */
	protected function setup()
	{}
	/**
	 * Set proper lang according to GLOTR´s lang
	 * @param string $lang
	 */
	public function setLang($lang)
	{
		if($this->langs[$lang])
		{
			$this->lang = $this->langs[$lang];
		}
	}
	/**
	 * Make simulation link
	 * @param array $result database row
	 * @param array $resources filtered resources
	 * @param array $fleet filtered fleet
	 * @param array $defence filtered defence
	 * @param array $buildings filtered buildings
	 * @param array $researches filtered researches
	 * @param string $prefix column prefix
	 * @param array $server server properties
	 * @return string
	 */
	public function makeLink($result, $resources, $fleet, $defence, $buildings, $researches, $prefix, $server)
	{
		$ret = static::BASE_URL."?";
		$parts = array();
		$parts[] = static::REF_PARAMETER."=".static::REFERENCE;
		$parts[] = static::LANG_PARAMETER."=".$this->lang;
		$parts = array_merge(
				$parts,
				$this->getResearchesPart($researches, $this->userPlayer),
				$this->getServerPart($server),
				$this->getUnitsPart($fleet, $prefix),
				$this->getUnitsPart($defence, $prefix),
				$this->getUnitsPart($resources, $prefix),
				$this->getGeneralPart($result)
				);
		$ret .= $this->gluePieces($parts);
		return $ret;
	}
	/**
	 * Get research part of the query
	 * @param array $def_res
	 * @param array $att_res
	 * @return array
	 */
	protected function getResearchesPart($def_res, $att_res)
	{
		// find out whether defender´s techs are known
		$resKnown = array_values(array_unique($def_res));
		$resKnown = !(is_null($resKnown[0]) && (count($resKnown) === 1));
		$ret = array();
		if($resKnown)
		{
			$keys = array(
				"a_weapons" => "weapons_technology",
				"a_shields" => "shielding_technology",
				"a_armour" => "armour_technology"
			);
			foreach($keys as $key => $val)
			{
				$ret[] = $this->getMappedKeyValue($key, $att_res[$val]);
			}
			$keys = array(
				"d_weapons" => "weapons_technology",
				"d_shields" => "shielding_technology",
				"d_armour" => "armour_technology"
			);
			foreach($keys as $key => $val)
			{
				$ret[] = $this->getMappedKeyValue($key, $def_res[$val]);
			}
		}
		$keys = array(
			"combustion_drive",
			"impulse_drive",
			"hyperspace_drive",
		);
		foreach($keys as $val)
		{
			$ret[] = $this->getMappedKeyValue($val, $att_res[$val]);
		}
		return $ret;
	}
	/**
	 * Get server settings part of query
	 * @param array $server
	 * @return array
	 */
	protected function getServerPart($server)
	{
		if(empty($server)) return array();
		return array(
			$this->getMappedKeyValue("debrisFactor", intval($server["debrisFactor"]*100)),
			$this->getMappedKeyValue("speed", intval($server["speed"])),
			$this->getMappedKeyValue("defToTF", intval( ($server["defToTF"]*$server["debrisFactor"])*100)),
			$this->getMappedKeyValue("repairFactor", intval($server["repairFactor"]*100)),
			$this->getMappedKeyValue("rapidFire", (intval($server["rapidFire"]) ? "1" : "0"))
		);

	}
	/**
	 * Get battle units part of query, used for resources, defence and fleet
	 * @param array $units
	 * @param string $prefix planet_|moon_
	 * @return array
	 */
	protected function getUnitsPart($units, $prefix)
	{
		if(empty($units)) return array();
		$ret = array();
		foreach($units as $key => $value)
		{
			$ret[] = $this->getMappedKeyValue(str_replace($prefix, "", $key), $value);
		}
		return $ret;

	}
	/**
	 * Get the general part of the query
	 * @param array $result
	 * @return array
	 */
	protected function getGeneralPart($result)
	{
		return array(
			$this->getMappedKeyValue("plunder", (isset($result["_computed_player_status"]["plunder"]) ? $result["_computed_player_status"]["plunder"] : "50")),
			$this->getMappedKeyValue("destination", $result["galaxy"].":".$result["system"].":".$result["position"]),
			$this->getMappedKeyValue("enemy_name", $result["playername"]),
		);
	}
	/**
	 * Return key-value part of query
	 * @param string $key GLOTR key to be mapped
	 * @param string $value
	 * @return string
	 */
	protected function getMappedKeyValue($key, $value)
	{
		if(isset($this->propertyMap[$key]) && $this->propertyMap[$key] != "")
		{
			return $this->propertyMap[$key]."=".$value;
		}
		return "";
	}
	protected function gluePieces($pieces, $glue = "&")
	{
		// this will leave only one empty value
		$pieces = array_unique($pieces);
		$key = array_search("", $pieces);
		if($key !== FALSE)
			unset($pieces[$key]);
		return implode($glue, $pieces);
	}

}