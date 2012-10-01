<?php
namespace GLOTR;
use Nette\Caching\Cache;
use Nette;
class OgameApi extends Nette\Object
{
	protected $url;
	protected $container;
	public function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
		$this->url = $this->container->parameters["server"]."/api/";
	}
	public function getData($file, $params = array())
	{
		$expirationTime = $this->container->parameters["ogameApiExpirations"][$file];
		$cache = new Cache($this->container->cacheStorage, 'OgameAPI');
		if(!empty($params))
		{
			$p = "?";
			foreach($params as $key => $val)
			{
				$p .="$key=$val&";
			}
			// get rid of & at the end and add it to filename
			$file .= substr($p, 0, -1);

		}
		$data = $cache->load($file);
		if($data === NULL)
		{
			if($this->container->parameters["testServer"])
			{
				$context = stream_context_create(array(
					'http' => array(
						'header'  => "Authorization: Basic " . base64_encode($this->container->parameters["testServerUsrname"].":".$this->container->parameters["testServerPass"])
					)
				));
				$data = file_get_contents($this->url.$file, false, $context);
			}

			$data = @file_get_contents($this->url.$file);

			if($data !== FALSE)
			{
			$xml = simplexml_load_string($data);
			$exp = (int) $xml->attributes()->timestamp;
			$exp += $expirationTime;
			$cache->save($file, $data, array(
					Cache::EXPIRE => $exp
				));
			}
		}
		else
			$xml = simplexml_load_string($data);
		return $xml;
	}
}

