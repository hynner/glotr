<?php
namespace Drahak\Restful\Validation;

use Drahak\Restful\InvalidStateException;
use Nette\InvalidArgumentException;
use Nette\Callback;
use Nette\Object;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

/**
 * Rule validator
 * @package Drahak\Restful\Validation
 * @author Drahomír Hanák
 */
class Validator extends Object implements IValidator
{

	/** @var array Command handle callbacks */
	public $handle = array(
		self::EMAIL => array(__CLASS__, 'validateEmail'),
		self::URL => array(__CLASS__, 'validateUrl'),
		self::REGEXP => array(__CLASS__, 'validateRegexp'),
		self::EQUAL => array(__CLASS__, 'validateEquality')
	);

	/**
	 * Validate value for this rule
	 * @param mixed $value
	 * @param Rule $rule
	 * @return void
	 *
	 * @throws ValidationException
	 * @throws InvalidStateException
	 */
	public function validate($value, Rule $rule)
	{
		if (isset($this->handle[$rule->expression])) {
			try {
				$callback = new Callback($this->handle[$rule->expression]);
				$callback->invokeArgs(array($value, $rule));
				return;
			} catch (InvalidArgumentException $e) {
				throw new InvalidStateException('Handle for expression ' . $rule->expression . ' not found or is not callable');
			}
		}

		$expression = $this->parseExpression($rule);
		if (!Validators::is($value, $expression)) {
			throw ValidationException::createFromRule($rule);
		}
	}

	/**
	 * Parse nette validator expression
	 * @param Rule $rule
	 * @return string
	 */
	private function parseExpression(Rule $rule)
	{
		return vsprintf($rule->expression, $rule->argument);
	}

	/******************** Special validators ********************/

	/**
	 * Validate regexp
	 * @param mixed $value
	 * @param Rule $rule
	 *
	 * @throws InvalidArgumentException
	 * @throws ValidationException
	 */
	public static function validateRegexp($value, Rule $rule)
	{
		if (!isset($rule->argument[0])) {
			throw new InvalidArgumentException('No regular expression found in pattern validation rule');
		}

		if (!Strings::match($value, $rule->argument[0])) {
			throw ValidationException::createFromRule($rule);
		}
	}

	/**
	 * Validate equality
	 * @param string $value
	 * @param Rule $rule
	 * @throws ValidationException
	 */
	public static function validateEquality($value, Rule $rule)
	{
		if (!in_array($value, $rule->argument)) {
			throw ValidationException::createFromRule($rule);
		}
	}

	/**
	 * Validate email
	 * @param string $value
	 * @param Rule $rule
	 * @throws ValidationException
	 */
	public static function validateEmail($value, Rule $rule)
	{
		if (!Validators::isEmail($value)) {
			throw ValidationException::createFromRule($rule);
		}
	}

	/**
	 * Validate URL
	 * @param string $value
	 * @param Rule $rule
	 * @throws ValidationException
	 */
	public static function validateUrl($value, Rule $rule)
	{
		if (!Validators::isUrl($value)) {
			throw ValidationException::createFromRule($rule);
		}
	}

}