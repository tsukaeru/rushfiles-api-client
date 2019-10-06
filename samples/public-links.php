<?php

require_once "__DIR__/../vendor/autoload.php";

use Tsukaeru\RushFiles\API\Client;
use Tsukaeru\RushFiles\PublicLink;

// import $username, $password and $domain
require_once "_auth_params.php";

$client = new Client();

$user = $client->Login($username, $password);

$share = reset($user->getShares());
$dir = reset($share->getDirectories());
$file = reset($dir->getFiles());

/**
 * public links can be created for the directories in the same way
 */

// Create a default public link but return only URL
// - no password protection
// - unlimited uses
// - valid for 4 weeks
$linkStr = $file->createPublicLink(PublicLink::STRING);
echo "Default new Public Link: " . $linkStr . "\n";

function public_link_properties(PublicLink $link)
{
    echo "\nId:                  " . $link->getId();
    echo "\nURL:                 " . $link->getFullLink();
    echo "\nShare Id:            " . $link->getShareId();
    echo "\nIs File:             " . var_export($link->isFile(), true);
    echo "\nVirtual File Id:     " . $link->getVirtualFileId();
    echo "\nIs Password Enabled: " . var_export($link->isPasswordEnabled(), true);
    echo "\nMessage:             " . $link->getMessage();
    echo "\n";
}

// create public link passing additional properties
$linkObj = $file->createPublicLink([
    'Password' => 'qwerty',
    'DaysToExpire' => 3,
    'MaxUse' => 3,
    'Message' => 'test public link'
]);

echo "\nLink created with additional properties and returned as object:";
public_link_properties($linkObj);

echo "\n-----------------------------------------------\n";
echo "\nList of all public links for the file:\n";
foreach ($file->getPublicLinks() as $link) {
    public_link_properties($link);
}

/*

Default new Public Link: https://tsukaeru.cloudfile.jp/client/publiclink.aspx?id=NiB0i6QIQX

Link created with additional properties and returned as object:
Id:                  xKUCLhdBUP
URL:                 https://cloudfile.jp/client/publiclink.aspx?id=xKUCLhdBUP
Share Id:            02a8d989567e4c4c8085637c7aa24569
Is File:             true
Virtual File Id:     5b44423401a1442ab5ca0ba447163026
Is Password Enabled: true
Message:             test public link

-----------------------------------------------

List of all public links created for the file:

Id:                  NiB0i6QIQX
URL:                 https://cloudfile.jp/client/publiclink.aspx?id=NiB0i6QIQX
Share Id:            02a8d989567e4c4c8085637c7aa24569
Is File:             true
Virtual File Id:     5b44423401a1442ab5ca0ba447163026
Is Password Enabled: false
Message:             

Id:                  xKUCLhdBUP
URL:                 https://cloudfile.jp/client/publiclink.aspx?id=xKUCLhdBUP
Share Id:            02a8d989567e4c4c8085637c7aa24569
Is File:             true
Virtual File Id:     5b44423401a1442ab5ca0ba447163026
Is Password Enabled: true
Message:             test public link

*/