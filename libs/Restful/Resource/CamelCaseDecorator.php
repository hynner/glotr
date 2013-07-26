<?php
namespace Drahak\Restful\Resource;
use Nette\Utils\Strings;

/**
 * CamelCaseDecorator
 * @package Drahak\Restful\Resource
 * @author Drahomír Hanák
 */
class CamelCaseDecorator extends Decorator
{

	/**
	 * Get data with converted keys to camel case
	 * @return array|\stdClass|\Traversable|void
	 */
	public function getData()
	{
		$data = parent::getData();
		$this->convertToCamel($data);
		return $data;
	}

	/**
	 * Convert array keys to camel case
	 * @param array|\Traversable $array
	 */
	private function convertToCamel(&$array)
	{
		$func = function($matches) {
			return Strings::upper($matches[2]);
		};

		if ($array instanceof \Traversable) {
			$array = iterator_to_array($array);
		}

		foreach (array_keys($array) as $key) {
			$value = &$array[$key];
			unset($array[$key]);

			$transformedKey = Strings::replace($key, '/(_| |-)([a-z])/', $func);
			if (is_array($value) || $value instanceof \Traversable) {
				$this->convertToCamel($value);
			}
			$array[$transformedKey] = $value;
			unset($value);
		}
	}

}