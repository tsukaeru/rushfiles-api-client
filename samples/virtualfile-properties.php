<?php

require_once "__DIR__/../vendor/autoload.php";

use RushFiles\API\Client;
use RushFiles\VirtualFile;
use RushFiles\VirtualFile\File;

// change to your own credentials
$username = "admin@example.com";
$password = "qwerty";

$client = new Client();

$user = $client->Login($username, $password);

$share = reset($user->getShares());
$dir = reset($share->getDirectories());
$file = reset($dir->getFiles());

function show_properties(VirtualFile $file)
{
    echo "\nObject:        " . get_class($file);
    echo "\nName:          " . $file->getName();
    echo "\nInternal Name: " . $file->getInternalName();
    echo "\nShare Id:      " . $file->getShareId();
    echo "\nIs Directory:  " . var_export($file->isDirectory(), true);
    echo "\nIs File:       " . var_export($file->isFile(), true);
    echo "\nSize:          " . $file->getSize();
    echo "\nTick:          " . $file->getTick();
    if ($file instanceof File) {
        echo "\nUpload Name:   " . $file->getUploadName();
    }
    echo "\n";
}

show_properties($share);
show_properties($dir);
show_properties($file);

/*

Exemplary output:

Object:        RushFiles\VirtualFile\Share
Name:          jsmith - Home folder
Internal Name: 02a8d989567e4c4c8085637c7aa24569
Share Id:      02a8d989567e4c4c8085637c7aa24569
Is Directory:  true
Is File:       false
Size:          3
Tick:          46

Object:        RushFiles\VirtualFile\Directory
Name:          screenshots
Internal Name: 12793f8cc7474c168c4f98c8b1bfd725
Share Id:      02a8d989567e4c4c8085637c7aa24569
Is Directory:  true
Is File:       false
Size:          6
Tick:          1

Object:        RushFiles\VirtualFile\File
Name:          details.PNG
Internal Name: 5fcabe41261d4d29aa494647f04d22d5
Share Id:      02a8d989567e4c4c8085637c7aa24569
Is Directory:  false
Is File:       true
Size:          53765
Tick:          1
Upload Name:   a2ec79aa9e4449fe9dc2a2fb82fb726c

*/