<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Tsukaeru\RushFiles\API\AuthToken;
use Tsukaeru\RushFiles\API\Client;
use Tsukaeru\RushFiles\API\Exceptions\AuthorizationFailed;

// import $username, $password, etc
require_once "_auth_params.php";

$client = new Client([
    'clientId' => $clientId,
    'clientSecret' => $clientSecret,
    'redirectUrl' => $redirectUrl,
    'authority' => $authority,
]);

$tokenFileName = dirname(__FILE__) . "/{$clientId}_token.json";

if (file_exists($tokenFileName)) {
    $authToken = new AuthToken(json_decode(file_get_contents($tokenFileName), true));
    if (!$authToken->isValid()) {
        $authToken = $client->GetTokenThroughRefreshToken($authToken->getRefreshToken());
        
        file_put_contents($tokenFileName, json_encode($authToken->toArray()));
    }
} else {
    try {
        if ($grant == "password") {
            $authToken = $client->GetTokenThroughResourceOwnerPasswordCredentials($username, $password);
        } else {
            echo "Access the following url in the browser and enter returned code\n";
            echo $client->GetAuthorizationCodeUrl() . "\n";
            $code = readline("OAuth authorization code: ");
            $authToken = $client->GetTokenThroughAuthorizationCode($code);
        }
    } catch (AuthorizationFailed $e) {
        echo $e->getMessage();
        exit;
    }

    file_put_contents($tokenFileName, json_encode($authToken->toArray()));
}

if (strpos($_SERVER["PHP_SELF"], basename(__FILE__)) !== false) {
    echo "Auth token object:\n";
    echo "getAccessToken(): " . $authToken->getAccessToken() . "\n";
    echo "getUsername():    " . $authToken->getUsername() . "\n";
    echo "getDomains():     " . print_r($authToken->getDomains(), true) . "\n";
    echo "isValid():        " . $authToken->isValid() . "\n";
    echo "isRefreshable():  " . $authToken->isRefreshable() . "\n";
    echo "__toString():     " . (string)$authToken . "\n";
}
