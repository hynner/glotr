<?php
namespace Drahak\Restful;

use Drahak\Restful\Mapping\MapperContext;
use Drahak\Restful\Validation\IValidationSchema;
use Drahak\Restful\Validation\IValidationSchemaAggregate;
use IteratorAggregate;
use Drahak\Restful\IInput;
use Drahak\Restful\Mapping\IMapper;
use Nette\MemberAccessException;
use Nette\Object;
use Nette\Http;
use Nette\Utils\Json;
use Nette\Utils\Strings;

/**
 * Request Input parser
 * @package Drahak\Restful\Input
 * @author Drahomír Hanák
 *
 * @property-read array $data
 */
class Input extends Object implements IteratorAggregate, IInput
{

	/** @var \Nette\Http\IRequest */
	private $httpRequest;

	/** @var array */
	private $data;

	/** @var IValidationSchema */
	private $validationSchema;

	/** @var IMapper */
	protected $mapper;

	/**
	 * @param Http\IRequest $httpRequest
	 * @param MapperContext $mapperContext
	 * @param IValidationSchema $validationSchema
	 */
	public function __construct(Http\IRequest $httpRequest, MapperContext $mapperContext, IValidationSchema $validationSchema)
	{
		$this->httpRequest = $httpRequest;
		$this->validationSchema = $validationSchema;
		try {
			$this->mapper = $mapperContext->getMapper($httpRequest->getHeader('Content-Type'));

		} catch (InvalidStateException $e) {
			// No mapper for this content type - ignore in this step
		}
	}

	/******************** IInput ********************/

	/**
	 * Set input mapper
	 * @param IMapper $mapper
	 * @return IInput
	 */
	public function setMapper(IMapper $mapper)
	{
		$this->mapper = $mapper;
		return $this;
	}
	/**
	 * Get input mapper
	 * @return IMapper
	 */
	public function getMapper()
	{
		return $this->mapper;
	}
	/**
	 * Get parsed input data
	 * @return array
	 *
	 * @throws Application\BadRequestException when mapper for this Content-Type not found
	 */
	public function getData()
	{
		if (!$this->data) {
			$this->data = $this->parseData();
		}
		return $this->data;
	}
	public function getRawData()
	{
		if ($this->httpRequest->getPost()) {
			return http_build_query($this->httpRequest->getPost());
		} else if ($input = file_get_contents('php://input')) {
			return $input;
		}
		return http_build_query($this->httpRequest->getQuery());
	}
	/**
	 * Parse data from input
	 * @return array|mixed|\Traversable
	 * @throws Application\BadRequestException
	 */
	private function parseData()
	{
		if ($this->httpRequest->getPost()) {
			return $this->httpRequest->getPost();
		} else if ($input = file_get_contents('php://input')) {
			if (!$this->mapper) {
				throw Application\BadRequestException::unsupportedMediaType(
					'No mapper defined for Content-Type ' . $this->httpRequest->getHeader('Content-Type')
				);
			}
			return $this->mapper->parse($input);
		}
		return $this->httpRequest->getQuery();
	}

	/******************** Magic methods ********************/

	/**
	 * @param string $name
	 * @return mixed
	 *
	 * @throws \Exception|\Nette\MemberAccessException
	 */
	public function &__get($name)
	{
		try {
			return parent::__get($name);
		} catch(MemberAccessException $e) {
			$data = $this->getData();
			if (isset($data[$name])) {
				return $data[$name];
			}
			throw $e;
		}
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		$isset = parent::__isset($name);
		if ($isset) {
			return TRUE;
		}
		$data = $this->getData();
		return isset($data[$name]);
	}

	/******************** Iterator aggregate interface ********************/

	/**
	 * Get input data iterator
	 * @return InputIterator
	 */
	public function getIterator()
	{
		return new InputIterator($this);
	}

	/******************** Aggregate validation schema interface ********************/

	/**
	 * Get validation field
	 * @param string $name
	 * @return Validation\IField
	 */
	public function field($name)
	{
		return $this->getValidationSchema()->field($name);
	}

	/**
	 * Validate input data
	 * @return array
	 */
	public function validate()
	{
		return $this->getValidationSchema()->validate($this->getData());
	}

	/**
	 * Is input valid
	 * @return bool
	 */
	public function isValid()
	{
		return !$this->validate();
	}

	/**
	 * Get validation scope
	 * @return IValidationSchema
	 */
	public function getValidationSchema()
	{
		return $this->validationSchema;
	}

}