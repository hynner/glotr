<?php
namespace Drahak\Restful\Utils;

use Drahak\Restful\Http\IRequest;
use Drahak\Restful\InvalidStateException;
use Nette\Object;
use Nette\Utils\Paginator;

/**
 * RequestFilter
 * @package Drahak\Restful\Utils
 * @author Drahomír Hanák
 *
 * @property-read IQueryList $fieldList
 * @property-read IQueryList $sortList
 * @property-read string $searchQuery
 * @property-read Paginator $paginator
 */
class RequestFilter extends Object
{

	/** @var IQueryList */
	private $fieldList;

	/** @var IQueryList */
	private $sortList;

	/** @var Paginator */
	private $paginator;

	/** @var IRequest */
	private $request;

	public function __construct(IRequest $request)
	{
		$this->request = $request;
	}

	/**
	 * Get fields list
	 * @return IQueryList
	 */
	public function getFieldList()
	{
		if (!$this->fieldList) {
			$fields = array_filter(explode(',', $this->request->getQuery('fields')));
			$this->fieldList = new QueryList($fields);
		}
		return $this->fieldList;
	}

	/**
	 * Create sort list
	 * @return IQueryList
	 */
	public function getSortList()
	{
		if (!$this->sortList) {
			$this->sortList = new QueryList(array_filter(explode(',', $this->request->getQuery('sort'))));
		}
		return $this->sortList;
	}

	/**
	 * Get search query
	 * @return string|NULL
	 */
	public function getSearchQuery()
	{
		return $this->request->getQuery('q');
	}

	/**
	 * Get paginator
	 * @param string|NULL $offset default value
	 * @param string|NULL $limit default value
	 * @return Paginator
	 *
	 * @throws InvalidStateException
	 */
	public function getPaginator($offset = NULL, $limit = NULL)
	{
		if (!$this->paginator) {
			$offset = $this->request->getQuery('offset', $offset);
			$limit = $this->request->getQuery('limit', $limit);

			if ($offset === NULL || $limit === NULL) {
				throw new InvalidStateException(
					'To create paginator add offset and query parameter to request URL'
				);
			}

			$paginator = new Paginator();
			$paginator->setItemsPerPage($limit);
			$paginator->setPage(floor($offset/$limit)+1);
			$this->paginator = $paginator;
		}
		return $this->paginator;
	}

}