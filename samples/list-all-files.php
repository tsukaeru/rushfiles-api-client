<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Tsukaeru\RushFiles\User;
use Tsukaeru\RushFiles\VirtualFile\Directory;

// import $authToken and $client
require_once "generate-token.php";

$user = new User($tokenRecovered, $client);

function parse_dir(Directory $directory, $level = 0)
{
    foreach ($directory->getChildren() as $file) {
        echo str_repeat('  ', $level) . $file->getName() . "\n";
        if ($file->isDirectory()) {
            parse_dir($file, $level + 1);
        }
    }
}

foreach ($user->getShares() as $share) {
    echo $share->getName() . "\n";
    parse_dir($share, 1);
}

/*

Exemplary output:

jsmith - Home folder
  screenshots
    details.PNG
    list.PNG
    page.PNG
    settings page.PNG
  miniature.txt
printer
  documents
  test
    test.rtf
  backuplist.png
  documents - ショートカット.lnk
  test.txt
screenshots
  details.PNG
  list.PNG
  page.PNG
  settings page.PNG

*/