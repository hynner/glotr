<?php
namespace Drahak\Restful;

use Drahak\Restful\IResource;
use Drahak\Restful\Http\IRequest;
use Drahak\Restful\Resource\EnvelopeDecorator;
use Drahak\Restful\Utils\RequestFilter;
use Nette\Http\IResponse;
use Nette\Http\Url;
use Nette\Object;
use Nette\Utils\Paginator;

/**
 * REST ResponseFactory
 * @package Drahak\Restful
 * @author Drahomír Hanák
 */
class ResponseFactory extends Object implements IResponseFactory
{

	/** @var IResponse */
	private $response;

	/** @var IRequest */
	private $request;

	/** @var RequestFilter */
	private $filter;

	/** @var array */
	private $responses = array(
		IResource::JSON => 'Drahak\Restful\Application\Responses\JsonResponse',
		IResource::JSONP => 'Drahak\Restful\Application\Responses\JsonpResponse',
		IResource::QUERY => 'Drahak\Restful\Application\Responses\QueryResponse',
		IResource::XML => 'Drahak\Restful\Application\Responses\XmlResponse',
		IResource::DATA_URL => 'Drahak\Restful\Application\Responses\DataUrlResponse',
		IResource::NULL => 'Drahak\Restful\Application\Responses\NullResponse'
	);

	/** @var array Default response code for each request method */
	protected $defaultCodes = array(
		IRequest::GET => 200,
		IRequest::POST => 201,
		IRequest::PUT => 200,
		IRequest::PATCH => 200,
		IRequest::HEAD => 200,
		IRequest::DELETE => 200,
	);

	public function __construct(IResponse $response, IRequest $request, RequestFilter $filter)
	{
		$this->response = $response;
		$this->request = $request;
		$this->filter = $filter;
	}

	/**
	 * Register new response type to factory
	 * @param string $mimeType
	 * @param string $responseClass
	 * @return $this
	 *
	 * @throws InvalidArgumentException
	 */
	public function registerResponse($mimeType, $responseClass)
	{
		if (!class_exists($responseClass)) {
			throw new InvalidArgumentException('Response class does not exist.');
		}

		$this->responses[$mimeType] = $responseClass;
		return $this;
	}

	/**
	 * Unregister API response fro mfactory
	 * @param string $mimeType
	 */
	public function unregisterResponse($mimeType)
	{
		unset($this->responses[$mimeType]);
	}

	/**
	 * Create new api response
	 * @param IResource $resource
	 * @param int|null $code
	 * @return IResponse
	 *
	 * @throws InvalidStateException
	 */
	public function create(IResource $resource, $code = NULL)
	{
		$contentType = $resource->getContentType();
		if (!isset($this->responses[$contentType])) {
			throw new InvalidStateException('Unregistered API response.');
		}

		if (!class_exists($this->responses[$contentType])) {
			throw new InvalidStateException('API response class does not exist.');
		}

		if ($this->request->isJsonp()) {
			$contentType = IResource::JSONP;
		}

		$this->setupCode($resource, $code);
		$this->setupPaginator($resource, $code);

		$responseClass = $this->responses[$contentType];
		$response = new $responseClass($resource->getData());
		return $response;
	}

	/**
	 * Setup response HTTP code
	 * @param IResource $resource
	 * @param int|null $code
	 */
	protected function setupCode(IResource $resource, $code = NULL)
	{
		if ($code === NULL) {
			$code = $this->defaultCodes[$this->request->getMethod()];
			if (!$resource->getData()) {
				$code = 204; // No content
			}
		}
		$this->response->setCode($code);
	}

	/**
	 * Setup paginator
	 * @param IResource $resource
	 * @param int|null $code
	 */
	protected function setupPaginator(IResource $resource, $code = NULL)
	{
		try {
			$paginator = $this->filter->getPaginator();

			$link = '<' . $this->getNextPageUrl($paginator) . '>; rel="next"';
			if ($paginator->getItemCount()) {
				$link .= ', <' . $this->getLastPageUrl($paginator) . '>; rel="last"';
			}
			$this->response->setHeader('X-Total-Count', $paginator->getItemCount() ? $paginator->getItemCount() : NULL);
			$this->response->setHeader('Link', $link);
		} catch (InvalidStateException $e) {
			// Don't use paginator
		}
	}

	/**
	 * Get next page URL
	 * @param Paginator $paginator
	 * @return Url
	 */
	private function getNextPageUrl(Paginator $paginator)
	{
		$url = $this->request->getUrl();
		parse_str($url->getQuery(), $query);
		$paginator->setPage($paginator->getPage()+1);
		$query['offset'] = $paginator->getOffset();
		$query['limit'] = $paginator->getItemsPerPage();
		return $url->appendQuery($query);
	}

	/**
	 * Get last page URL
	 * @param Paginator $paginator
	 * @return Url
	 */
	private function getLastPageUrl(Paginator $paginator)
	{
		$url = $this->request->getUrl();
		parse_str($url->getQuery(), $query);
		$query['offset'] = $paginator->getLastPage() * $paginator->getItemsPerPage() - $paginator->getItemsPerPage();
		$query['limit'] = $paginator->getItemsPerPage();
		return $url->appendQuery($query);
	}

}