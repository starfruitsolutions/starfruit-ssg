<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Slim\Factory\AppFactory;


require __DIR__ . '/vendor/autoload.php';

require __DIR__ . '/app/container.php';

$app = AppFactory::create();

require __DIR__ . '/app/routes.php';

$app->run();
