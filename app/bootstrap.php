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
$configurator->setDebugMode(FALSE);
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
$container = $configurator->createContainer();

// Setup router
$container->router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY);
$container->router[] = new Route('update/galaxyplugin.php', 'Update:galaxyplugin', Route::ONE_WAY); // URL for galaxyplugin
$container->router[] = new Route('information/systems/<galaxy=1>/<system=1>', "Information:systems");
$container->router[] = new Route('information/reportarchive/<id_planet>/<moon=0>', "Information:reportArchive");
$container->router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');


// Configure and run the application!
$container->application->run();
