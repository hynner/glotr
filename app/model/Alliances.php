<?php
namespace GLOTR;
use Nette;

class Alliances extends Table
{
	/** @var string */
	protected $tableName = "alliances";
	/** @var string api filename */
	protected $apiFile = "alliances.xml";
	public function updateFromApi()
	{
		$data = $this->container->ogameApi->getData($this->apiFile);

		if($data !== false)
		{

			$timestamp = (int)$data["timestamp"];
			/* this can´t be done at the moment!
			// data in ogame api are always valid at the time of their creation, so I can delete all older data, because they will be replaced anyway
			// this way I also get rid of nonexisting alliances
			if($data->alliance)
				$res = $this->getTable()->where("last_update < ?", $timestamp)->delete(); */


			$start = (int) $this->container->config->load("$this->tableName-start");

			$end = $start + $this->container->parameters["ogameApiRecordsAtOnce"];

			for($i = $start; $i < $end; $i++)
				{
					if($data->alliance[$i])
						$alliance = $data->alliance[$i];
					else
						{
							// this is the end of update, save the timestamp
							$this->container->config->save("$this->tableName-finished", $timestamp);
							$this->container->config->save("$this->tableName-start", 0);
							return true;
						}
				$name = (string) $alliance["name"];
				$id = (int) $alliance["id"];
				$tag = (string) $alliance["tag"];
				$logo = (string) $alliance["logo"];
				$homepage = (string) $alliance["homepage"];
				$open = (int) $alliance["open"];


				$dbData = array(
								"id_alliance_ogame" => $id,
								"name" => $name,
								"tag" =>  $tag,
								"open" =>  $open,
								"logo" => $logo,
								"homepage" => $homepage,
								"last_update" => $timestamp
								);
				$this->insertAlliance($dbData);

				foreach($alliance->player as $player)
				{
					$id = (int) $player["id"];
					$this->container->players->setAlliance($id, $dbData["id_alliance_ogame"]);
				}

			}
			$this->container->config->save("$this->tableName-start", $end);
			return true;
		}
		else
			return false;

	}
	public function insertAlliance($dbData)
	{
		try{

			$this->getTable()->insert($dbData);
		}
		catch(\PDOException $e)
		{

			// if alliance already exists update it
			$this->getTable()->where(array("id_alliance_ogame" => $dbData["id_alliance_ogame"]))->update($dbData);

		}
		return true;
	}
	public function search($id_alliance)
	{
		if(!$id_alliance)
			return array();
		$ret["alliance"] = $this->getTable()->where(array("id_alliance_ogame" => $id_alliance))->fetch()->toArray();
		$res = $this->container->players->findBy(array("id_alliance" => $id_alliance));
		while($r = $res->fetch())
			$ret["players"][$r->id_player_ogame] = $r->toArray();

		$res = $this->container->universe->getTable()->where("id_player", array_keys($ret["players"]))->order("galaxy ASC, system ASC, position ASC");
		while($r = $res->fetch())
			$ret["planets"][] = $r->toArray();

		return $ret;
	}
}
