<?php

use JetBrains\PhpStorm\NoReturn;

$username = filter_var($username, FILTER_SANITIZE_STRING);

session_start();

#[NoReturn] function kickToProfile($username)
{
    header("Location: /u/" . $username . "/settings/profile");
    die();
}

#Leave page if not logged-in user
if (!($_SESSION['username'] == $username)) {
    leavePage();
}

#redirect if not accessing from /profile
if (!isset($pieces[3])) {
    kickToProfile($username);
}

#Parse subpage if set
if (isset($pieces[3])) {
    $setpage = $pieces[3];
    if ($setpage == "profile") {
        $chosenpagehtml = $_SERVER['DOCUMENT_ROOT'] . "/u/settings/profileset.php";
    } elseif ($setpage == "uploads") {
        $chosenpagehtml = $_SERVER['DOCUMENT_ROOT'] . "/u/settings/uploadset.php";
    } elseif ($setpage == "deleteaccount") {
        $chosenpagehtml = $_SERVER['DOCUMENT_ROOT'] . "/u/settings/deleteaccount.php";
    } else {
        kickToProfile($username);
    }
} else {
    echo "Couldn't parse url";
    die();
}

#load page
require_once($_SERVER['DOCUMENT_ROOT'] . "/u/settings/base.php");