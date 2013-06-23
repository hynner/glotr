<?php
namespace GLOTR;
use Nette\Caching\Cache;
use Nette;
/**
 * Class for getting files from OgameApi
 */
class OgameApi extends Nette\Object
{
	protected $url;
	protected $container;
	protected $params = array();
	protected $cache;
	protected $session;
	protected $socketFactory;
	public function __construct(Nette\DI\Container $container, Cache $cache, Nette\Http\Session $sess, Nette\Callback $factory)
	{
		$this->params = $container->parameters;
		$this->url = $this->params["server"]."/api/";
		$this->cache = $cache;
		$this->session = $sess;
		$this->socketFactory = $factory;
	}
	/**
	 * Get specified file
	 * @param string $file
	 * @param array $params GET parameters for query, used to obtain highscore files
	 * @param string $type XMLReader|SimpleXML|String output format
	 * @return boolean|mixed
	 */
	public function getData($file, $params = array(), $type = "XMLReader")
	{
		$expirationTime = $this->params["ogameApiExpirations"][$file];
		$cache = $this->cache;

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
			try {
				$conn = $this->socketFactory->invoke(str_replace("http://", "", $this->params["server"]),"/api/$file", 80,2,2);
				// zlib_decode is present in PHP >= 5.4.0
				if(function_exists("zlib_decode"))
				{
					$conn->addHeader("Accept-Encoding:", "gzip, deflate");
				}

				if($this->params["server"] == "http://uni680.ogame.org")
				{
					$conn->addHeader("Authorization:",  "Basic " . base64_encode(preg_replace("/[\r\n]/", "", file_get_contents($this->params["testServerAuthFile"]))));
				}

				$data = $conn->rangeDownload($this->session->getId(), $cache,1024);
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

