<?php

require_once __DIR__."/../vendor/autoload.php";

use Cache\Adapter\PHPArray\ArrayCachePool;
use Tsukaeru\RushFiles\API\Client;

$pool = new ArrayCachePool();

$client = new Client();

// change to your own credentials
$username = "admin@example.com";
$password = "qwerty";

for ($i = 0; $i < 2; ++$i)
{
    $start = microtime(true);

    // Login using cache and specifying domain
    $user = $client->Login($username, $password, null, $pool);

    echo "$i: Login function took " . (microtime(true) - $start) . "ms.\n";    
}
