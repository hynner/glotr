<?php
namespace GLOTR;
use Nette;

class Server extends OgameApiModel
{
	/** @var string */
	protected $tableName = "server";
	/** @var string api filename */
	protected $apiFile = "serverData.xml";
	private $data;
	public function updateFromApi()
	{
		$data = $this->ogameApi->getData($this->apiFile, array(), "SimpleXML");
		if($data !== false)
		{
			$timestamp = (int)$data["timestamp"];
			// data in ogame api are always valid at the time of their creation, so I can delete all older data, because they will be replaced anyway
			if($data->domain)
			{
				$dbData = array();
				foreach($data as $key => $value)
					$dbData[$key] = (string) $value;
				$res_c = $this->getConnection()->query("show columns from $this->tableName" );
				$cols = array();
				while($col = $res_c->fetch())
					$cols[] = $col->Field;

				foreach($dbData as $k => $d)
				{
					if(!in_array($k, $cols))
							unset($dbData[$k]);
				}

				$dbData["last_update"] = $timestamp;

				try{
					$this->getTable()->insert($dbData);
				}
				catch(\PDOException $e)
				{
					$this->getTable()->update($dbData);
				}
				$this->config->save($this->tableName."-finished", $timestamp);
				$this->data = $dbData;
				return true;
			}
			return false;
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
	public function getTimezone()
	{
		$this->_get();
		return $this->data["timezone"];
	}
	public function getData()
	{
		$this->_get();
		return $this->data;
	}

}
