<?php
namespace GLOTR;
use Nette;
class FleetMovements extends Table
{
	/** @var string */
	protected $tableName = "fleet_movements";

	public function __construct(Nette\Database\Connection $database, Nette\DI\Container $container)
	{
		parent::__construct($database, $container);
	}
	public function insertFleetMovement($dbData)
	{
		try {
			$this->getTable()->insert($dbData);
		}
		catch(\PDOException $e)
		{
			$this->getTable()->where(array("id_fleet_ogame" => $dbData["id_fleet_ogame"]))->update($dbData);
		}
		return true;
	}
	public function search($userPlayer = array(), $paginator = NULL)
	{
		if(!empty($userPlayer))
			$userPlayer = $userPlayer["player"];
		$query = $this->getTable()->where("arrival > ?", time());
		if($paginator !== NULL)
		{
			$count = $query->where("id_parent IS NULL OR id_parent = id_fleet_ogame")->count("id_fleet_ogame");
			$paginator->setItemCount($count);
			$paginator->setItemsPerPage(20);
			// I must do this, so that ACS counts as 1 fleet movement
			$query = $query->limit($paginator->getLength(), $paginator->getOffset())
					->where("id_parent IS NULL OR id_parent = id_fleet_ogame")
					->select("id_fleet_ogame");
			$ids = array_keys($query->fetchPairs("id_fleet_ogame"));
			$query = $this->getTable()->where("id_fleet_ogame IN (?) OR id_parent IN (?)", $ids, $ids);
		}
		$data = $query->order("arrival ASC")->fetchPairs("id_fleet_ogame");
		$ret = array();
		foreach($data as $id => $row)
		{
			$player_or = $player_dest = false;
			$origin = $this->container->universe->getTable()->where("id_planet", $row->id_origin)->fetch();
			if($origin)
			{
				$origin = $origin->toArray();
				$player_or = $this->container->players->getTable()
						->where(array("id_player_ogame" => $origin["id_player"]))->fetch();
				if($player_or)
				{
					$player_or = $player_or->toArray();
					$player_or["_computed_status_class"] = $this->container->players->getClassForPlayerStatus($this->container->players->getRelativeStatus($player_or, $userPlayer));
				}
			}

			$dest = $this->container->universe->getTable()->where("id_planet", $row->id_destination)->fetch();
			if($dest)
			{
				$dest = $dest->toArray();
				$player_dest = $this->container->players->getTable()
						->where(array("id_player_ogame" => $dest["id_player"]))->fetch();
				if($player_dest)
				{
					$player_dest = $player_dest->toArray();
					$player_dest["_computed_status_class"] = $this->container->players->getClassForPlayerStatus($this->container->players->getRelativeStatus($player_dest, $userPlayer));
				}
			}
			$init = false;
			if(isset($row->id_parent))
			{
				if(!isset($ret[$row->id_parent]))
				{
					$ret[$row->id_parent] = $row->toArray();
					$ret[$row->id_parent]["id_fleet_ogame"] = $row->id_parent;
					$unset = array("last_updated", "id_origin");
					foreach($unset as $key)
						unset($ret[$row->id_parent][$key]);
					$ret[$row->id_parent]["children"] = array($row->id_fleet_ogame => $row->toArray());
					$init = true;
				}
				if(!$init)
				{
					$loop = array_merge($this->getFleetKeys(), array("metal", "crystal", "deuterium"));
					foreach($loop as $key)
					{
						if(isset($row->$key) && $row->$key !== NULL)
						{
							$ret[$row->id_parent][$key] += $row->$key;
						}
					}
					$ret[$row->id_parent]["children"][$row->id_fleet_ogame] = $row->toArray();
				}
				$ret[$row->id_parent]["children"][$row->id_fleet_ogame]["origin"] = $origin;
				$ret[$row->id_parent]["children"][$row->id_fleet_ogame]["dest"] = $dest;
				$ret[$row->id_parent]["children"][$row->id_fleet_ogame]["player_or"] = $player_or;
				$ret[$row->id_parent]["children"][$row->id_fleet_ogame]["player_dest"] = $player_dest;
			}
			else
			{
				$ret[$row->id_fleet_ogame] = $row->toArray();
			}
			if(!isset($row->id_parent) || $row->id_parent == $row->id_fleet_ogame)
			{
				$ret[$row->id_fleet_ogame]["origin"] = $origin;
				$ret[$row->id_fleet_ogame]["dest"] = $dest;
				$ret[$row->id_fleet_ogame]["player_or"] = $player_or;
				$ret[$row->id_fleet_ogame]["player_dest"] = $player_dest;
			}

		}
		return $ret;
	}
	public function getFleetKeys()
	{
		$tmp = $this->container->espionages->getAllInfo();
		$tmp = $tmp["planet_fleet"];
		unset($tmp[array_search("sollar_sattelite", $tmp)]);
		return $tmp;
	}
}
