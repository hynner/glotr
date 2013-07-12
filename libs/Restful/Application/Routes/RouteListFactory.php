<?php
namespace Drahak\Restful\Application\Routes;

use Drahak\Restful\Application\RouteAnnotation;
use Drahak\Restful\InvalidStateException;
use Nette\Caching\IStorage;
use Nette\DI\Container;
use Nette\Loaders\RobotLoader;
use Nette\Object;
use Nette\Reflection\ClassType;
use Nette\Utils\Strings;

/**
 * RouteListFactory
 * @package Drahak\Restful\Application\Routes
 * @author Drahomír Hanák
 */
class RouteListFactory extends Object implements IRouteListFactory
{

	/** @var \Nette\Loaders\RobotLoader */
	private $loader;

	/** @var array */
	private $routeConfig;

	/** @var \Nette\Caching\IStorage */
	private $cacheStorage;

	/** @var \Drahak\Restful\Application\RouteAnnotation */
	private $routeAnnotation;

	public function __construct(array $routeConfig, IStorage $cacheStorage, RouteAnnotation $routeAnnotation)
	{
		$loader = new RobotLoader();
		$loader->addDirectory($routeConfig['presentersRoot']);
		$loader->setCacheStorage($cacheStorage);
		$loader->tryLoad('Drahak\Restful\Application\IResourcePresenter');

		$this->loader = $loader;
		$this->routeConfig = $routeConfig;
		$this->cacheStorage = $cacheStorage;
		$this->routeAnnotation = $routeAnnotation;
	}

	/**
	 * Create route list
	 * @param string|null $module
	 * @return ResourceRouteList
	 *
	 * @throws \Drahak\Restful\InvalidStateException
	 */
	public function create($module = NULL)
	{
		$module = $module ? $module : $this->routeConfig['module'];

		$routeList = new ResourceRouteList($module);
		foreach ($this->loader->getIndexedClasses() as $class => $file) {
			if(!method_exists($class, "getReflection")) continue;
			/** @var ClassType $classReflection */
			$classReflection = $class::getReflection();
			$presenter = str_replace('Presenter', '', $classReflection->getShortName());
			$methods = $classReflection->getMethods();

			// Fetch routes data
			$routeData = array();
			foreach ($methods as $method) {
				if (!Strings::contains($method->getName(), 'action'))
					continue;

				$annotations = $this->routeAnnotation->parse($method);
				foreach ($annotations as $requestMethod => $mask) {
					$action = str_replace('action', '', $method->getName());
					$action = Strings::lower(Strings::substring($action, 0, 1)) . Strings::substring($action, 1);

					$pattern = $this->routeConfig['prefix'] ?
						$this->routeConfig['prefix'] . '/' .  $mask :
						$mask;

					$routeData[$pattern][$requestMethod] = $action;
				}
			}

			// Create joined Resource routes form routes data
			foreach ($routeData as $mask => $dictionary) {
				$routeList[] = new ResourceRoute($mask, array(
					'presenter' => $presenter,
					'action' => $dictionary
				), IResourceRouter::RESTFUL);
			}
		}
		return $routeList;
	}

}