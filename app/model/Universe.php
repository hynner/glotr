<?php
namespace GLOTR;
use Nette;
class Universe extends OgameApiModel
{
	/** @var string */
	protected $tableName = "universe";
	/** @var string api filename */
	protected $apiFile = "universe.xml";
	public function updateFromApi()
	{
		$mc = microtime(1);
		$data = $this->ogameApi->getData($this->apiFile);

		if($data !== false)
		{
			$data->read();
			$timestamp = (int)$data->getAttribute("timestamp");

			$query = "";
			$i = 0;
			while($data->read())
			{
			next_planet:
				if($data->name === "planet" && $data->nodeType == \XMLReader::ELEMENT)
				{
					$i++;

					$coords = (string) $data->getAttribute("coords");
					$coords = explode(":", $coords);
					$dbData = array(
									"id_planet_ogame" => (int) $data->getAttribute("id"),
									"name" => (string) $data->getAttribute("name"),
									"galaxy" =>  $coords[0],
									"system" =>  $coords[1],
									"position" =>  $coords[2],
									"id_player" => (int) $data->getAttribute("player"),
									"last_update" => $timestamp
								);
						$dataFields = " id_planet_ogame = $dbData[id_planet_ogame], name = '$dbData[name]',
							galaxy = $dbData[galaxy], system = $dbData[system],
							position = $dbData[position], id_player = $dbData[id_player],
							last_update = $dbData[last_update] ";


						$data->read();
						if($data->name == "moon")
						{
							$dbData["moon_name"] = (string) $data->getAttribute("name");
							$dbData["moon_size"] = (int) $data->getAttribute("size");
							$dbData["id_moon_ogame"] = (int) $data->getAttribute("id");
							$dataFields .= " , moon_name = '$dbData[moon_name]' , moon_size = $dbData[moon_size] , id_moon_ogame = $dbData[id_moon_ogame] ";
						}
						$query .= "delete from $this->tableName where position = $dbData[position] and system = $dbData[system] and galaxy = $dbData[galaxy] and id_player != $dbData[id_player];";
						$query .= " insert into $this->tableName set $dataFields on duplicate key update $dataFields;";
						// if $data->read() has moved to another planet element, it must be stored before, reading again
						if($data->name == "planet")
							goto next_planet;
				}
			}

			$this->chunkedMultiQuery($query);
			// this is the end of update, save the timestamp
			$this->config->save("$this->tableName-finished", $timestamp);
			$this->config->save("$this->tableName-start", 0);
			return true;

		}
		else
			return false;

	}
	/**
	 * Insert planet data into database
	 * @param array $dbData - contains all the neccessary info about planet
	 * @return bool
	 */
	public function insertPlanet($dbData)
	{
		$coords = array("galaxy" => $dbData["galaxy"], "system" => $dbData["system"], "position" => $dbData["position"]);

		try{ // i can´t safely delete the old records, because some dbData might be associated with it via old GLOTR id_planet
			$this->getTable()->insert($dbData);

		}
		catch(\PDOException $e)
		{
			// exception means, there is already planet on that position in Database
			// first I try to "delete" planet with the same coordinates but different id_player
			$this->getTable()->where($coords)->where("NOT id_player",$dbData["id_player"])
					->where("last_update < ?", $dbData["last_update"])
					->update(array("galaxy" => NULL, "system" => NULL, "position" => NULL));

			try{ // problem could be solved, try insert again
				$this->getTable()->insert($dbData);
			}
			catch(\PDOException $e)
			{
				// exception means there is a planet on this coordinates and it is the planet of the same player, update it
				$this->getTable()->where($coords)
						->where("last_update < ?", $dbData["last_update"])->update($dbData);

			}
		}
		return true;
	}
	/**
	 * Planet´s can´t be deleted unless player is deleted, because information are bound to it
	 * this way I can recover the info
	 * @param array $coords required indexes galaxy, system, position
	 * @return bool
	 */
	public function deletePlanet($coords)
	{
		$this->getTable()->where($coords)->update(array(
			"galaxy" => NULL,
			"system" => NULL,
			"position" => NULL
		));
		return true;
	}



}
