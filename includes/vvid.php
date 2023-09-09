<?php

use JetBrains\PhpStorm\NoReturn;

session_start();

#[NoReturn] function leavePage(): void
{
    header("Location: /");
    die();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");

$url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
$url_components = array();
$url_components = parse_url($url);

if (empty($url_components['query'])) {
    leavePage();
}

$decrypted = openssl_decrypt($url_components['query'], "AES-128-CTR", "T0yP1c$", null, "1234567891011122");

#make sure filehash exists
$videodata = getVideoDataFilehash($link, $decrypted);

if (!isset($videodata) || $videodata['filehash'] != $decrypted) {
    leavePage();
}

if (isset($_SESSION['username'])) {
    $sql = "SELECT filehash FROM videoviews WHERE filehash = ? AND username = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $decrypted, $_SESSION['username']);
        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                #Check if we should update
                $sql = "SELECT lastview,views FROM videoviews WHERE filehash = ? AND username = ?";

                if ($stmt2 = mysqli_prepare($link, $sql)) {
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt2, "ss", $decrypted, $_SESSION['username']);

                    if (mysqli_stmt_execute($stmt2)) {
                        #store result
                        $result = mysqli_stmt_get_result($stmt2);

                        $resp = mysqli_fetch_assoc($result);

                        if (time() - strtotime($resp['lastview']) >= 1800) {
                            #Log another view
                            $newviewcount = $resp['views'] + 1;
                            $sql = "update videoviews SET
                        lastview = now(),
                        views = " . $newviewcount . " WHERE filehash = ? AND username = ?";

                            if ($stmt3 = mysqli_prepare($link, $sql)) {
                                // Bind variables to the prepared statement as parameters
                                mysqli_stmt_bind_param($stmt3, "ss", $decrypted, $_SESSION['username']);

                                // Attempt to execute the prepared statement
                                if (!mysqli_stmt_execute($stmt3)) {
                                    echo "Something went wrong. Please try again later.";
                                }

                                // Close statement
                                mysqli_stmt_close($stmt3);

                            }
                        }

                    } else {
                        echo "Something went wrong. Please try again later.";
                    }

                    // Close statement
                    mysqli_stmt_close($stmt2);

                }
            } else {
                #No entry exists, make one
                $sql = "INSERT into videoviews SET
                        filehash = ?,
                        username = ?,
                        lastview = now(),
                        views = '1'
                    ";

                if ($stmt2 = mysqli_prepare($link, $sql)) {
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt2, "ss", $decrypted, $_SESSION['username']);

                    // Attempt to execute the prepared statement
                    if (!mysqli_stmt_execute($stmt2)) {
                        echo "Something went wrong. Please try again later.";
                    }

                    // Close statement
                    mysqli_stmt_close($stmt2);

                }
            }
        }
        // Close statement
        mysqli_stmt_close($stmt);
    }
} else {
    #User is not logged-in, lets try to track a view anyway
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $guestip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $guestip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $guestip = $_SERVER['REMOTE_ADDR'];
    }

    $guestip = "g-" . htmlspecialchars($guestip);

    #get view log for file & user
    $sql = "SELECT filehash FROM videoviews WHERE filehash = ? AND username = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $decrypted, $guestip);
        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                #Check if we should update
                $sql = "SELECT lastview,views FROM videoviews WHERE filehash = ? AND username = ?";

                if ($stmt2 = mysqli_prepare($link, $sql)) {
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt2, "ss", $decrypted, $guestip);

                    if (mysqli_stmt_execute($stmt2)) {
                        # store result
                        $result = mysqli_stmt_get_result($stmt2);

                        $resp = mysqli_fetch_assoc($result);

                        if (time() - strtotime($resp['lastview']) >= 1800) {
                            #Log another view
                            $newviewcount = $resp['views'] + 1;
                            $sql = "update videoviews SET
                        lastview = now(),
                        views = " . $newviewcount . " WHERE filehash = ? AND username = ?";

                            if ($stmt3 = mysqli_prepare($link, $sql)) {
                                // Bind variables to the prepared statement as parameters
                                mysqli_stmt_bind_param($stmt3, "ss", $decrypted, $guestip);

                                // Attempt to execute the prepared statement
                                if (!mysqli_stmt_execute($stmt3)) {
                                    echo "Something went wrong. Please try again later.";
                                }

                                // Close statement
                                mysqli_stmt_close($stmt3);

                            }
                        }

                    } else {
                        echo "Something went wrong. Please try again later.";
                    }

                    // Close statement
                    mysqli_stmt_close($stmt2);

                }
            } else {
                #No entry exists, make one
                $sql = "INSERT into videoviews SET
                        filehash = ?,
                        username = ?,
                        lastview = now(),
                        views = '1'
                    ";

                if ($stmt2 = mysqli_prepare($link, $sql)) {
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt2, "ss", $decrypted, $guestip);

                    // Attempt to execute the prepared statement
                    if (!mysqli_stmt_execute($stmt2)) {
                        echo "Something went wrong. Please try again later.";
                    }

                    // Close statement
                    mysqli_stmt_close($stmt2);

                }
            }
        }
        // Close statement
        mysqli_stmt_close($stmt);
    }

}

leavePage();