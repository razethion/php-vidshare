<?php
session_start();

use JetBrains\PhpStorm\NoReturn;

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");
disable_ob();

#[NoReturn] function leavePage(): void
{
    header("Location: /");
    die();
}

#Ensure user is logged in first
if (!(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true)) {
    header("location: /login");
    die;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    #Sanitize post values
    array_walk_recursive($_POST, function (&$value) {
        $value = htmlentities($value, ENT_QUOTES);
    });

    $username = $_SESSION["username"];

    if (!is_dir(CHUNK_DIR . $username)) {
        if (!mkdir(CHUNK_DIR . $username)) {
            die("Could not make user chunkdir");
        }
    }

    $index = $_POST['index'];
    $totalChunks = $_POST['totalChunks'];
    $chunk = $_FILES['chunk'];
    $tempPath = $chunk['tmp_name'];
    $name = filter_filename($_POST['fileName']);
    $position = strpos($name, ".");
    $fileextension = substr($name, $position + 1);
    $fileextension = strtolower($fileextension);
    $hash = $_POST['filehash'];
    $hashedname = $hash . "." . $fileextension;
    $targetPath = CHUNK_DIR . $username . '/' . $hashedname . '_part' . $index;
    $s3target = S3_LOCAL . $username . '/processing/' . $hashedname;

    move_uploaded_file($tempPath, $targetPath);

    $allChunksUploaded = true;
    for ($i = 0; $i < $totalChunks; $i++) {
        if (!is_readable(CHUNK_DIR . $username . '/' . $hashedname . '_part' . $i)) {
            $allChunksUploaded = false;
            break;
        }
    }

    if ($allChunksUploaded) {
        $finalFile = fopen($s3target, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $filePart = file_get_contents(CHUNK_DIR . $username . '/' . $hashedname . '_part' . $i);
            fwrite($finalFile, $filePart);
            unlink(CHUNK_DIR . $username . '/' . $hashedname . '_part' . $i); // Delete the chunk
        }

        fclose($finalFile);

    }
} else {
    print "Awaiting more chunks";
}