<?php

require __DIR__ . '/../vendor/autoload.php';

session_start();
session_regenerate_id();

// Set settings and instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register routes
require __DIR__ . '/../src/routes.php';

$app->run();
