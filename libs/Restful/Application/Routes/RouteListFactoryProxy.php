<?php
namespace Drahak\Restful\Application\Routes;

use Drahak\Restful\Application\RouteAnnotation;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;
use Nette\Utils\Finder;

/**
 * RouteListFactoryProxy
 * @package Drahak\Restful\Application\Routes
 * @author Drahomír Hanák
 */
final class RouteListFactoryProxy extends Object implements IRouteListFactory
{

	const CACHE_NAME = 'resourceRouteList';

	/** @var array */
	private $routeConfig;

	/** @var \Nette\Caching\Cache */
	private $cache;

	/** @var \Drahak\Restful\Application\RouteAnnotation */
	private $routeAnnotation;

	/**
	 * @param array $routeConfig
	 * @param IStorage $cacheStorage
	 * @param RouteAnnotation $annotation
	 */
	public function __construct(array $routeConfig, IStorage $cacheStorage, RouteAnnotation $annotation)
	{
		$this->routeConfig = $routeConfig;
		$this->cache = new Cache($cacheStorage);
		$this->routeAnnotation = $annotation;
	}

	/**
	 * Create resources route list
	 * @param string|null $module
	 * @return ResourceRouteList
	 */
	public function create($module = NULL)
	{
		$routeList = $this->cache->load(self::CACHE_NAME);
		if ($routeList !== NULL) {
			return $routeList;
		}
		return $this->createCached($module);
	}

	/**
	 * Create cached route list
	 * @param null $module
	 * @return ResourceRouteList
	 */
	private function createCached($module = NULL)
	{
		$files = array();
		$presenterFiles = Finder::findFiles('*Presenter.php')->from($this->routeConfig['presentersRoot']);
		foreach ($presenterFiles as $path => $splFile) {
			$files[] = $path;
		}

		$factory = new RouteListFactory($this->routeConfig, $this->cache->storage, $this->routeAnnotation);
		$routeList = $factory->create($module);
		$this->cache->save(self::CACHE_NAME, $routeList, array(
			Cache::FILES => $files
		));
		return $routeList;
	}

}