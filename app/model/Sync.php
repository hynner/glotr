<?php
namespace GLOTR;
use Nette;

class Sync extends Table
{
	/** @var string */
	protected $tableName = "sync";
	/** @var array ordered list of compressions algorithms */
	protected $compressions = array("gzcompress", "bzcompress");
	protected $availableCompressions;
	protected $compression;
	const COMPRESSION_PLAIN = "plain";

	public function insertSync($action, $data,$compression, $timestamp = NULL, $id_server = NULL)
	{
		if($this->container->parameters["enableSync"] === FALSE)
			return false;
		if($timestamp === NULL)
			$timestamp = time();
		// if data are uncompressed and server supports compression, compress it
		if($compression == self::COMPRESSION_PLAIN && $this->getCompression() != self::COMPRESSION_PLAIN)
		{
			$compression = $this->getCompression();
			$data = $compression($data);
		}
		$row = $this->getTable()->insert(array(
			"action" => $action,
			"timestamp" => $timestamp,
			"id_server" => $id_server,
			"compression" => $compression,
			"data" => $data
		));
		return $row->id_update;
	}
	public function applySync($args)
	{
		if(!isset($args["action"]) || !isset($args["data"]) || !isset($args["compression"]) || !isset($args["timestamp"]))
			return false;
		switch($args["action"])
		{
			case "Galaxyplugin":
				return $this->container->gtp->update($args["data"], $args["timestamp"]);
				break;
			default:
				return false;
				break;
		}
		return true;
	}
	public function getCompressions()
	{
		if(empty($this->availableCompressions))
		{
			foreach($this->compressions as $c)
			{
				if(function_exists($c))
					$this->availableCompressions[] = $c;
			}
		}

		return $this->availableCompressions;
	}
	public function getCompression()
	{
		if($this->compression === NULL)
		{
			$this->compression = self::COMPRESSION_PLAIN;
			$this->getCompressions();
			if(!empty($this->availableCompressions))
				$this->compression = $this->availableCompressions[0];
		}
		return $this->compression;
	}
	public function needUpdate()
	{
		if($this->container->parameters["enableSync"])
		{
			$count = $this->container->syncServers->getServersNeedingUpdate()->count("id_server");
			return $count > 0;

		}
		return false;
	}
	public function update()
	{
		$server = $this->container->syncServers->getServersNeedingUpdate()->limit(1)->fetch();
		if($server === FALSE)
		{
			return false;
		}
		if($this->needUpload($server["last_upload_id"], $server["id_server"]))
		{
			$limit = $this->getTransferLimit($server["max_transfer_items"]);
			$data = $this->getTable()->where("id_server != ? OR id_server IS NULL", $server["id_server"]);

			if($server["last_upload_id"] !== NULL)
				$data = $data->where("id_update > ?", $server["last_upload_id"]);
			$data = $data->order("id_update ASC")->limit($limit)->fetchPairs("id_update");
			if(!empty($data))
			{
				$packet = $this->container->createTransferPacket($this->getCompressions(), explode("|", $server["compression"]));
				foreach($data as $d)
				{
					$packet->addItem($d->toArray());
				}
				$last_id = end($data);
				$last_id = $last_id["id_update"];
				try{
					$success = $this->container->syncServers->upload($server,$packet->toString());
					if($success)
					{
						$this->container->syncServers->getTable()->where("id_server", $server["id_server"])->update(array("last_upload_id" => $last_id));
					}
				}
				// something wrong with server, deactivate
				catch(\Nette\Application\ApplicationException $e)
				{
					$this->container->syncServers->getTable()->where("id_server", $server["id_server"])->update(array("active" => "0"));
				}
			}
		}
		else
		{
			$success = $this->container->syncServers->download($server);
		}



	}
	public function getTransferLimit($serverLimit)
	{
		$syncLimit = $this->container->parameters["syncLimit"];
		if(($syncLimit < $serverLimit) && $syncLimit !== NULL)
		{
			return $syncLimit;
		}
		return $serverLimit;
	}
	public function needUpload($last_upload_id, $id_server)
	{
		$max = $this->getTable()->where("id_server != ? OR id_server IS NULL", $id_server)->max("id_update");
		return (($last_upload_id + $this->container->parameters["syncMinimum"]) < $max);
	}
}
