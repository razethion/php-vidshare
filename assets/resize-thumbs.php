<?php
/*
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");

$filehashes = array (

    "7f0a06ebb2f3e55b948273362a52da11",
    "cff8ed4f4952ee5fd8d2b437e33acfc9",
    "cff8ed4f4952ee5fd8d2b437e33acfc9",
    "f9469aa3ce3baf9c609b15d5092ef8c7",
    "aa3bbb8f09308a8a8fc05d9460be3e8f",
    "212cb606340e251699541c182cbc4e43",
    "839b7d2a85f57c702a59c775e7f50895"

);

foreach ($filehashes as $filehash) {

    echo PHP_EOL . "running " . $filehash;

    $videodata = getVideoDataFilehash($link, $filehash);
    $username = $videodata['username'];

    $pfprenamed = $filehash . ".jpg";
    $pfprenamedold = $filehash . ".jpg.old";
    $filepath = S3_LOCAL . $username . "/" . $pfprenamed;
    $filepathold = S3_LOCAL . $username . "/" . $pfprenamedold;

    copy($filepath, $filepathold);

    processThumbnail($filepath, $filepath);

}
*/