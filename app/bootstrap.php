<?php

/**
 * My Application bootstrap file.
 */
use Nette\Application\Routers\Route;


// Load Nette Framework
require LIBS_DIR . '/Nette/loader.php';

// Configure application
$configurator = new Nette\Config\Configurator;

// Enable Nette Debugger for error visualisation & logging
$configurator->setDebugMode(TRUE);
$configurator->enableDebugger( '../log');

// Enable RobotLoader - this will load all classes automatically
$configurator->setTempDirectory('../temp');
$configurator->createRobotLoader()
	->addDirectory(APP_DIR)
	->addDirectory(LIBS_DIR)
	->register();

// Create Dependency Injection container from config.neon file
$configurator->addConfig(APP_DIR . '/'.CONF_DIRNAME.'/AppConf.neon');
$configurator->addConfig(APP_DIR . '/'.CONF_DIRNAME.'/ServerConf.neon');
Drahak\Restful\DI\Extension::install($configurator);
$container = $configurator->createContainer();

// Setup router
$container->router[] = new Route('index.php', 'Front:Homepage:default', Route::ONE_WAY);
$container->router[] = new Route('update/galaxyplugin.php', 'Api:Update:galaxyplugin', Route::ONE_WAY); // URL for galaxyplugin
$container->router[] = new Route('information/systems/<galaxy=1>/<system=1>', "Front:Information:systems");
$container->router[] = new Route('information/reportarchive/<id_planet>/<moon=0>', "Front:Information:reportArchive");
//$container->router[] = new Route('<presenter>/<action>[/<id>]', 'Front:Homepage:default');
$container->router[] = new Route('<presenter>/<action>[/<id>]', array(
	"presenter" => array(
	Route::VALUE => "Front:Homepage",
	Route::FILTER_IN => function ($p){return "Front:".$p;},
	Route::FILTER_OUT => function ($p){return str_replace("Front:", "", $p);}
	),
	"action" => "default",
	"id" => NULL
));



// Configure and run the application!
$container->application->run();
