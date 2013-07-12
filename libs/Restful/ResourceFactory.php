<?php
namespace Drahak\Restful;

use Drahak\Restful\Http\IRequest;
use Drahak\Restful\Resource\CamelCaseDecorator;
use Drahak\Restful\Resource\DateTimeDecorator;
use Drahak\Restful\Resource\SnakeCaseDecorator;
use Nette\Object;

/**
 * ResourceFactory
 * @package Drahak\Restful
 * @author Drahomír Hanák
 */
class ResourceFactory extends Object implements IResourceFactory
{

	/** Snake case key format */
	const SNAKE_CASE = 'snake_case';
	/** Camel case key format */
	const CAMEL_CASE = 'camelCase';

	/** @var string */
	private $convention = self::CAMEL_CASE;

	/** @var string */
	private $datetimeFormat;

	/** @var IRequest */
	private $request;

	public function __construct($convention, $datetimeFormat, IRequest $request)
	{
		$this->request = $request;
		$this->convention = $convention;
		$this->datetimeFormat = $datetimeFormat;
	}

	/**
	 * Create new API resource
	 * @return IResource
	 */
	public function create()
	{
		// TODO: refactor
		$resource = new Resource;
		$resource->setContentType($this->request->getPreferredContentType());

		$resource = new DateTimeDecorator($resource, $this->datetimeFormat);

		// Conventions
		if ($this->convention === self::SNAKE_CASE) {
			$resource = new SnakeCaseDecorator($resource);
		} else if ($this->convention === self::CAMEL_CASE) {
			$resource = new CamelCaseDecorator($resource);
		}

		return $resource;
	}

}