<?php
namespace ApiModule;

use Drahak\Restful\Application\ResourcePresenter;
use Drahak\Restful\IResource;
use GLOTR\Restful\Security\Process\UserSecuredAuthentication;
use \Drahak\Restful\Security\AuthenticationException;

abstract class BasePresenter extends ResourcePresenter
{
	const VERSION = "0.0.1";
	/** @var string */
	protected $defaultContentType = IResource::JSON;
	/** @var array */
	protected $typeMap = array(
	   'json' => IResource::JSON
	);
	/** @var \GLOTR\GLOTRApi */
	protected $glotrApi;
	/** @var array */
	protected $parameters;
	/** @var \GLOTR\Users */
	protected $users;
	/** @var \Nette\Database\Connection */
	protected $connection;

	  /** @var UserSecuredAuthentication */
    private $auth;
	/** @var boolean It needs to be set to FALSE, otherwise it will redirect automatically! */
	public $autoCanonicalize = FALSE;

	public function __construct(\Nette\DI\Container $context = NULL, \GLOTR\GLOTRApi $glotrApi, \GLOTR\Users $users, UserSecuredAuthentication $auth,  \Nette\Database\Connection $conn)
	{
		parent::__construct($context);
		$this->glotrApi = $glotrApi;
		$this->parameters = $context->parameters;
		$this->users = $users;
		$this->auth = $auth;
		$this->connection = $conn;


	}
	protected function startup()
	{
		parent::startup();

		$response = $this->getHttpResponse();
		$request = $this->getHttpRequest();
		// allow cross-domain requests - neccessary for ingame tools
		$response->setHeader("Access-Control-Allow-Origin",$request->getHeader("Origin"));
		$response->setHeader("Access-Control-Allow-Headers", $request->getHeader("Access-Control-Request-Headers"). ", x-requested-with");
		$response->setHeader("Access-Control-Allow-Methods", $request->getHeader("Access-Control-Request-Method").", OPTIONS");
		$response->setHeader("Access-Control-Max-Age", "5");
		// this is needed when making cross-domain request in the browser - for "preflight" request
		// http://www.w3.org/TR/cors/#preflight-request
		if($request->getMethod() === "OPTIONS")
		{

			$response->setCode(204);
			$this->sendResponse(new \Nette\Application\Responses\TextResponse(""));
		}
		if(!$this->getUser()->isLoggedIn())
		{
			// Authentication
			// first get api key by user
			try{
				$this->auth->getPrivateKeyForRequest($this->getHttpRequest());
			}
			catch(AuthenticationException $e)
			{
				$this->sendErrorResource($e);
			}
			$this->authentication->setAuthProcess($this->auth);
		}

		$this->resource->apiVersion = self::VERSION;
	}
	/**
	 * If type is set in URL, it will always override headers
	 * @param string $type
	 */
	protected function overrideOutputType($type)
	{
		if($type !== NULL && isset($this->typeMap[$type]))
		{
			$this->sendResource($this->typeMap[$type]);
		}
	}
}