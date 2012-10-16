<?php

// uncomment this line if you must temporarily take down your site for maintenance
// require '.maintenance.php';

// absolute filesystem path to this web root
define('WWW_DIR', __DIR__);

// absolute filesystem path to the application root
define('APP_DIR',  '../app');

// absolute filesystem path to the libraries
define('LIBS_DIR',  '../libs');
// you can use this value to have multiple configurations
define('CONF_DIRNAME', 'config');
// load bootstrap file
require APP_DIR . '/bootstrap.php';
