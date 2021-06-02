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

API calls are done through the instance of `Tsukaeru\RushFiles\API\Client`. It can be configured using setters or by passing configuration array to constructor.

Configuration array in constructor:

```php
require_once "vendor/autoload.php";

use Tsukaeru\RushFiles\API\Client;

$httpClient = new GuzzleHttp\Client([
    'verify' => false // e.g. turn off SSL certificates verification
]);

$client = new Client([
    'clientId' => 'ClientID',
    'clientSecret' => 'ClientSecret',
    'deviceName' => 'MyLibrary',
    'httpClient' => $httpClient,
]);
```

Using setters:

```php
require_once "vendor/autoload.php";

use Tsukaeru\RushFiles\API\Client;

$client = new Client();

$httpClient = new GuzzleHttp\Client([
    'verify' => false // e.g. turn off SSL certificates verification
]);

$client->setHttpClient($httpClient);
$client->setDeviceName("MyLibrary");
$client->setClientId("ClientID");
$client->setClientSecret("ClientSecret");
```

Defaults are:
```php
[
    'httpClient' => new GuzzleHttp\Client,
    'authority' => 'https://auth.rushfiles.com',
    'deviceName' => 'tsukaeru/rushfiles-api-client@v0.3.0',
    'deviceOS' => php_uname('s') . ' ' . php_uname(''),
    'deviceType' => Client::DEVICE_UNKNOWN,
    'clientId' => '',
    'clientSecret' => '',
    'redirectUrl' => '',
]
```

## Authentication

Library supports authentication with OAuth Authorization Code and Resource Owner Password Credentials flows.

Refer to RushFiles api documentation for details and differences between the flows: https://wiki.rushfiles.com/api/authorization

### Authorization Code flow

For Authorization Code flow, configure `clientId`, `clientSecret` and `redirectUrl` of the client. Redirect user to the URL returned from `GetAuthorizationCodeUrl()` and pass retuned authorization code to `GetTokenThroughAuthorizationCode()`.

```php
$client = new Client([
    'clientId' => 'ClientID',
    'clientSecret' => 'ClientSecret',
    'redirectUrl' => 'https://exmaple.com/auth',
]);

$authUrl = $client->GetAuthorizationCodeUrl();
// redirect user to $auth Url

// ...

// read authorization code from url parameter after user logs in to RushFiles and is redirected back to your website
$code = // ...
$authToken = $client->GetTokenThroughAuthorizationCode($code);
```

### Resource Owner Password Credentials flow

For Resource Owner Password Credentials flow, configure `clientId` and `clientSecret` of the client. Then call `GetTokenThroughResourceOwnerPasswordCredentials()` with username (email) and password.

```php
$client = new Client([
    'clientId' => 'ClientID',
    'clientSecret' => 'ClientSecret',
]);

$authToken = $client->GetTokenThroughResourceOwnerPasswordCredentials('admin@example.com', 'password');
```

### Refreshing token

A new access token can be requested with refresh token.

```php
$client = new Client([
    'clientId' => 'ClientID',
    'clientSecret' => 'ClientSecret',
]);

$oldAuthToken = // ...

$newAuthToken = $client->GetTokenThroughRefreshToken($oldAuthToken->getRefreshToken());
```

## Usage

Library can be used to interact with RushFiles API either through `Tsukaeru\RushFiles\API\Client` object itself or provided object-oriented abstraction.

### Using `Tsukaeru\RushFiles\API\Client` directly

For example, the following code gets information on all shares that user has access to using client's methods directly.

```php
$authToken = // ...

// get all shares from all domain accessible using the token
foreach ($domain as $authToken->getDomains()) {
    $shares = $client->GetUserShares($authToken->getUsername(),
                                     $authToken,
                                     $domain);

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
$client = new Client([
    // ...
]);
$authToken = // ...
/**
 * Create user instance from AuthToken
 */
$user = new User($authToken, $client);

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