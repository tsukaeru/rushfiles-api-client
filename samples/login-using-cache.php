<?php

require_once __DIR__."/../vendor/autoload.php";

use Cache\Adapter\PHPArray\ArrayCachePool;
use Tsukaeru\RushFiles\API\Client;

// import $username, $password and $domain
require_once "_auth_params.php";

$pool = new ArrayCachePool();

$client = new Client();

for ($i = 0; $i < 2; ++$i)
{
    $start = microtime(true);

    // Login using cache and specifying domain
    $user = $client->Login($username, $password, null, $pool);

    echo "$i: Login function took " . (microtime(true) - $start) . "ms.\n";    
}
