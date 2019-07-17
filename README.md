# RushFiles' API PHP Client Library #

## Requirements

* PHP 5.6+

## Installation

The RushFiles' API PHP Client can be installed using composer.

Add the following repository details to `composer.json` file:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/tsukaeru/rushfiles-api-client"
    }
]
```

and then require the package:

```
composer require tsukaeru/rushfiles-api-client
```

## Configuration

API calls are done through the instance of `Tsukaeru\RushFiles\API\Client` which instead uses a Guzzle library for HTTP client. Default Guzzle client object is created automatically, but you can also pass your own instance for further customization.

```php
require_once "vendor/autoload.php";

use Tsukaeru\RushFiles\API\Client;

$client = new Client();

/**
 * Optionally setting own guzzle instance
 */
$httpClient = new GuzzleHttp\Client([
    'verify' => false // e.g. turn off SSL certificates verification
]);

$client->setHttpClient($httpClient);
```

By default, `"tsukaeru/rushfiles-api-client@v0.1.0"` is used as a device name when connecting to Tsukaeru\RushFiles API. You can set your own name using `setDeviceName` method:

```php
$client->setDeviceName("MyLibrary");
```

## Usage

Library can be used to interact with RushFiles API either through `Tsukaeru\RushFiles\API\Client` object itself or provided object-oriented abstraction.

### Using `Tsukaeru\RushFiles\API\Client` directly

For example, the following code gets information on all shares that user has access to using client's methods directly.

```php
$username = "admin@example.com";
$password = "qwerty";

// get the main domain (RushFiles instance) where user is registered
$domain = $client->GetUserDomain($username);

/**
 * register device
 *   For every user, current host/device must be registered before interacting
 * with API. Subsequent calls are ignored, but interacting with API without
 * registering the device is undefined.
 */
$client->RegisterDevice($username, $password, $domain);

// get all domains and their tokens that user has shares on
$tokens = $client->GetDomainTokens($username, $password, $domain);

// get all shares from all domain
foreach ($tokens as $token) {
    $shares = $client->GetUserShares($username,
                                     $token['DomainToken'],
                                     $token['DomainUrl']);

    foreach ($shares as $share) {
        print_r($share);
    }
}
```

Exemplary output:

```
Array
(
    [ShareAssociationType] => 2
    [Id] => 02a8d989567e4c4c8085637c7aa24569
    [CompanyId] => 28ac608f-7bc7-4bda-9a30-b17f7a38bf22
    [Name] => jsmith - Home folder
    [ShareDefault] => Array
        (
            [Owner] => admin@example.com
            [ShareCategory] => 2
        )

    [ShareTick] => 46
    [SpaceUsage] => Array
        (
            [DiskUsage] => 3978074
            [HistoryUsage] => 1989040
            [DeletedUsage] => 0
        )

    [ShareType] => 0
    [ShareIds] => Array
        (
            [0] => c1ac0633adb74bd29fa2bf90cb838a1b
        )

    [TimeStamps] => Array
        (
            [CreateTime] => 0
            [CreatedBy] => Admin
            [UpdateTime] => 0
            [UpdatedBy] => Admin
            [DeleteTime] => 0
            [DeletedBy] => Admin
        )

)
...
```
* For all available methods, please see the `Tsukaeru\RushFiles\API\Client` class' [source file](src/API/Client.php)

* For data structures returned and pass to the client, please see the [RushFiles API swagger documentation](https://clientgateway.rushfiles.com/swagger/ui/index#/)

### Using provided abstraction

The following code gets information on all shares that user has access to using abstraction.

```php
/**
 * Login method detects user's domain, registers the device and returns
 * an initialized Tsukaeru\RushFiles\User instance.
 */
$user = $client->Login($username, $password);

/**
 * Retrieve data on all shares as a array of Tsukaeru\RushFiles\VirtualFile\Share
 * objects.
 */
$shares = $user->getShares();
foreach ($shares as $share) {
    echo "\nName:          " . $share->getName();
    echo "\nInternal Name: " . $share->getInternalName();
    echo "\n";
}
```

Exemplary output:

```
Name:          jsmith - Home folder
Internal Name: 02a8d989567e4c4c8085637c7aa24569
```

* For more examples of using the abstraction layer, please refer to the [samples](samples).