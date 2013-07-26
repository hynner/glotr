<?php
namespace GLOTR;
/**
 * Lazy loading for mysqli
 */
class LazyMysqli
{
	protected $loaded;
	protected $host, $user, $pass, $dbname;
	protected $mysqli;
	public function __construct($host, $user, $pass, $dbname = NULL)
	{
		$this->loaded = false;
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->dbname = $dbname;
	}
	/**
	 * Pass all the calls to mysqli
	 * @throws \Nette\FatalErrorException - unknown mysqli method
	 */
	public function __call($name, $args)
	{
		$this->_load();
		try{
			$method = new \ReflectionMethod($this->mysqli, $name);
		}
		catch(\ReflectionException $e)
		{
			//method doesn´t exists
			throw new \Nette\FatalErrorException($e->getMessage());
		}
		return $method->invokeArgs($this->mysqli, $args);

	}
	/**
	 * Load mysqli if it isn´t loaded aleready
	 */
	protected function _load()
	{
		if(!$this->loaded)
		{
			$this->mysqli = new \mysqli($this->host, $this->user, $this->pass, $this->dbname);
			$this->loaded = true;
		}
	}
}