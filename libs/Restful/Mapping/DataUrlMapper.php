<?php
namespace Drahak\Restful\Mapping;

use Drahak\Restful\InvalidArgumentException;
use Nette\Object;
use Nette\Utils\Strings;

/**
 * DataUrlMapper - encode or decode base64 file
 * @package Drahak\Restful\Mapping
 * @author Drahomír Hanák
 */
class DataUrlMapper extends Object implements IMapper
{

	/**
	 * Create DATA URL from file path
	 * @param array $data (type => string, src => string)
	 * @param bool $prettyPrint
	 * @return string
	 *
	 * @throws \Drahak\Restful\InvalidArgumentException
	 */
	public function stringify($data, $prettyPrint = TRUE)
	{
		if (!isset($data['src']) || !isset($data['type'])) {
			throw new InvalidArgumentException('DataUrlMapper expects array(src => \'\', contentType => \'\')');
		}
		$src = base64_encode($data['src']);
		return 'data:' . $data['type'] . ';base64,'. $src;
	}

	/**
	 * Convert client request data to array or traversable
	 * @param mixed $data
	 * @return array (type => string|null, src => string)
	 *
	 * @throws MappingException
	 */
	public function parse($data)
	{
		$matches = Strings::match($data, "@^data:([\w/]+?);(\w+?),(.*)$@si");
		if (!$matches) {
			throw new MappingException('Given data URL is invalid.');
		}

		return array(
			'type' => $matches[1],
			'src' => base64_decode($matches[3])
		);
	}

}