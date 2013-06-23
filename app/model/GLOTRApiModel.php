<?php
namespace GLOTR;
use Nette;
/**
 * Base class for all models using GLOTRApi
 */
class GLOTRApiModel extends Table
{
	/** @var GLOTR\GLOTRApi */
	protected $glotrApi;
	/** @var GLOTR\Config */
	protected $config;
	public function __construct(Nette\Database\Connection $database,  Nette\DI\Container $container, LazyMysqli $mysqli, GLOTRApi $api, Config $conf)
	{
		parent::__construct($database, $container, $mysqli);
		$this->glotrApi = $api;
		$this->config = $conf;
	}


}
