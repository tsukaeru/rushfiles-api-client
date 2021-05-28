<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Tsukaeru\RushFiles\User;

// import $authToken and $client
require_once "generate-token.php";

$path = "test.txt";

// create file to upload
file_put_contents($path, "contents");

$user = new User($authToken, $client);

$shares = $user->getShares();
$share = array_pop($shares);

// upload to directory works the same way
$file = $share->uploadFile($path);

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

// clean up
unlink($path);

/*

Exemplary output:

Object:        Tsukaeru\RushFiles\VirtualFile\File
Name:          test (duplicate 05-10-2019 04.06.05).txt
Internal Name: 62043508-e725-11e9-92f3-021596edacda
Share Id:      7b41e134-b558-41b6-8f8d-1bd8214bac14
Is Directory:  false
Is File:       true
Size:          8
Tick:          1

*/