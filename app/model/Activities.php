<?php
namespace GLOTR;
use Nette;
use Nette\Diagnostics\Debugger as DBG;
class Activities extends Table
{
	/** @var string */
	protected $tableName = "activities";
	/** @var array $strongTypes activity types, when it is 100% sure that player is online */
	protected $strongTypes;
	/** @var array $types all types of activity */
	protected $types;
	public function __construct(Nette\Database\Connection $database, Nette\DI\Container $container)
	{
		parent::__construct($database, $container);

		$this->strongTypes = array("alliance_page", "message", "scan", "apg_inactivity");
		$this->types = array("galaxyview", "inactivity", "manual", "manual_inactivity");
		$this->types = array_merge($this->types, $this->strongTypes);

	}
	/**
	 * checks whether activity can be inserted into db, there can be only one record per user, type, 15 minutes and coordinates (only for galaxyview and inactivity)
	 * @param array $data neccessary indexes are type, timestamp, id_player, if type is galaxyview or inactivity galaxy, system and position are neccessary
	 * @return boolean
	 */
	public function isOverlapping($data)
	{
		if(!in_array($data["type"], $this->types))
				throw new Nette\Application\ApplicationException("Activity type must be defined in GLOTR\Activities!");
		$tmp = $data["timestamp"];
		unset($data["timestamp"]);
		// find out, in which part of hour is this activity in
		$minute = date("i", $tmp);
		$start = $minute%15;
		$end = 15-$start;
		$res = $this->getTable()->where($data)->where("timestamp >= ?", $tmp-($start*60))->where("timestamp <= ?", $tmp+($end*60))->fetch();

		return ($res !== false);
	}
	public function insertActivity($act)
	{
		if(in_array($act["type"], $this->strongTypes))
				// if it is one of the strong types, delete inactivity for that player in past 15minutes
				$this->getTable()->where("timestamp > ? AND timestamp < ?", $act["timestamp"]-(15*60), $act["timestamp"])->where(array("type" => "inactivity", "id_player" => $act["id_player"]))->delete();
		if(!$this->isOverlapping($act))
		{

			$res = $this->getTable()->insert($act);

		}
	}
	public function getTypes()
	{
		return $this->types;
	}
	public function search($id_player, $filter = array())
	{
		$activities = $this->container->activities->getTable()->select("id, type, id_player, timestamp, ((hour(from_unixtime(timestamp))*3600 + minute(from_unixtime(timestamp))*60) DIV (15*60)) AS period, count(id) AS records, max(planets) AS planet_factor")
				->where(array("id_player" => $id_player))
				->where($filter)
				->group("type, period")
				->order("period ASC");

		$results = array();

		while($r = $activities->fetch())
		{
			$results[] = $r;
		}
		$activities = array();
		$ret = array();
		// there are 4*15minutes in a hour and 24hours/day = 96
		for($i = 0; $i <= 95; $i++)
		{

			// initialize activities
			foreach($this->container->activities->types as $type)
			{
				$activities[$i][$type] = 0;
				$activities[$i]["label"] = floor(($i)/4);
				if((15*(($i)%4)) != 0)
					$activities[$i]["label"] .= ":".(15*(($i)%4));
				else
					$activities[$i]["label"] .= ":00";
			}

		}
		foreach($results as $result)
		{
			$value = 0;
			switch($result["type"]):
				case "galaxyview": case "inactivity":
					$value = $result["records"]/$result["planet_factor"];
					break;
				default:
					$value = $result["records"];
			endswitch;
			$activities[$result["period"]][$result["type"]] += $value;
		}
		return $activities;
	}
	public function searchUngrouped($id_player, $filter = array())
	{
		$activities = $this->container->activities->getTable()
				->where(array("id_player" => $id_player))
				->where($filter)
				->order("timestamp DESC");

		$results = array();

		while($r = $activities->fetch())
		{
			$results[] = $r;
		}
		return $results;
	}
}
