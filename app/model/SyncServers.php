<?php
namespace GLOTR;
use Nette;

class SyncServers extends Table
{
	/** @var string */
	protected $tableName = "sync_servers";

	protected function getHost($url)
	{
		$host = array();
		preg_match("/^[^\/]*/", $url, $host);
		if(empty($host))
			return false;
		return $host[0];
	}
	protected function getPort($url)
	{
		$port = array();
		preg_match("/:\d*/", $url, $port);
		if(empty($port))
		{
			$port = 80;
		}
		else
		{
			$port = $port[0];
		}
		return $port;
	}
	public function verify($id_server)
	{
		$ret_code = FALSE;
		$server = $this->find($id_server);
		if($server === FALSE)
			goto error;
		$url = $this->getHost($server->url);
		if($url === FALSE)
			goto error;
		$port = $this->getPort($server->url);
		try
		{
			$socket = $this->container->createHttpSocket($url, substr($server->url, strlen($url))."?pg=input", $port, 2,5);
		}
		// socket cannot be created
		catch(\Nette\Application\ApplicationException $e)
		{
			goto error;
		}
		$data = array(
			"name" => $server->username,
			"password" => $server->password,
			"action" => "verify"
		);
		$socket->postData($data);
		$headers = $socket->getResponseHeaders();
		$data = $socket->getData();

		if($data === FALSE)
		{
			$ret_code = $headers["responseCode"];
			goto error;
		}

		$xml = simplexml_load_string($data);
		$code = $xml->xpath("//*[@id='code']");
		if(empty($code))
			goto error;
		$code = (string) $code[0];
		if($code == "OK")
		{
			$compression = $xml->xpath("//*[@id='compression']");
			if(!empty($compression))
				$compression = (string) $compression[0];
			else
				$compression = "";
			$max_input = $xml->xpath("//*[@id='max_input_items']");
			if(!empty($max_input))
				$max_input = (string) $max_input[0];
			else
				$max_input = "";
			$this->getTable()->where("id_server", $id_server)
					->update(array("compression" => $compression, "max_input_items" => $max_input, "active" => "1"));
		}
		else
		{
			$ret_code = $code;
			goto error;
		}
		return $code;
	error:
		$this->getTable()->where("id_server", $id_server)
					->update(array("active" => "0"));
		return $ret_code;
	}
	public function register($values)
	{
		$url = preg_replace("/^[^:\/]*:\/\//","",$values["url"]);
		$host = $this->getHost($values["url"]);
		if($host === FALSE)
			throw new \Nette\Application\ApplicationException("Bad URL!");
		$port = $this->getPort($values["url"]);
		// if connection can´t be established, Exception will be raised
		$socket = $this->container->createHttpSocket($host, substr($url, strlen($host))."?pg=add_server&no-redir=1", $port, 2,5);
		$post = array(
				"name" => $values["username"],
				"password" => $values["password"],
				"password2" => $values["password"]
		);
		$socket->postData($post);
		$headers = $socket->getResponseHeaders();
		$data = $socket->getData();
		if($data === FALSE)
		{
			throw new \Nette\Application\ApplicationException("No data! Return code ".$headers["responseCode"]);
		}
		$xml = simplexml_load_string($data);
		$code = $xml->xpath("//*[@id='code']");
		if(empty($code))
			throw new \Nette\Application\ApplicationException("Invalid data!");
		$code = (string) $code[0];
		if($code == "OK")
		{
			$data = array(
				"name" => $values["servername"],
				"url" => $url,
				"username" => $values["username"],
				"password" => $values["password"],
				"active" => 0
			);
			try
			{
				$this->getTable()->insert($data);
			}
			catch(\PDOException $e)
			{
				// registration on glotr-sync already succeeded and I don´t want to make a mess in there
				$data["name"] = Nette\Utils\Strings::random(10, "0-9a-z");
				try
				{
					$this->getTable()->insert($data);
				}
				catch(\PDOException $e)
				{
					throw new \Nette\Application\ApplicationException("Servername already exists!");
				}
			}
			return true;
		}
		else
		{
			$error = $xml->xpath("//*[@id='error']");
			if(!empty($error))
				$error = (string) $error[0];
			else
				$error = "No error reported!";
			throw new \Nette\Application\ApplicationException("Bad return code! $error");
		}

		return false;


	}
	/**
	 * Checks whether given URL is valid glotr-sync app
	 * @param string $url
	 * @param int $port
	 * @return boolean
	 * @throws \Nette\Application\ApplicationException
	 */
	public function ping($url, $port = 80)
	{
		$host = $this->getHost($url);
		// if connection can´t be established, Exception will be raised
		$socket = $this->container->createHttpSocket($host, substr($url, strlen($host))."?pg=input&action=ping", $port, 2,5);
		$socket->sendHeaders();
		$headers = $socket->getResponseHeaders();
		$data = $socket->getData();
		if($data === FALSE)
		{
			throw new \Nette\Application\ApplicationException("No data! Return code %s", $headers["returnCode"]);
		}
		$xml = simplexml_load_string($data);
		$code = $xml->xpath("//*[@id='code']");
		if(empty($code))
			throw new \Nette\Application\ApplicationException("Invalid data!");
		$code = (string) $code[0];
		if($code == "OK")
		{
			return true;
		}
		else
		{
			throw new \Nette\Application\ApplicationException("Bad return code!");
		}
	}

}
