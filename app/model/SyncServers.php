<?php
namespace GLOTR;
use Nette;
/**
 * Class for communication with sync servers
 */
class SyncServers extends GLOTRApiModel
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
			$socket = $this->glotrApi->createHttpSocket($url, substr($server->url, strlen($url))."?pg=input", $port, 2,5);
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
		$code = $this->getElementContentById($data, "code");
		if($code === FALSE)
			goto error;
		if($code == "OK")
		{
			$compression = $this->getElementContentById($data, "compression");
			if($compression === FALSE)
				$compression = "";
			$max_input = $this->getElementContentById($data, "max_transfer_items");
			if($max_input === FALSE)
				$max_input = "";
			$this->getTable()->where("id_server", $id_server)
					->update(array("compression" => $compression, "max_transfer_items" => $max_input, "active" => "1"));
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
		if($values["not_register"] === FALSE)
		{
			$host = $this->getHost($url);

			if($host === FALSE)
				throw new \Nette\Application\ApplicationException("Bad URL!");
			$port = $this->getPort($url);
			// if connection can´t be established, Exception will be raised
			$socket = $this->glotrApi->createHttpSocket($host, substr($url, strlen($host))."?pg=add_server&no-redir=1", $port, 2,5);
			$post = array(
					"name" => $values["username"],
					"password" => $values["password"],
					"password2" => $values["password"]
			);
			$socket->postData($post);
			$headers = $socket->getResponseHeaders();
			$_data = $socket->getData();
			if($_data === FALSE)
			{
				throw new \Nette\Application\ApplicationException("No data! Return code ".$headers["responseCode"]);
			}
			$code = $this->getElementContentById($_data, "code");
			if($code === FALSE)
				throw new \Nette\Application\ApplicationException("Invalid data!");
		}
		else
		{
			$code = "OK";
		}

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
			$error = $this->getElementContentById($_data, "error");
			if($error === FALSE)
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
		$socket = $this->glotrApi->createHttpSocket($host, substr($url, strlen($host))."?pg=input&action=ping", $port, 2,5);
		$socket->sendHeaders();
		$headers = $socket->getResponseHeaders();
		$_data = $socket->getData();
		if($_data === FALSE)
		{
			throw new \Nette\Application\ApplicationException("No data! Return code ".$headers["responseCode"]);
		}
		$code = $this->getElementContentById($_data, "code");
		if($code === FALSE)
			throw new \Nette\Application\ApplicationException("Invalid data!");
		if($code == "OK")
		{
			return true;
		}
		else
		{
			throw new \Nette\Application\ApplicationException("Bad return code!");
		}
	}
	public function getServersNeedingUpdate()
	{
		return $this->getTable()
					->where("(last_download_time + ?) < ? OR last_download_time IS NULL",$this->params["syncFrequency"], time())
					->where("active", "1");
	}
	/**
	 * Upload updates to server
	 * @param array $server
	 * @param string $data
	 * @return boolean
	 * @throws \Nette\Application\ApplicationException
	 */
	public function upload($server, $data)
	{
		if(!$data)
		{
			return false;
		}
		$host = $this->getHost($server["url"]);
		if($host === FALSE)
			throw new \Nette\Application\ApplicationException("Bad URL!");
		$port = $this->getPort($server["url"]);
		// if connection can´t be established, Exception will be raised
		$socket = $this->glotrApi->createHttpSocket($host, substr($server["url"], strlen($host))."?pg=input", $port, 2,15);
		$post = array(
				"name" => $server["username"],
				"password" => $server["password"],
				"action" => "input",
				"data" => $data
		);
		$socket->postData($post);
		$headers = $socket->getResponseHeaders();
		$_data = $socket->getData();
		if($_data === FALSE)
		{
			throw new \Nette\Application\ApplicationException("No data! Return code ".$headers["responseCode"]);
		}
		$code = $this->getElementContentById($_data, "code");
		if($code === FALSE)
			throw new \Nette\Application\ApplicationException("Invalid data!");
		if($code == "OK")
		{
			return true;
		}
		else
		{
			throw new \Nette\Application\ApplicationException("Bad return code - ". $code);
		}
	}
	/**
	 * Download updates from sync server
	 * @param array $server
	 * @return array downloaded updates
	 * @throws \Nette\Application\ApplicationException
	 */
	public function download($server)
	{
		$host = $this->getHost($server["url"]);
		if($host === FALSE)
			throw new \Nette\Application\ApplicationException("Bad URL!");
		$port = $this->getPort($server["url"]);
		// if connection can´t be established, Exception will be raised
		$socket = $this->glotrApi->createHttpSocket($host, substr($server["url"], strlen($host))."?pg=input", $port, 2,15);
		$post = array(
				"name" => $server["username"],
				"password" => $server["password"],
				"action" => "download",
				"last_id" => ($server["last_download_id"] === NULL) ? "0" : $server["last_download_id"],
				"compression" => implode("|", $this->glotrApi->getCompressions())
		);
		if($this->params["syncLimit"] !== NULL)
			$post["limit"] = $this->params["syncLimit"];
		$socket->postData($post);
		$headers = $socket->getResponseHeaders();
		$_data = $socket->getData();
		if($_data === FALSE)
		{
			throw new \Nette\Application\ApplicationException("No data! Return code ".$headers["responseCode"]);
		}
		$code = $this->getElementContentById($_data, "code");
		if($code === FALSE)
			throw new \Nette\Application\ApplicationException("Invalid data!");
		if($code == "OK")
		{
			$data = $this->getElementContentById($_data, "data");
			if($data === FALSE)
				throw new \Nette\Application\ApplicationException("No data!");
			if($data == "NO-DATA")
			{
				$this->getTable()->where("id_server", $server["id_server"])->update(array("last_download_time" => time()));
				return array();
			}
			else
			{
				$packet = $this->glotrApi->createTransferPacket($this->glotrApi->getCompressions(), array());
				$packet->loadString($data);
				return $packet->getItems();
			}
		}
		else
		{
			throw new \Nette\Application\ApplicationException("Bad return code - ". $code);
		}
	}
	public function getElementContentById($data, $id)
	{
		$matches = array();
		preg_match("/<[^>]*id=[\"']".$id."[\"'][^>]*>([^<]*)<\/[^>]*>/", $data, $matches);
		if(count($matches) < 2)
			return false;
		return $matches[1];
	}
}
