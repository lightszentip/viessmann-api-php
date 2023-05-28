# viessmann-api-php

# How to

## composer

```shell
composer require lightszentip/viessmannapi
```

## create credentials properties

```properties
user = ""
pwd = ""
installationId = ""
gatewayId = ""
client_id = ""
callback_uri = ""
```

## Start the script

in resources need to exist the credentials.properties

```php
use Lightszentip\Viessmannapi\Api\DataApi;
use Lightszentip\Viessmannapi\Connection\Login;

include_once __DIR__ . '/vendor/autoload.php';

$test = new Login("/resources");
$api = new DataApi($test);
$api->readUserData();
$api->getDevices();
$api->getDeviceFeatures();
$api->getGatewayFeatures();
$api->getEvents();
```