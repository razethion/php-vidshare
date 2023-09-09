<?php

use JetBrains\PhpStorm\NoReturn;

session_start();

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");

header('Access-Control-Allow-Origin: ' . SITE_DOMAIN);
header('Access-Control-Allow-Methods: POST');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    #Sanitize post values
    array_walk_recursive($_POST, function (&$value) {
        $value = htmlentities($value, ENT_QUOTES);
    });

    if (!(isset($_POST['filehash']) && isset($_POST['username']) && isset($_POST['state']))) {
        header("HTTP/1.1 422 Unprocessable Entity");
        die();
    }

    #Leave page if not logged-in user
    if (!isset($_SESSION['username'])) {
        header("HTTP/1.1 401 Unauthorized");
        die();
    }

    #Leave page if session doesn't match request
    if ($_SESSION['username'] != $_POST['username']) {
        header("HTTP/1.1 403 Forbidden");
        die();
    }

    #Make sure filehash exists
    $videodata = getVideoDataFilehash($link, $_POST['filehash']);
    if (!(isset($videodata['filehash']))) {
        header("HTTP/1.1 422 Unprocessable Entity");
        die();
    }

    if ($_POST['state'] == 'active') {
        //Add the entry as a like
        $sql = "INSERT IGNORE INTO videolikes (filehash,username) VALUES (?,?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ss", $_POST['filehash'], $_POST['username']);

            // Attempt to execute the prepared statement
            if (!mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            }

            // Close statement
            mysqli_stmt_close($stmt);
            return true;
        }
    } elseif ($_POST['state'] == 'inactive') {
        //Delete the entry as a like
        $sql = "DELETE FROM videolikes WHERE filehash = ? AND username = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ss", $_POST['filehash'], $_POST['username']);

            // Attempt to execute the prepared statement
            if (!mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            }

            // Close statement
            mysqli_stmt_close($stmt);
            return true;
        }
    } else {
        header("HTTP/1.1 422 Unprocessable Entity");
        die();
    }

}

header("HTTP/1.1 405 Method Not Allowed");
die();
