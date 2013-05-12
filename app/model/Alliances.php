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
			$data->read();
			$timestamp = (int)$data->getAttribute("timestamp");
			/* this can´t be done at the moment!
			// data in ogame api are always valid at the time of their creation, so I can delete all older data, because they will be replaced anyway
			// this way I also get rid of nonexisting alliances
			if($data->alliance)
				$res = $this->getTable()->where("last_update < ?", $timestamp)->delete(); */

			$query = "";
			$i = 0;
			while($data->read())
			{
				if($data->name === "alliance" && $data->nodeType == \XMLReader::ELEMENT)
				{
					$i++;
						$dbData = array(
							"id_alliance_ogame" => (int) $data->getAttribute("id"),
							"name" => (string) $data->getAttribute("name"),
							"tag" =>  (string) $data->getAttribute("tag"),
							"open" =>  (int) $data->getAttribute("open"),
							"logo" => (string) $data->getAttribute("logo"),
							"homepage" => (string) $data->getAttribute("homepage"),
							"last_update" => $timestamp
						);
						$dataFields = " id_alliance_ogame = $dbData[id_alliance_ogame], name = '$dbData[name]',
							tag = '$dbData[tag]', open = $dbData[open],
							logo = '$dbData[logo]', homepage = '$dbData[homepage]',
							last_update = $dbData[last_update] ";
						$query .= " insert into $this->tableName set $dataFields on duplicate key update $dataFields;";
				}
			}
			$this->chunkedMultiQuery($query);

			// this is the end of update, save the timestamp
			$this->container->config->save("$this->tableName-finished", $timestamp);
			$this->container->config->save("$this->tableName-start", 0);
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
		$ret["moons"] = $this->getMoonsFromPlanets($ret["planets"]);
		return $ret;
	}
	public function getNumAlliances()
	{

	}
}
