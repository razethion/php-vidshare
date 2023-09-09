<?php

use JetBrains\PhpStorm\NoReturn;

#[NoReturn] function leavePage()
{
    header("Location: /");
    die();
}

#parse and clean URI
$pieces = preg_split('-/-', $_SERVER['REQUEST_URI'], NULL, PREG_SPLIT_NO_EMPTY);
$username = $pieces[1];

$username = filter_var($username, FILTER_SANITIZE_STRING);

#Skip user search if none provided
if ($username == "") {
    leavePage();
}

#limit to 16 char username
$username = substr($username, null, 16);
if (strlen($username) < 4) {
    leavePage();
}

#check if user exists
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
$sql = "SELECT username FROM users WHERE username = ?";

if ($stmt = mysqli_prepare($link, $sql)) {
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "s", $username);

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        mysqli_stmt_store_result($stmt);

        if (!mysqli_stmt_num_rows($stmt) == 1) {
            leavePage();
        }
    } else {
        echo "Something went wrong. Please try again later.";
    }

    // Close statement
    mysqli_stmt_close($stmt);
}

#Parse subpage if set
if (isset($pieces[2])) {
    $subpage = $pieces[2];
    if ($subpage == "uploads") {
        /** @noinspection PhpIncludeInspection */
        require_once($_SERVER['DOCUMENT_ROOT'] . "/u/uploads/index.php");
    } elseif ($subpage == "settings") {
        require_once($_SERVER['DOCUMENT_ROOT'] . "/u/settings/index.php");
    } elseif ($subpage == "likes") {
        require_once($_SERVER['DOCUMENT_ROOT'] . "/u/likes/index.php");
    } elseif (is_numeric($subpage)) {
        if (isset($pieces[3]) && $pieces[3] == "edit") {
            require_once($_SERVER['DOCUMENT_ROOT'] . "/u/play/edit.php");
        } elseif (isset($pieces[3]) && $pieces[3] == "tagedit") {
            require_once($_SERVER['DOCUMENT_ROOT'] . "/u/play/tagedit/index.php");
        } else {
            require_once($_SERVER['DOCUMENT_ROOT'] . "/u/play/index.php");
        }
    } else {
        require_once($_SERVER['DOCUMENT_ROOT'] . "/u/page.php");
    }
} else {
    require_once($_SERVER['DOCUMENT_ROOT'] . "/u/page.php");
}