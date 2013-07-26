<?php
namespace Drahak\Restful\Validation;

use Nette\Object;

/**
 * ValidationSchema
 * @package Drahak\Restful\Validation
 * @author Drahomír Hanák
 *
 * @property-read IValidator $validator
 */
class ValidationSchema extends Object implements IValidationSchema
{

	/** @var IValidator */
	private $validator;

	/** @var array */
	private $fields = array();

	public function __construct(IValidator $validator)
	{
		$this->validator = $validator;
	}

	/****************** Validation scope interface ******************/

	/**
	 * Create field or get existing
	 * @param string $name
	 * @return IField
	 */
	public function field($name)
	{
		if (!isset($this->fields[$name])) {
			$this->fields[$name] = $this->createField($name);
		}
		return $this->fields[$name];
	}

	/**
	 * Validate all field in collection
	 * @param array $data
	 * @return array of fields with error information
	 */
	public function validate(array $data)
	{
		$errors = array();
		/** @var IField $field */
		foreach ($this->fields as $field) {
			$value = isset($data[$field->getName()]) ? $data[$field->getName()] : NULL;
			$fieldErrors = $field->validate($value);
			if (!$fieldErrors) {
				continue;
			}
			$errors += $fieldErrors;
		}
		return $errors;
	}

	/**
	 * Create field
	 * @param string $name
	 * @return Field
	 */
	protected function createField($name)
	{
		return new Field($name, $this->getValidator());
	}

	/****************** Getters & setters ******************/

	/**
	 * Get validator
	 * @return mixed
	 */
	public function getValidator()
	{
		return $this->validator;
	}

}