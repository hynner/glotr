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
	public function getData($file, $params = array(), $type = "XMLReader")
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
			$sess = $this->container->session;
			try {
				$conn = $this->container->createHttpSocket(str_replace("http://", "", $this->container->parameters["server"]),"/api/$file", 80,2,2);
				// zlib_decode is present in PHP >= 5.4.0
				if(function_exists("zlib_decode"))
				{
					$conn->addHeader("Accept-Encoding:", "gzip, deflate");
				}

				if($this->container->parameters["testServer"])
				{
					$conn->addHeader("Authorization:",  "Basic " . base64_encode(preg_replace("/[\r\n]/", "", file_get_contents($this->container->parameters["testServerAuthFile"]))));
				}

				$data = $conn->rangeDownload($sess->getId(), $cache,1024);
			}
			catch (\Nette\Application\ApplicationException $e) {
				return false;
			}


			if($data !== FALSE)
			{
			$xml = new \XMLReader();

			$xml->XML($data, "UTF-8");

			$xml->next();
			$exp = (int) $xml->getAttribute("timestamp");
			$exp += $expirationTime;
			$cache->save($file, $data, array(
					Cache::EXPIRE => $exp
				));
			}
		}
		switch($type)
		{
			case "XMLReader":
				$xml = new \XMLReader;
				$xml->XML($data, "UTF-8");
				return $xml;
			case "SimpleXML":
				return simplexml_load_string($data);
			case "String":
			default:
				return $data;
		}
	}
	public function getUrl()
	{
		return $this->url;
	}

}

