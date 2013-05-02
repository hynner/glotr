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
	public function search()
	{
		$data = $this->getTable()->where("arrival > ?", time())->order("arrival ASC")->fetchPairs("id_fleet_ogame");
		$ret = array();
		foreach($data as $id => $row)
		{
			$origin = $this->container->universe->getTable()->where("id_planet", $row->id_origin)->fetch();
			if($origin)
				$origin = $origin->toArray();
			$dest = $this->container->universe->getTable()->where("id_planet", $row->id_destination)->fetch();
			if($dest)
				$dest = $dest->toArray();

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
			}
			else
			{
				$ret[$row->id_fleet_ogame] = $row->toArray();
			}
			if(!isset($row->id_parent) || $row->id_parent == $row->id_fleet_ogame)
			{
				$ret[$row->id_fleet_ogame]["origin"] = $origin;
				$ret[$row->id_fleet_ogame]["dest"] = $dest;
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
