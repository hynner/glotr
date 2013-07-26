<?php
namespace GLOTR;
class Config extends Table
{
	/** @var string */
	protected $tableName = "config";
	/** @var array */
	protected $config;
	/** @var bool */
	protected $loaded = false;

	public function save($key, $value)
	{
		try {
			$this->getTable()->insert(array("k" =>$key, "v" => $value));
		}
		catch(\PDOException $e)
		{
			$this->findBy(array("k" => $key))->limit(1)->update(array("v" => $value));
		}
		$this->config[$key] = $value;
	}
	public function load($key)
	{
		if(!$this->loaded)
		{
			$this->config = $this->getTable()->fetchPairs("k", "v");
		}
		if(isset($this->config[$key]))
			return $this->config[$key];
	}
}
