<?php
namespace GLOTR;
use Nette;

class Highscore extends Table
{
	/** @var string */
	protected $tableName = "score_history";
	/** @var string api filename */
	protected $apiFile = "highscore.xml";
	protected $categories;
	protected $types;
	public function __construct(Nette\Database\Connection $database, Nette\DI\Container $container)
	{
		parent::__construct($database, $container);
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
	public function updateFromApi($cat = 1, $type = 0)
	{


		if(!in_array($cat, $this->categories) || !in_array($type, $this->types))
			throw new Nette\Application\ApplicationException("Unknown type or category when updating highscores!");
				$data = $this->container->ogameApi->getData($this->apiFile, array("category" => $cat, "type" => $type));
				if($data !== false)
				{
					$timestamp = (int)$data["timestamp"];
					$start = (int) $this->container->config->load("$this->tableName-$cat-$type-start");

					$end = $start + $this->container->parameters["ogameApiRecordsAtOnce"];
					if($cat == 1)
					{
						$service = "players";
						$item = "player";
						$idField = "id_player_ogame";
					}
					else
					{
						$service = "alliances";
						$item = "alliance";
						$idField = "id_alliance_ogame";
					}

						for($i = $start; $i < $end; $i++)
						{
							if($data->{$item}[$i])
								$player = $data->{$item}[$i];
							else
								{
									// this is the end of update, save the timestamp
									$this->container->config->save("$this->tableName-$cat-$type-finished", $timestamp);
									$this->container->config->save("$this->tableName-$cat-$type-start", 0);
									return true;
								}
							$id = (int) $player["id"];
							$score = (string) $player["score"]; // as seen on uni680, int is not big enough :)
							$position = (int) $player["position"];
							$ships = (string) $player["ships"];
							$dbData = array(
											"score_$type" => $score,
											"score_$type"."_position" =>  $position
											);
							if($ships)
								$dbData["ships"] = $ships;
							$this->container->$service->getTable()->where(array($idField => $id))->update($dbData);
							$period = intval((intval(date("j"))*intval(date("n")))/intval($this->container->parameters["scoreHistoryPeriod"])); // compute current period
							$year = intval(date("Y"));

							try // fill in the score history, only one record per period
							{
								$this->getTable()->insert(array("period" => $period, "year" => $year, "category" => $cat, "id_item" => $id));
							}
							catch(\PDOException $e)
							{}
							$this->findBy(array("period" => $period, "year" => $year, "category" => $cat, "id_item" => $id))->limit(1)->update($dbData);



						}

					$this->container->config->save("$this->tableName-$cat-$type-start", $end);

						return true;
				}
				else
					return false;




	}
	public function cleanUpScoreHistory()
	{
		// if player or alliance isn´t in its table, it doesn´t exist anymore
		$this->findBy(array("category" => 1))->where("NOT id_item", $this->container->players->getTable()->select("id_player_ogame"))->delete();
		$this->findBy(array("category" => 2))->where("NOT id_item", $this->container->alliances->getTable()->select("id_alliance_ogame"))->delete();
	}
	public function needApiUpdate($cat = "", $type = "")
	{
		if(!in_array($cat, $this->categories) || !in_array($type, $this->types))
			throw new Nette\Application\ApplicationException("Unknown type or category when updating highscores!");
		return ($this->container->config->load("$this->tableName-$cat-$type-finished")+$this->container->parameters["ogameApiExpirations"][$this->apiFile] < time());
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
					$ret[] = $this->container->ogameApi->url.$this->apiFile."?category=$cat&type=$type";
				}
				else
					$ret[] = false;
			}
		return $ret;
	}

}
