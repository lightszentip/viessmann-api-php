<?php

use Lightszentip\Viessmannapi\Api\DataApi;
use Lightszentip\Viessmannapi\Connection\Login;

include_once __DIR__ . './../vendor/autoload.php';

$test = new Login("/resources");
$api = new DataApi($test);
$api->readUserData();
$api->getDevices();
$api->getDeviceFeatures();
$api->getGatewayFeatures();
$api->getEvents();