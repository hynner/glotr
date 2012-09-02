<?php
namespace GLOTR;
use Nette;
class Config extends Table
{
	/** @var string */
	protected $tableName = "config";
	/** @var array */
	protected $config;
	public function __construct(Nette\Database\Connection $database, Nette\DI\Container $container)
	{
		parent::__construct($database, $container);

		$this->config = $this->getTable()->fetchPairs("k", "v");
	}
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
		if(isset($this->config[$key]))
			return $this->config[$key];
	}
}
