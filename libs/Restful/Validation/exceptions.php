<?php
namespace Drahak\Restful\Validation;

use Exception;
use Drahak\Restful\LogicException;

/**
 * ValidationException is thrown when validation problem appears
 * @package Drahak\Restful\Validation
 * @author Drahomír Hanák
 */
class ValidationException extends LogicException
{

	/** @var string */
	protected $field;

	/**
	 * @param string $field
	 * @param string $message
	 * @param int $code
	 * @param Exception $previous
	 */
	public function __construct($field, $message = "", $code = 0, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
		$this->field = $field;
	}

	/**
	 * Get validation field name
	 * @return string
	 */
	public function getField()
	{
		return $this->field;
	}

	/**
	 * Validation exception simple factory
	 * @param Rule $rule
	 * @return ValidationException
	 */
	public static function createFromRule(Rule $rule)
	{
		return new self($rule->getField(), vsprintf($rule->getMessage(), $rule->getArgument()), $rule->getCode());
	}

}