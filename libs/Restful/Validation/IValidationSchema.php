<?php
namespace Drahak\Restful\Validation;

/**
 * IValidationSchema
 * @package Drahak\Restful\Validation
 * @author Drahomír Hanák
 */
interface IValidationSchema
{

	/**
	 * Create field or get existing
	 * @param string $name
	 * @return IField
	 */
	public function field($name);

	/**
	 * Validate all field in collection
	 * @param array $data
	 * @return array
	 */
	public function validate(array $data);

}