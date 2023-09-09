<?php //This file is called by the upload script before file is uploaded to make sure all database fields are good
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $_POST['videodescription'] = preg_replace("/\\r\\n/", "", nl2br($_POST['videodescription']));
    array_walk_recursive($_POST, function (&$value) {
        $value = htmlentities($value, ENT_QUOTES);
    });
    $video_desc = $_POST['videodescription'];
    $filename = filter_filename($_POST['fileName']);
    $username = $_POST['username'];
    $video_title = $_POST['videoname'];

    $err = '';

    if (empty($_POST['filehash'])) {
        $err .= "Issue hashing file. Is javascript enabled? ";
    } else {
        $hash = $_POST['filehash'];

        #Check if filehash already exists
        $sql = "SELECT filehash FROM uploads WHERE filehash = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $hash);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                /* store result */
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $err .= "This file has already been uploaded. ";
                }
            } else {
                $err .= "Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }

    }

    if (empty($filename)) {
        $err .= "File is invalid. ";
    } elseif ($_POST['filesize'] > 1024 * 1024 * 1024) {
        $err .= "File is too large. ";
    } //TODO a check to make sure the file is actually a video file

    if (empty($_POST['videoname'])) {
        $err .= "Please enter a video title. ";
    } else {
        if (strlen($_POST['videoname']) > 60) {
            $err .= "Video name must not be longer than 60 characters. ";
        }
    }

    if (!empty($err)) {
        print $err;
    } else {

        #parse tags if set, outputs $atags[]
        if (!empty($_POST['tags'])) {

            $seltags = $_POST['tags'];
            $seltags = substr($seltags, 1, -1);

            $exploded = explode(',', $seltags);
            $atags = array();

            foreach ($exploded as $token) {

                if (preg_match('{"value":"(.*)"}', html_entity_decode($token), $resp)) {
                    array_push($atags, htmlentities(mb_strtolower(strval($resp[1]))));
                }

            }
        }

        #Make create user's S3 dir if it doesn't exist yet
        if (!is_dir(S3_LOCAL . $username . '/')) {
            if (!mkdir(S3_LOCAL . $username . '/')) {
                print "Server storage error.";
                header("HTTP/1.1 500 Internal Server Error");
                die();
            }
        }

        #Make create user's S3 processing dir if it doesn't exist yet
        if (!is_dir(S3_LOCAL . $username . '/processing/')) {
            if (mkdir(S3_LOCAL . $username . '/processing/')) {
                print "Server storage error.";
                header("HTTP/1.1 500 Internal Server Error");
                die();
            }
        }

        $path = S3_LOCAL . $username . '/processing/';

        #Video insert
        $sql = "INSERT INTO uploads (username,video_title,filehash,video_desc) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssss", $username, $video_title, $hash, $video_desc);

            // Attempt to execute the prepared statement
            if (!mysqli_stmt_execute($stmt)) {
                $inserr = 1;
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }

        #Tags insert
        if (isset($atags)) {
            foreach ($atags as $tag) {
                $sql = "INSERT IGNORE INTO tags (tag) VALUES (?)";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt, "s", $tag);

                    // Attempt to execute the prepared statement
                    if (!mysqli_stmt_execute($stmt)) {
                        $inserr = 1;
                    }

                    // Close statement
                    mysqli_stmt_close($stmt);
                }
            }

            #Vidtag insert
            foreach ($atags as $tag) {
                $sql = "INSERT IGNORE INTO videotags (filehash,tag) VALUES (?,?)";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt, "ss", $hash, $tag);

                    // Attempt to execute the prepared statement
                    if (!mysqli_stmt_execute($stmt)) {
                        $inserr = 1;
                    }

                    // Close statement
                    mysqli_stmt_close($stmt);
                }
            }
        }

        #View creation
        $sql = "INSERT INTO videoviews (filehash, username, lastview, views) VALUES (?,?,now(),1)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ss", $hash, $username);

            // Attempt to execute the prepared statement
            if (!mysqli_stmt_execute($stmt)) {
                $inserr = 1;
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }

        print "TRUE";
    }

}