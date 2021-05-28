<?php

require_once __DIR__ . "/../vendor/autoload.php";

// import $authToken and $client
require_once "generate-token.php";

echo "Previous access token:\n$authToken\n";

$authToken = $client->GetTokenThroughRefreshToken($authToken->getRefreshToken());

echo "New access token:\n$authToken\n";
