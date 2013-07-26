<?php
namespace Drahak\Restful\Http;

use Drahak\Restful\IResource;
use Nette;
use Nette\Utils\Strings;

/**
 * HTTP Request
 * @package Drahak\Restful\Http
 * @author Drahomír Hanák
 *
 * @property-write string $jsonpKey
 * @property-read array $formats
 */
class Request extends Nette\Http\Request implements IRequest
{

	/** Request method header override name */
	const METHOD_OVERRIDE_HEADER = 'X-HTTP-Method-Override';

	/** Request method override query parameter name */
	const METHOD_OVERRIDE_PARAM = '__method';

	/** @var string */
	private $jsonpKey = 'jsonp';

	/** @var string */
	private $prettyPrintKey = 'pretty';

	/** @var array */
	protected $formats = array(
		'json' => IResource::JSON,
		'xml' => IResource::XML,
		'jsonp' => IResource::JSONP,
		'query' => IResource::QUERY,
		'data_url' => IResource::DATA_URL
	);

	/**
	 * Get formats map
	 * @return array
	 */
	public function getFormats()
	{
		return $this->formats;
	}

	/**
	 * Set JSONP parameter name in query string
	 * @param string $name
	 */
	public function setJsonpKey($name)
	{
		$this->jsonpKey = $name;
	}

	/**
	 * Set pretty print parameter name in query string
	 * @param string $name
	 */
	public function setPrettyPrintKey($name)
	{
		$this->prettyPrintKey = $name;
	}

	/**
	 * Get request method
	 * @return string
	 */
	public function getMethod()
	{
		$method = parent::getMethod();
		if ($method !== self::POST) {
			return $method;
		}

		// Override request method with query param
		if ($this->getQuery(self::METHOD_OVERRIDE_PARAM)) {
			return $this->getQuery(self::METHOD_OVERRIDE_PARAM);
		}

		// Override request method with header
		if ($this->getHeader(self::METHOD_OVERRIDE_HEADER)) {
			return $this->getHeader(self::METHOD_OVERRIDE_HEADER);
		}
		return $method;
	}

	/**
	 * Get original method
	 * @return string
	 */
	public function getOriginalMethod()
	{
		return parent::getMethod();
	}

	/**
	 * Is JSONP request
	 * @return bool
	 */
	public function isJsonp()
	{
		return (bool)$this->getJsonp();
	}

	/**
	 * Get JSONP value - callback function name
	 * @return string|NULL
	 */
	public function getJsonp()
	{
		return $this->getQuery($this->jsonpKey);
	}

	/**
	 * Is pretty print enabled
	 * @return bool
	 */
	public function isPrettyPrint()
	{
		$prettyPrint = $this->getQuery($this->prettyPrintKey);
		if ($prettyPrint === 'false') {
			return FALSE;
		}
		return $prettyPrint === NULL ? TRUE : (bool)$prettyPrint;
	}

	/**
	 * Get preferred request content type
	 * @return string
	 */
	public function getPreferredContentType()
	{
		$accept = explode(',', $this->getHeader('Accept'));
		foreach ($accept as $mimeType) {
			foreach ($this->formats as $formatMime) {
				if (Strings::contains($mimeType, $formatMime)) {
					return $formatMime;
				}
			}
		}
	}


}