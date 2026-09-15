<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/vendor/autoload.php';

use App\Core\Application;

$app = new Application();
$app->run();