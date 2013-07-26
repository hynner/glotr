<?php
namespace GLOTR;
use Nette;
/**
 * Base class for all models using OgameApi
 */
class OGameApiModel extends GLOTRApiModel
{
	/** @var GLOTR\OgameApi */
	protected $ogameApi;
	/** @var string name of the file from OgameApi */
	protected $apiFile;
	/** @var string $columnListPrefix prefix used by \GLOTR\Table::getPrefixedColumnList method */
	protected $columnListPrefix;

	/** @var GLOTR\Config */
	protected $config;
	public function __construct(Nette\Database\Connection $database,  Nette\DI\Container $container, LazyMysqli $mysqli, GLOTRApi $glApi, Config $conf, OgameApi $api)
	{
		parent::__construct($database, $container, $mysqli, $glApi, $conf);
		$this->ogameApi = $api;

	}
	/**
	 * checks if model needs update from Ogame API
	 * @return boolean
	 */
	public function needApiUpdate()
	{
		return ($this->config->load("$this->tableName-finished")+$this->params["ogameApiExpirations"][$this->apiFile] < time());
	}
	/**
	 * Get the URL of needed file
	 * @return string|boolean
	 */
	public function ogameApiGetFileNeeded()
	{
		if($this->needApiUpdate())
		{
			return $this->ogameApi->url.$this->apiFile;
		}
		else
			return false;
	}
	

}
