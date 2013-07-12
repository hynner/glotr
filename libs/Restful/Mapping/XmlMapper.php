<?php
namespace Drahak\Restful\Mapping;

use Drahak\Restful\InvalidArgumentException;
use Nette\Object;

/**
 * XmlMapper
 * @package Drahak\Restful\Mapping
 * @author Drahomír Hanák
 */
class XmlMapper extends Object implements IMapper
{

	/** @var \DOMDocument */
	private $xml;

	/** @var null|string */
	private $rootElement;

	/**
	 * @param string|null $rootElement
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct($rootElement = NULL)
	{
		$this->rootElement = $rootElement;
		$this->xml = new \DOMDocument('1.0', 'UTF-8');
		$this->xml->formatOutput = TRUE;
	}

	/**
	 * Set XML root element
	 * @param string|null $rootElement
	 * @return XmlMapper
	 *
	 * @throws InvalidArgumentException
	 */
	public function setRootElement($rootElement)
	{
		if (!is_string($rootElement) && $rootElement !== NULL) {
			throw new InvalidArgumentException('Root element must be of type string or null if disabled');
		}
		$this->rootElement = $rootElement;
		return $this;
	}

	/**
	 * Get XML root element
	 * @return null|string
	 */
	public function getRootElement()
	{
		return $this->rootElement;
	}

	/**
	 * Parse traversable or array resource data to XML
	 * @param array|\Traversable $data
	 * @param bool $prettyPrint
	 * @return mixed|string
	 *
	 * @throws \Drahak\Restful\InvalidArgumentException
	 */
	public function stringify($data, $prettyPrint = TRUE)
	{
		if (!is_array($data) && !($data instanceof \Traversable)) {
			throw new InvalidArgumentException('Data must be of type array or Traversable');
		}

		if ($data instanceof \Traversable) {
			$data = iterator_to_array($data);
		}

		if ($this->rootElement) {
			$data = array($this->rootElement => $data);
		}

		$this->toXml($data);
		$this->xml->preserveWhiteSpace = $prettyPrint;
		$this->xml->formatOutput = $prettyPrint;
		return $this->xml->saveXML();
	}

	/**
	 * Parse XML to array
	 * @param string $data
	 * @return array
	 */
	public function parse($data)
	{
		return $this->fromXml($data);
	}

	/**
	 * @param string $data
	 * @return array
	 */
	private function fromXml($data)
	{
		$xml = new \SimpleXMLElement($data);
		return (array)$xml;
	}

	/**
	 * @param $data
	 * @param null $domElement
	 */
	private function toXml($data, $domElement = NULL)
	{
		$domElement = is_null($domElement) ? $this->xml : $domElement;

		if (is_array($data) || $data instanceof \Traversable) {
			foreach ($data as $index => $mixedElement) {
				if (is_int($index)) {
					if ($index == 0) {
						$node = $domElement;
					} else {
						$node = $this->xml->createElement($domElement->tagName);
						$domElement->parentNode->appendChild($node);
					}
				} else {
					$node = $this->xml->createElement($index);
					$domElement->appendChild($node);
				}
				$this->toXml($mixedElement, $node);
			}
		} else {
			$domElement->appendChild($this->xml->createTextNode($data));
		}
	}

}