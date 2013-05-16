<?php
class TransferPacket
{
	protected $availableCompressions = array();
	protected $targetCompressions = array();
	protected $usableCompressions = array();
	protected $decompressions = array(
		"gzcompress" => "gzuncompress",
		"bzcompress" => "bzdecompress"
	);
	protected $items = array();
	const COMPRESSION_PLAIN = "plain";
	/**
	 * Constructor
	 * @param array $availableCompressions Ordered list of available compressions
	 * @param array $targetCompressions Ordered list of target compressions
	 * @throws TransferException
	 */
	function __construct($availableCompressions, $targetCompressions)
	{
		$compressions = array_keys($this->decompressions);
		$this->availableCompressions = $availableCompressions;
		$this->targetCompressions = $targetCompressions;
		// only need to check available, target will be reduced acordingly
		if(array_intersect($this->availableCompressions, $compressions) != $this->availableCompressions)
		{
			throw new TransferException("Some of the decompressions are missing!");
		}
		$this->usableCompressions = array_intersect($this->availableCompressions, $this->targetCompressions);
	}
	/**
	 * Add item - compressing or recompressing if neccessary
	 * @param array $args - compression and data keys must be set, other are optional
	 * @return boolean
	 * @throws TransferException
	 */
	public function addItem($args)
	{
		if(is_array($args) && isset($args["compression"]) && isset($args["data"]))
		{
			// first check, whether recompression can even be done
			if(!empty($this->usableCompressions))
			{
				// if data are uncompressed, compress it
				if($args["compression"] == self::COMPRESSION_PLAIN)
				{
					$args["compression"] = $this->usableCompressions[0];
					$args["data"] = $args["compression"]($args["data"]);
				}
				// if data are compressed, but target doesn´t accept this compression and data can be recompressed
				elseif(!in_array($args["compression"], $this->targetCompressions) && in_array($args["compression"], $this->availableCompressions))
				{
					$args["compression"] = $this->usableCompressions[0];
					$args["data"] = $args["compression"]($this->decompressions[$args["compression"]]($args["data"]));
				}
				elseif(!in_array($args["compression"], $this->targetCompressions) && !in_array($args["compression"], $this->availableCompressions))
				{
					return false;
				}
			}
			// if target doesn´t accept this compression and data can´t be recompressed don´t add it
			elseif(!in_array($args["compression"], $this->targetCompressions))
			{
				return false;
			}
			$this->items[] = $args;
			return true;

		}
		throw new TransferException("Item is not valid!");
		return false;
	}
	/**
	 *	Serializes items, compress if possible
	 * @return string
	 */
	public function toString()
	{
		$str = serialize($this->items);
		if(!empty($this->usableCompressions))
		{
			$compression = $this->usableCompressions[0];
			$str = $compression($str);
		}
		else
		{
			$compression = self::COMPRESSION_PLAIN;
		}
		return base64_encode($compression."###".$str);
	}
	/**
	 * Load items from string
	 * @param string $data
	 * @return boolean
	 * @throws TransferException
	 */
	public function loadString($data)
	{
		$data = base64_decode($data);
		$comp = array();
		preg_match("/^([^\#]*)\#\#\#/", $data, $comp);
		if(count($comp) < 2)
		{
			throw new TransferException("Data string not valid!");
		}
		elseif(!in_array($comp[1], $this->availableCompressions) && $comp[1] != self::COMPRESSION_PLAIN)
		{
			throw new TransferException("Unknown compression!");
		}
		$data = str_replace($comp[0], "", $data);
		if($comp[1] != self::COMPRESSION_PLAIN)
		{
			$data = $this->decompressions[$comp[1]]($data);
		}
		$this->items = unserialize($data);
		return true;
	}
	/**
	 * get items
	 * @return array
	 */
	public function getItems()
	{
		return $this->items;
	}
	public function uncompress($data, $compression)
	{
		if($compression == self::COMPRESSION_PLAIN)
			return $data;
		if(in_array($compression,$this->availableCompressions))
		{
			return $this->decompressions[$compression]($data);
		}
		return false;
	}
}
class TransferException extends Exception
{}