<?php

namespace GLOTR;
use Nette\Caching\Cache;
class httpScoket
{
	protected $host;
	protected $port;
	protected $errno, $errstr;
	protected $timeout, $conn_timeout;
	protected $socket;
	protected $headers;
	protected $file;
	protected $responseHeaders, $data;
	public function __construct($host, $file, $port = 80, $conn_timeout = NULL, $timeout = NULL )
	{
		if($conn_timeout === NULL)
			$conn_timeout = ini_get("default_socket_timeout");

		$this->host = $host;
		$this->file = $file;
		$this->port = $port;
		$this->conn_timeout = $conn_timeout;
		$this->timeout = $timeout;
		$this->connect();
		$this->addHeader("Host:", $this->host);
	}
	protected function connect()
	{
		$this->socket = @fsockopen($this->host, $this->port, $this->errno, $this->errstr, $this->conn_timeout);
		if($this->socket === FALSE)
			throw new \Nette\Application\ApplicationException("Can´t open connection to given host.");
		if($this->timeout !== NULL)
			stream_set_timeout($this->socket, $this->timeout);
	}
	public function close()
	{
		fclose($this->socket);
		return $this;
	}
	protected function clearCache()
	{
		$this->responseHeaders = array();
		$this->data = "";
	}
	public function addHeader($name, $value)
	{
		$this->headers[$name] = $value;
		return $this;
	}
	public function sendHeaders()
	{
		$this->clearCache();
		$headers  = "GET $this->file HTTP/1.1\r\n";
		foreach($this->headers as $key => $value)
		{
			$headers .= "$key $value\r\n";
		}
        $headers .= "\r\n";
		fwrite($this->socket, $headers);
		return $this;
	}
	public function postData($data)
	{
		$this->clearCache();
		$content = http_build_query($data);
		$headers  = "POST $this->file HTTP/1.1\r\n";
		$ignored = array("Content-Type:", "Content-Length:");
		foreach($this->headers as $key => $value)
		{
			if(in_array($key, $ignored)) continue;
			$headers .= "$key $value\r\n";
		}
		$headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$headers .= "Content-Length: ".strlen($content)."\r\n";
        $headers .= "\r\n";
		fwrite($this->socket, $headers);
		fwrite($this->socket, $content);
		return $this;
	}
	public function getResponseHeaders()
	{
		$this->responseHeaders = array();
		$i = 0;

		while($ret = fgets($this->socket))
		{
			$matches = array();
			$ret = str_replace("\r", "", str_replace("\n", "", $ret));
			if($i++ == 0)
            {
                $tmp = explode(" ", $ret);
                // HTTP response headers are like "HTTP/1.1 code message"
                $this->responseHeaders["responseCode"] =(int) $tmp[1];
                $this->responseHeaders["responseMessage"] = $ret;
				continue;
			}

			if($ret == "") // start of data
			{
				break;
			}
			preg_match("/[^:]*:/", $ret, $matches);
			if($matches)
			{
				$this->responseHeaders[$matches[0]] = str_replace($matches[0]." ", "", $ret);
			}

        }
		return $this->responseHeaders;
	}
	public function getData()
	{
		$data = "";
		if(empty($this->responseHeaders))
			return false;
		$acceptable = array(200, 206);
		if(!in_array($this->responseHeaders["responseCode"], $acceptable))
				return false;

		while($ret = fgets($this->socket))
		{
			$data .= $ret;
		}
		return $data;
	}
	public function rangeDownload($id, $cache, $step = 1024)
	{

		$step *= 1024;
		$key = $this->file.$id;
		$data = $cache->load($key);
		$i = 0;
		$first_headers = array();
		while(true)
		{
			$start = strlen($data);
			$end = $start + $step;
			$this->addHeader("Range:", "bytes=$start-$end");
			$this->addHeader("Connection:", "keep-alive");
			if(!empty($headers) && $headers["Connection:"] == "close")
				$this->connect();
			$this->sendHeaders();
			$headers = $this->getResponseHeaders();
			if($i++ == 0)
			{
				$first_headers = $headers;
			}
            // range not Satisfiable => end of file
			if($headers["responseCode"] == 416)
            {
				break;
            }
            //error
            if($headers["responseCode"] > 400)
            {
                throw new \Nette\Application\ApplicationException("Downloading file failed. HTTP error code $headers[responseCode]");
            }
			$data .= $this->getData();
			$cache->save($key, $data, array(
				Cache::EXPIRATION => "+ 5 minutes"
            ));
            // OK
			if($headers["responseCode"] == 200)
			{
				break;
			}
		}
		if(isset($first_headers["Transfer-Encoding:"]) && $first_headers["Transfer-Encoding:"] == "chunked")
		{
			$data = $this->unchunkString($data);
		}
		if(isset($first_headers["Content-Encoding:"]) && $first_headers["Content-Encoding:"] == "gzip")
		{
			$data = zlib_decode($data);
		}
		return $data;
	}
	protected function unchunkString ($str) {

		// A string to hold the result
		$result = '';

		// Split input by CRLF
		$parts = explode("\r\n", $str);

		// These vars track the current chunk
		$chunkLen = 0;
		$thisChunk = '';

		// Loop the data
		while (($part = array_shift($parts)) !== NULL) {
		  if ($chunkLen) {
			// Add the data to the string
			// Don't forget, the data might contain a literal CRLF
			$thisChunk .= $part."\r\n";
			if (strlen($thisChunk) == $chunkLen) {
			  // Chunk is complete
			  $result .= $thisChunk;
			  $chunkLen = 0;
			  $thisChunk = '';
			} else if (strlen($thisChunk) == $chunkLen + 2) {
			  // Chunk is complete, remove trailing CRLF
			  $result .= substr($thisChunk, 0, -2);
			  $chunkLen = 0;
			  $thisChunk = '';
			} else if (strlen($thisChunk) > $chunkLen) {
			  // Data is malformed
			  return FALSE;
			}
		  } else {
			// If we are not in a chunk, get length of the new one
			if ($part === '') continue;
			if (!$chunkLen = hexdec($part)) break;
		  }
		}

		// Return the decoded data of FALSE if it is incomplete
		return ($chunkLen) ? FALSE : $result;

	}
	function __destruct()
	{
		$this->close();
	}
}
