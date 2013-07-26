<?php
namespace GLOTR;
use Nette;

class Highscore extends OgameApiModel
{
	/** @var string */
	protected $tableName = "score_history";
	/** @var string api filename */
	protected $apiFile = "highscore.xml";
	protected $categories;
	protected $types;
	protected function setup()
	{
		/**
		 * 1 - player
		 * 2 - alliance
		 */
		$this->categories = array(1,2);
		/**
		 * 0 - total
		 * 1 - economy
		 * 2 - research
		 * 3 - military
		 * 5 - military built
		 * 6 - military destroyed
		 * 5 - military lost
		 * 7 - honor
		 */
		$this->types = array(0,1,2,3,4,5,6,7);

	}
	public function updateFromApi()
	{
		$queryData = array();


		foreach($this->categories as $cat)
		{
			foreach($this->types as $type)
			{
				$data = $this->ogameApi->getData($this->apiFile, array("category" => $cat, "type" => $type));
				if($data !== false)
				{
					if($data->name != "highscore")
						$data->next();
					if($data->name != "highscore")
						throw new Nette\Application\ApplicationException("Invalid XML!");
						$timestamp = (int)$data->getAttribute("timestamp");
					if($cat == 1)
					{
						$idField = "id_player_ogame";
						$item = "player";
					}
					else
					{
						$idField = "id_alliance_ogame";
						$item = "alliance";
					}



					while($data->read())
					{
						if($data->name === $item && $data->nodeType == \XMLReader::ELEMENT)
						{

							$id = (int) $data->getAttribute("id");

							$queryData[$cat][$id]["score_$type"] = (string) $data->getAttribute("score");
							$queryData[$cat][$id]["score_$type"."_position"] = (int) $data->getAttribute("position");
							if($type == 3 && $cat == 1)
							{
								$queryData[$cat][$id]["ships"] = (string) $data->getAttribute("ships");
								if($queryData[$cat][$id]["ships"] == "")
									$queryData[$cat][$id]["ships"] = 0;
							}

							}
					}

				}
			}
		}
		$pids = implode(", ", array_keys($queryData[1])); // ids of all players
		// delete data of old players
		$this->mysqli->query("delete from ".$this->glotrApi->getTableName("players")." where id_player_ogame  not in ($pids) and status != 'a'"); // delete non-existent players
		$this->mysqli->query("delete from ".$this->glotrApi->getTableName("universe")." where id_player not in ($pids) "); // delete old planets
		$this->mysqli->query("delete from ".$this->tableName." where id_item not in ($pids) and category = 1"); // delete score history
		$this->mysqli->query("delete from ".$this->glotrApi->getTableName("activities")." where id_player not in ($pids)"); // delete activities
		$this->mysqli->query("delete from ".$this->glotrApi->getTableName("fs")." where id_player not in ($pids)"); // delete fleetsaves

		/* delete old alliances */
		// don´t delete, it´s not that much data and it´s neccessary to preserve them
		/*$aids = implode(", ", array_keys($queryData[2])); // ids of all alliances, PHP is case sensitive, so aids != AIDS :-)
		$this->mysqli->query("delete from ".$this->glotrApi->getTableName("alliances")." where id_alliance_ogame  not in ($aids)"); // delete non-existent alliances
		$this->mysqli->query("delete from ".$this->tableName." where id_item not in ($aids) and category = 2"); // delete score history*/

		$period = ceil(intval(date("z"))/intval($this->params["scoreHistoryPeriod"])); // compute current period
		$year = intval(date("Y"));
		$query = "";


		foreach($queryData as $cat => $entries)
		{
			if($cat == 1)
			{
				$idField = "id_player_ogame";
				$service = "players";
			}
			elseif($cat == 2)
			{
				$idField = "id_alliance_ogame";
				$service = "alliances";
			}

			$tbl_name = $this->glotrApi->getTableName($service);
			$time = time();
			foreach($entries as $id => $entry)
			{

				$dataFields = array();
				foreach($entry as $key => $value)
				{
					$dataFields[] = " $key = $value ";
				}
				$dataFields = implode(",", $dataFields);


				$query .= "update $tbl_name set  $dataFields where $idField = $id;";


				$query .= "insert into $this->tableName set period = $period, year = $year, category = $cat, id_item = $id, $dataFields on duplicate key update $dataFields;";
				if($cat == 1)
				{
					// on the beginning of the universe it can happen that new player is only in some of the highscore files
					if(!isset($entry["score_0"]))
					{
						$entry["score_0"] = 0;
					}
					// score_inactivity
					$query .= "insert into ".$this->glotrApi->getTableName("scoreInactivity")."
						set id_player=$id, score_0 = $entry[score_0], last_update = $time, duration = 0 on duplicate key update
						duration = (score_0 >= $entry[score_0])*(duration + $time -last_update), score_0 = $entry[score_0], last_update = $time;";
				}

			}
		}
		$this->chunkedMultiQuery($query);
		$this->config->save("$this->tableName-finished", $timestamp);
	}

	public function needApiUpdate()
	{

		return ($this->config->load("$this->tableName-finished")+$this->params["ogameApiExpirations"][$this->apiFile] < time());
	}
	public function getCategories()
	{
		return $this->categories;
	}
	public function getTypes()
	{
		return $this->types;
	}
	public function ogameApiGetFileNeeded()
	{
		$ret = array();
		foreach($this->categories as $cat)
			foreach($this->types as $type)
			{
				if($this->needApiUpdate($cat, $type))
				{
					$ret[] = $this->ogameApi->url.$this->apiFile."?category=$cat&type=$type";
				}
				else
					$ret[] = false;
			}
		return $ret;
	}
	public function scoreHistory($search)
	{
		$ret = array();
		if(empty($search))
			return $ret;
		if(empty($search["alliances"]))
			$search["alliances"] = array("");
		if(empty($search["players"]))
			$search["players"] = array("");
		$res = $this->getTable()->where("( category = 1 AND id_item IN (?)) OR (category = 2 AND id_item IN (?))", array_keys($search["players"]), array_keys($search["alliances"]))->order("id_item ASC, period ASC");
		while($r = $res->fetch())
		{
			$ret[$r->year][$r->period][$r->id_item] = $r->toArray();
		}
		return $ret;
	}
}
