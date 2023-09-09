<?php
header('Cache-Control: no-cache'); // recommended to prevent caching of event data.

use JetBrains\PhpStorm\NoReturn;

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");

disable_ob();

function mysqlArrayResp($link, string $sql): array|string
{
    if ($stmt = mysqli_prepare($link, $sql)) {

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            $videos = array(); // create a variable to hold the information
            while (($row = mysqli_fetch_assoc($result))) {
                $videos[] = $row; // add the row in to the results (data) array
            }

        } else {
            return "Something went wrong. Please try again later.";
        }
        // Close statement
        mysqli_stmt_close($stmt);
    }

    return $videos ?? array();

}

function mysqlQueryAction($link, string $sql): bool|string
{
    if ($stmt = mysqli_prepare($link, $sql)) {

        // Attempt to execute the prepared statement
        if (!mysqli_stmt_execute($stmt)) {
            return "Something went wrong. Please try again later.";
        }
        // Close statement
        mysqli_stmt_close($stmt);
    }

    return true;
}


//Get a video
$videos = mysqlArrayResp($link, "SELECT * FROM old_videos WHERE processing = 0 ORDER BY video_id LIMIT 10");

//Loop through the videos we got
foreach ($videos as $video) {

    mysqlQueryAction($link, "UPDATE old_videos SET processing = 1 WHERE video_id =" . $video['video_id']);

    $username = $video['username'];
    $video_title = htmlentities($video['video_title'], ENT_COMPAT);
    $video_desc = htmlentities(preg_replace("/\n/", "", nl2br(html_entity_decode($video['video_description']))), ENT_QUOTES);
    $video_file_name = $video['video_flv_name'];
    $video_path = "/mnt/s3/admin/old/";
    $video_filehash = hash_file('md5', $video_path . $video_file_name);
    $video_hashname = $video_filehash . ".mp4";
    $video_views = $video['video_view_number'];
    $video_date = $video['video_add_date'];

    echo "<span>" . $video_title . "</span><br>";
    echo "<span>" . $video_desc . "</span><br>";
    echo "<span>" . $video_file_name . "</span><br>";
    echo "<span>" . $video_filehash . "</span><br>";
    echo "<span>" . $video_hashname . "</span><br>";
    echo "<span>" . $video_date . "</span><br>";
    echo "<span>" . $video_views . "</span><br><hr>";

//    make sure file exists
    if (!is_readable($video_path . $video_file_name)) {
        die("Can't access video");
    }

    $user_path = '/mnt/s3/' . $username;
    $temp_path = $user_path . "/processing/";

    #Make user dir if not exist
    if (!is_dir($user_path)) {
        mkdir($user_path);
    }
    #Make user processing dir if not exist
    if (!is_dir($temp_path)) {
        mkdir($temp_path);
    }

    if (copy($video_path . $video_file_name, $temp_path . $video_hashname)) {

        $inserr = 0;

        do {

//        Insert into uploads
            $sql = "INSERT INTO uploads (username,video_title,filehash,video_desc,upload_date) VALUES (?, ?, ?, ?, ?)";

            if ($stmt = mysqli_prepare($link, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "sssss", $username, $video_title, $video_filehash, $video_desc, $video_date);

                // Attempt to execute the prepared statement
                if (!mysqli_stmt_execute($stmt)) {
                    $inserr = 1;
                    break;
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }

//            Create first view
            $sql = "INSERT INTO videoviews (filehash, username, lastview, views) VALUES (?,?,now(),?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "sss", $video_filehash, $username, $video_views);

                // Attempt to execute the prepared statement
                if (!mysqli_stmt_execute($stmt)) {
                    $inserr = 1;
                    break;
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }

            break;

        } while ($inserr == 0);

        if ($inserr == 1) {
            die("Insert error");
        } else {
            $resp = sendcURL(RABBIT_NOTIFY_URL . "?" . $video_filehash . "," . $username . ",mp4");
        }

        //mark the video as queued
        mysqlQueryAction($link, "UPDATE old_videos SET processed = 1 WHERE video_id =" . $video['video_id']);

    } else {
        die("Could not move file");
    }

}