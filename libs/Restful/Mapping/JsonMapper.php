<?php
namespace Drahak\Restful\Mapping;

use Nette\Object;
use Nette\Utils21\Json;
use Nette\Utils\JsonException;

/**
 * JsonMapper
 * @package Drahak\Restful\Mapping
 * @author Drahomír Hanák
 */
class JsonMapper extends Object implements IMapper
{

	/**
	 * Convert array or Traversable input to string output response
	 * @param array|\Traversable $data
	 * @param bool $prettyPrint
	 * @return mixed
	 *
	 * @throws MappingException
	 */
	public function stringify($data, $prettyPrint = TRUE)
	{
		if ($data instanceof \Traversable) {
			$data = iterator_to_array($data);
		}

		try {
			return Json::encode($data, $prettyPrint ? Json::PRETTY : 0);
		} catch(JsonException $e) {
			throw new MappingException('Error in parsing response: ' .$e->getMessage());
		}
	}

	/**
	 * Convert client request data to array or traversable
	 * @param string $data
	 * @return array to be compatible with other mappers
	 *
	 * @throws MappingException
	 */
	public function parse($data)
	{
		try {
			return (array)Json::decode($data);
		} catch (JsonException $e) {
			throw new MappingException('Error in parsing request: ' . $e->getMessage());
		}
	}


}