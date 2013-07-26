<?php
namespace GLOTR;
use Nette;

class Sync extends GLOTRApiModel
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
		if($this->params["enableSync"] === FALSE)
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
		if($this->params["enableSync"])
		{
			$count = $this->glotrApi->getSyncServersNeedingUpdate()->count("id_server");
			return $count > 0;

		}
		return false;
	}
	public function update()
	{
		$server = $this->glotrApi->getSyncServersNeedingUpdate()->limit(1)->fetch();
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
				$packet = $this->glotrApi->createTransferPacket($this->getCompressions(), explode("|", $server["compression"]));
				foreach($data as $d)
				{
					$packet->addItem($d->toArray());
				}
				$last_id = end($data);
				$last_id = $last_id["id_update"];
				try{
					$success = $this->glotrApi->syncUpload($server,$packet->toString());
					if($success)
					{
						$this->glotrApi->updateSyncServer($server["id_server"], array("last_upload_id" => $last_id));
					}
				}
				// something wrong with server, deactivate
				catch(\Nette\Application\ApplicationException $e)
				{
					$this->glotrApi->updateSyncServer($server["id_server"], array("active" => "0"));
				}
			}
		}
		else
		{
			$items = $this->glotrApi->syncDownload($server);
			$packet = $this->glotrApi->createTransferPacket($this->getCompression());
			foreach($items as $i)
			{
				$tmp = $i;
				$tmp["data"] = $packet->uncompress($tmp["data"], $tmp["compression"]);
				if($tmp["data"] === FALSE) continue;
				$tmp["compression"] = \TransferPacket::COMPRESSION_PLAIN;
				$success = $this->applySync($tmp);
				// don´t store invalid updates
				if($success !== FALSE)
				{
					$this->insertSync($i["action"], $i["data"], $i["compression"], $i["timestamp"], $server["id_server"]);
				}

			}
			$last_id = end($items);
			$last_id = $last_id["id_update"];
			$this->glotrApi->updateSyncServer($server["id_server"],array("last_download_id" => $last_id));
		}
		return true;


	}
	public function getTransferLimit($serverLimit)
	{
		$syncLimit = $this->params["syncLimit"];
		if(($syncLimit < $serverLimit) && $syncLimit !== NULL)
		{
			return $syncLimit;
		}
		return $serverLimit;
	}
	public function needUpload($last_upload_id, $id_server)
	{
		$max = $this->getTable()->where("id_server != ? OR id_server IS NULL", $id_server)->max("id_update");
		return (($last_upload_id + $this->params["syncMinimum"]) < $max);
	}
}
