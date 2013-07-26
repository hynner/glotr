<?php
namespace Drahak\Restful\Application;

use Drahak\Restful\IInput;
use Drahak\Restful\IResourceFactory;
use Drahak\Restful\IResponseFactory;
use Drahak\Restful\InvalidStateException;
use Drahak\Restful\IResource;
use Drahak\Restful\Resource;
use Drahak\Restful\Security\AuthenticationContext;
use Drahak\Restful\Security\RequestAuthenticator;
use Drahak\Restful\Security\SecurityException;
use Drahak\Restful\Utils\RequestFilter;
use Drahak\Restful\Validation\ValidationException;
use Nette\Caching\Cache;
use Nette\Callback;
use Nette\Utils\Strings;
use Nette\Application;
use Nette\Application\UI;
use Nette\Application\IResponse;
use Nette\Http;

/**
 * Base presenter for REST API presenters
 * @package Drahak\Restful\Application
 * @author Drahomír Hanák
 */
abstract class ResourcePresenter extends UI\Presenter implements IResourcePresenter
{

	/** @internal */
	const VALIDATE_ACTION_PREFIX = 'validate';

	/** @var IResource */
	protected $resource;

	/** @var IInput */
	protected $input;

	/** @var RequestFilter */
	protected $requestFilter;

	/** @var IResourceFactory */
	protected $resourceFactory;

	/** @var IResponseFactory */
	protected $responseFactory;

	/** @var AuthenticationContext */
	protected $authentication;

	/**
	 * Inject Drahak Restful
	 * @param IResponseFactory $responseFactory
	 * @param IResourceFactory $resourceFactory
	 * @param AuthenticationContext $authentication
	 * @param IInput $input
	 * @param RequestFilter $requestFilter
	 */
	public final function injectDrahakRestful(
		IResponseFactory $responseFactory, IResourceFactory $resourceFactory,
		AuthenticationContext $authentication, IInput $input, RequestFilter $requestFilter)
	{
		$this->responseFactory = $responseFactory;
		$this->resourceFactory = $resourceFactory;
		$this->authentication = $authentication;
		$this->requestFilter = $requestFilter;
		$this->input = $input;
	}

	/**
	 * Presenter startup
	 * @throws BadRequestException
	 */
	protected function startup()
	{
		parent::startup();
		$this->resource = $this->resourceFactory->create();

		// calls $this->validate<Action>()
		$validationProcessed = $this->tryCall($this->formatValidateMethod($this->action), $this->params);

		// Check if input is validate
		if (!$this->input->isValid() && $validationProcessed) {
			$errors = $this->input->validate();
			throw BadRequestException::unprocessableEntity($errors, 'Validation Failed: ' . $errors[0]['message']);
		}
	}

	/**
	 * Check security and other presenter requirements
	 * @param $element
	 */
	public function checkRequirements($element)
	{
		try {
			parent::checkRequirements($element);
		} catch (Application\ForbiddenRequestException $e) {
			$this->sendErrorResource($e);
		}

		// Try to authenticate client
		try {
			$this->authentication->authenticate($this->input);
		} catch (SecurityException $e) {
			$this->sendErrorResource($e);
		}
	}

	/**
	 * On before render
	 */
	protected function beforeRender()
	{
		parent::beforeRender();
		$this->sendResource();
	}

	/**
	 * Get REST API response
	 * @param string $contentType
	 * @param int $code
	 * @return IResponse
	 *
	 * @throws InvalidStateException
	 */
	public function sendResource($contentType = NULL, $code = NULL)
	{
		if ($contentType !== NULL) {
			$this->resource->setContentType($contentType);
		}

		$response = $this->responseFactory->create($this->resource, $code);
		$this->sendResponse($response);
	}

	/**
	 * Send error resource to output
	 * @param \Exception $e
	 */
	protected function sendErrorResource(\Exception $e)
	{
		$code = $e->getCode() ? $e->getCode() : 500;

		$this->resource = $this->resourceFactory->create();
		$this->resource->code = $code;
		$this->resource->status = 'error';
		$this->resource->message = $e->getMessage();

		if (isset($e->errors) && $e->errors) {
			$this->resource->errors = $e->errors;
		}

		$this->sendResource(NULL, $code);
	}

	/****************** Format methods ******************/

	/**
	 * Validate action method
	 */
	public static function formatValidateMethod($action)
	{
		return self::VALIDATE_ACTION_PREFIX . $action;
	}

}