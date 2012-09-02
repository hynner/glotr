<?php
namespace GLOTR;
use Nette;

class Server extends Table
{
	/** @var string */
	protected $tableName = "server";
	/** @var string api filename */
	protected $apiFile = "serverData.xml";
	private $data;
	public function updateFromApi()
	{
		$data = $this->container->ogameApi->getData($this->apiFile);
		if($data !== false)
		{
			$timestamp = (int)$data["timestamp"];
			// data in ogame api are always valid at the time of their creation, so I can delete all older data, because they will be replaced anyway
			// this way I also get rid of nonexisting players
			if($data->name)
				$res = $this->getTable()->where("last_update < ?", $timestamp)->delete();

			$dbData = array();
			foreach($data as $key => $value)
				$dbData[$key] = (string) $value;

			unset($dbData["name"]);
			unset($dbData["topScore"]);
			$dbData["last_update"] = $timestamp;




				try{
					// only insertion matters, there is no need for update, because there is no old data in the database
					$this->getTable()->insert($dbData);
				}
				// I don´t have to care about exceptions, the only source is duplicate unique keys => newer data I don´t want to replace
				catch(\PDOException $e)
				{}
				$this->data = $dbData;





			return true;
		}
		else
			return false;

	}
	protected function _get()
	{
		if($this->data)
			return true;
		$this->data = $this->findAll()->limit(1)->fetch();
		if(!$this->data)
			$this->updateFromApi();
		return true;
	}
	public function getGalaxies()
	{
		$this->_get();
		return $this->data["galaxies"];
	}
	public function getSystems()
	{
		$this->_get();
		return $this->data["systems"];
	}
}
