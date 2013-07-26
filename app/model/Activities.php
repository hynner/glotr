<?php
namespace GLOTR;
use Nette;

class Activities extends Table
{
	/** @var string */
	protected $tableName = "activities";
	/** @var array $strongTypes activity types, when it is 100% sure that player is online */
	protected $strongTypes;
	/** @var array $types all types of activity */
	protected $types;
	protected function setup()
	{
		$this->strongTypes = array("alliance_page", "message", "scan", "apg_inactivity");
		$this->types = array("galaxyview", "inactivity", "manual", "manual_inactivity");
		$this->types = array_merge($this->types, $this->strongTypes);
	}
	/**
	 * checks whether activity can be inserted into db, there can be only one record per user, type, 15 minutes and coordinates (only for galaxyview and inactivity)
	 * @param array $data neccessary indexes are type, timestamp, id_player, if type is galaxyview or inactivity galaxy, system and position are neccessary
	 * @return boolean
	 * @throws Nette\Application\ApplicationException
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
	/**
	 * Insert activity
	 * @param array $act
	 */
	public function insertActivity($act)
	{
		if(in_array($act["type"], $this->strongTypes))
				// if it is one of the strong types, delete inactivity for that player in past 15minutes
				$this->getTable()->where("timestamp > ? AND timestamp < ?", $act["timestamp"]-(15*60), $act["timestamp"])->where(array("type" => "inactivity", "id_player" => $act["id_player"]))->delete();
		if(!$this->isOverlapping($act))
		{
			$res = $this->getTable()->insert($act);
		}
		return true;
	}
	/**
	 * Helper method for inserting activities which can also reveal inactivity - like galaxyview or alliance_page
	 * @param array $act
	 * @param int $time when was activity record obtained
	 * @param string $act_type eg. galaxyview, alliance_page
	 * @param string $inact_type eg. inactivity, apg_inactivity
	 * @return boolean
	 */
	public function insertActivityMultiple($act, $time, $act_type, $inact_type)
	{
		$act["type"] = $act_type;
		$this->insertActivity($act);
		// if activity is more than 15mins old, insert inactivity too
		$inactivities = floor(($time - $act["timestamp"])/(15*60));
		if($inactivities > 0)
		{
			for($i = 1; $i <= $inactivities; $i++)
			{
				$tmp = $act;
				$tmp["timestamp"] += $i*(15*60); // add x*15 minutes to timestamp
				$tmp["type"] = $inact_type;
				$this->insertActivity($tmp);
			}
		}
		return TRUE;
	}
	/**
	 * Helper method to insert inactivity for past hour
	 * @param type $act
	 * @param type $type
	 * @return boolean
	 */
	public function insertInactivity($act, $type)
	{
		$act["type"] = $type;
		$act["timestamp"] += (15*60); // add 15 minutes, I will be substracting it again
		// inactvity shows after 60minutes => 4*15
		for($i = 1; $i <= 4; $i++)
		{
			$act["timestamp"] -= 15*60;

			$this->insertActivity($act);
		}
		return true;
	}
	public function getTypes()
	{
		return $this->types;
	}
	/**
	 * Search player activity grouped by type and period - used in activity chart
	 * @param int $id_player
	 * @param array $filter
	 * @return array
	 */
	public function search($id_player, $filter = array())
	{
		$activities = $this->getTable()->select("id, type, id_player, timestamp, ((hour(from_unixtime(timestamp))*3600 + minute(from_unixtime(timestamp))*60) DIV (15*60)) AS period, count(id) AS records, max(planets) AS planet_factor")
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
			foreach($this->types as $type)
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
	/**
	 * Search ungrouped activities for player
	 * @param int $id_player
	 * @param array $filter
	 * @return array
	 */
	public function searchUngrouped($id_player, $filter = array())
	{
		$activities = $this->getTable()
				->where(array("id_player" => $id_player))
				->where($filter)
				->order("timestamp DESC");

		$results = array();

		while($r = $activities->fetch())
		{
			$results[] = $r->toArray();
		}
		return $results;
	}
}
