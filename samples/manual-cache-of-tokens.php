<?php

use Cache\Adapter\PHPArray\ArrayCachePool;
use Psr\SimpleCache\CacheInterface;
use Tsukaeru\RushFiles\API\Client;
use Tsukaeru\RushFiles\User;

require_once "__DIR__/../vendor/autoload.php";


// change to your own credentials
$username = "admin@example.com";
$password = "qwerty";
$domain = "rushfiles.com";

$pool = new ArrayCachePool();

// set old, incorrect cache
$pool->set("rf_api_client." . str_replace('@', '_', $username) . ".tokens", [
    [
        "DomainUrl" => "rushfiles.com",
        "DomainToken" => "token"
    ]
]);

function MyLogin($username, $password, $domain, CacheInterface $cache)
{
    $client = new Client();

    $cacheKey = "rf_api_client." . str_replace('@', '_', $username) . ".tokens";
    $user = new User($username, $cache->get($cacheKey), $client);

    try {
        $user->getShares(); // call a method that needs to communicate with RF server
    } catch (Exception $e) {
        if ($e->getCode() !== 401) {
            throw $e;
        }

        $tokens = $client->GetDomainTokens($username, $password, $domain);
        $cache->set($cacheKey, $tokens);

        $user = new User($username, $tokens, $client);
    }

    return $user;
}

$start = microtime(true);
$user = MyLogin($username, $password, $domain, $pool);
echo "Cache miss login time: " . (microtime(true) - $start) . "ms.\n";

$start = microtime(true);
$user = MyLogin($username, $password, $domain, $pool);
echo "Cache hit login time: " . (microtime(true) - $start) . "ms.\n";
