<?php

use JetBrains\PhpStorm\NoReturn;

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/func-filenames.php");

#[NoReturn] function queryFailed(): void
{
    print "There was an issue with a query";
    die();
}

function sendcURL($url): string
{
    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL session and get the response
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo 'error: ' . curl_error($ch);
    }

    // Close cURL session
    curl_close($ch);

    // Return the response
    return $response;
}

/**
 * Takes an input date string and returns a formatted "x ago" string
 *
 * @param string $inputdate Input about any English textual datetime description
 * @return string|null num "x" ago
 */
function datediff(string $inputdate): ?string
{
    $now = time(); // or your date as well
    $your_date = strtotime($inputdate);
    $datediff = $now - $your_date;

    switch (true) {
        case round($datediff / 31540000, 0, PHP_ROUND_HALF_DOWN) > 0:
            $val = round($datediff / 31540000, 0, PHP_ROUND_HALF_DOWN);
            if ($val > 1) {
                return $val . " years ago";
            } else {
                return $val . " year ago";
            }
        case round($datediff / 2628000, 0, PHP_ROUND_HALF_DOWN) > 0:
            $val = round($datediff / 2628000, 0, PHP_ROUND_HALF_DOWN);
            if ($val > 1) {
                return $val . " months ago";
            } else {
                return $val . " month ago";
            }
        case round($datediff / 604800, 0, PHP_ROUND_HALF_DOWN) > 0:
            $val = round($datediff / 604800, 0, PHP_ROUND_HALF_DOWN);
            if ($val > 1) {
                return $val . " weeks ago";
            } else {
                return $val . " week ago";
            }
        case round($datediff / 86400, 0, PHP_ROUND_HALF_DOWN) > 0:
            $val = round($datediff / 86400, 0, PHP_ROUND_HALF_DOWN);
            if ($val > 1) {
                return $val . " days ago";
            } else {
                return $val . " day ago";
            }
        case round($datediff / 3600, 0, PHP_ROUND_HALF_DOWN) > 0:
            $val = round($datediff / 3600, 0, PHP_ROUND_HALF_DOWN);
            if ($val > 1) {
                return $val . " hours ago";
            } else {
                return $val . " hour ago";
            }
        case round($datediff / 60, 0, PHP_ROUND_HALF_DOWN) > 0:
            $val = round($datediff / 60, 0, PHP_ROUND_HALF_DOWN);
            if ($val > 1) {
                return $val . " minutes ago";
            } else {
                return $val . " minute ago";
            }
        case $datediff <= 59:
            $val = $datediff;
            if ($val > 1) {
                return $val . " seconds ago";
            } else {
                return $val . " second ago";
            }
    }
    return null;
}

/**
 * Gets video data from `uploads` and stores to an array
 *
 * @param $link mysqli Prepared mysqli link, usually from conn.php
 * @param $id int Int of video id
 * @return array|null an array with all the video data
 */
function getVideoDataID(mysqli $link, int $id): array|null
{
    #get video data
    $sql = "SELECT * FROM uploads WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $id);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            $videodata = mysqli_fetch_assoc($result);

            if (!mysqli_num_rows($result) == 1) {
                leavePage();
            }
            if ($videodata['processed'] !== 1) {
                leavePage();
            }

        } else {
            echo "Something went wrong. Please try again later.";
            return null;
        }

        // Close statement
        mysqli_stmt_close($stmt);

        return $videodata;
    } else {
        return null;
    }

}

/**
 * Gets video data from `uploads` and stores to an array
 *
 * @param $link mysqli Prepared mysqli link, usually from conn.php
 * @param $filehash string String of video filehash
 * @return array|null an array with all the video data
 */
function getVideoDataFilehash(mysqli $link, string $filehash): array|null
{
    #get video data
    $sql = "SELECT * FROM uploads WHERE filehash = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $filehash);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            $videodata = mysqli_fetch_assoc($result);

            if (!mysqli_num_rows($result) == 1) {
                leavePage();
            }
            if ($videodata['processed'] !== 1) {
                leavePage();
            }

        } else {
            echo "Something went wrong. Please try again later.";
            return null;
        }

        // Close statement
        mysqli_stmt_close($stmt);

        return $videodata;
    } else {
        return null;
    }

}

/**
 * Gets tags from `videotags` based on filehash
 *
 * @param mysqli $link Prepared mysqli link, usually from conn.php
 * @param string $filehash Filehash of requested video
 * @return array|null an array with all tags of the filehash
 */
function getVideoTagsHash(mysqli $link, string $filehash): array|null
{

    #get video tags
    $sql = "SELECT tag FROM videotags WHERE filehash = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $filehash);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            $tl = array(); // create a variable to hold the information
            while (($row = mysqli_fetch_assoc($result))) {
                $tl[] = $row['tag']; // add the row in to the results (data) array
            }
        } else {
            echo "Something went wrong. Please try again later.";
            return null;
        }

        // Close statement
        mysqli_stmt_close($stmt);
        return $tl;
    } else {
        return null;
    }

}

function getPendingTagsHash(mysqli $link, string $filehash): array|null
{

    $sql = "SELECT tag, remove FROM videotagsuggest WHERE filehash = ? AND approved = 0";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $filehash);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            $tl = array(); // create a variable to hold the information
            while (($row = mysqli_fetch_assoc($result))) {
                $tl[] = $row; // add the row in to the results (data) array
            }
        } else {
            echo "Something went wrong. Please try again later.";
            return null;
        }

        // Close statement
        mysqli_stmt_close($stmt);
        return $tl;
    } else {
        return null;
    }

}

/**
 * Gets all available tags and formats them for tagify
 *
 * @param $link mysqli Prepared mysqli link, usually from conn.php
 * @return string|null a comma-separated string of all tags
 */
function getTagifyTags(mysqli $link): string|null
{
    $sql = "SELECT tag FROM tags";
    if ($stmt = mysqli_prepare($link, $sql)) {

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            $tl = array(); // create a variable to hold the information
            while (($row = mysqli_fetch_assoc($result))) {
                $tl[] = $row['tag']; // add the row in to the results (data) array
            }

            $taglist = implode(',', $tl);

        } else {
            echo "Something went wrong. Please try again later.";
            return null;
        }
        // Close statement
        mysqli_stmt_close($stmt);

        return str_replace(',', '","', $taglist);

    } else {
        return null;
    }
}

function suggestTagAction($link, $tag, $filehash, $username, $remove): bool
{
    $sql = "REPLACE into videotagsuggest (filehash, tag, suggester, remove) values(?,?,?,?)";

    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "ssss", $filehash, $tag, $username, $remove);

        // Attempt to execute the prepared statement
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return false;
        }

        // Close statement
        mysqli_stmt_close($stmt);
        return true;
    }
    return false;
}

/**
 * Updates `uploads` video_title and video_desc
 *
 * @param mysqli $link Prepared mysqli link, usually from conn.php
 * @param string $title String of desired title
 * @param string $desc htmlentities encoded string of description
 * @param string $filehash Filehash of requested video
 * @return bool Returns false if there is an issue
 */
function updateVideoTitleDesc(mysqli $link, string $title, string $desc, string $filehash): bool
{
    $sql = "UPDATE uploads SET video_title = ?, video_desc = ? WHERE filehash = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "sss", $title, $desc, $filehash);

        // Attempt to execute the prepared statement
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return false;
        }

        // Close statement
        mysqli_stmt_close($stmt);
        return true;
    }
    return false;
}

/**
 * Checks if a tag is in `tags`
 *
 * @param mysqli $link Prepared mysqli link, usually from conn.php
 * @param string $tag The tag to check if exists
 * @return bool|null Returns true/false on result, or null if error
 */
function checkTagExists(mysqli $link, string $tag): bool|null
{
    $sql = "SELECT tag FROM tags WHERE tag = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $tag);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            $exists = (bool)mysqli_num_rows($result);

        } else {
            echo "Something went wrong. Please try again later.";
            return null;
        }
        // Close statement
        mysqli_stmt_close($stmt);
        return $exists;

    } else {
        echo "Something went wrong. Please try again later.";
        return null;
    }
}

function createTag(mysqli $link, string $tag): bool
{
    $sql = "INSERT IGNORE INTO tags (tag) VALUES (?)";

    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $tag);

        // Attempt to execute the prepared statement
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            queryFailed();
        }

        // Close statement
        mysqli_stmt_close($stmt);
        return true;
    }
    queryFailed();
}

function createVideotag(mysqli $link, string $tag, string $filehash): bool
{
    $sql = "INSERT IGNORE INTO videotags (filehash,tag) VALUES (?,?)";

    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "ss", $filehash, $tag);

        // Attempt to execute the prepared statement
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            queryFailed();
        }

        // Close statement
        mysqli_stmt_close($stmt);
        return true;
    }
    queryFailed();
}

function countTagUses(mysqli $link, string $tag): int
{
    $sql = "SELECT count(tag) as count FROM videotags WHERE tag = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {

        mysqli_stmt_bind_param($stmt, "s", $tag);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            $count = mysqli_fetch_assoc($result);

        } else {
            mysqli_stmt_close($stmt);
            queryFailed();
        }
        // Close statement
        mysqli_stmt_close($stmt);
        return $count['count'];
    }
    queryFailed();
}

function deleteTag(mysqli $link, string $tag): bool
{
    $sql = "DELETE FROM tags WHERE tag = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $tag);

        // Attempt to execute the prepared statement
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            queryFailed();
        }

        // Close statement
        mysqli_stmt_close($stmt);
        return true;
    }
    queryFailed();
}

function deleteVideoTag(mysqli $link, string $tag, string $filehash): bool
{
    $sql = "DELETE FROM videotags WHERE tag = ? AND filehash = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "ss", $tag, $filehash);

        // Attempt to execute the prepared statement
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            queryFailed();
        }

        // Close statement
        mysqli_stmt_close($stmt);
        return true;
    }
    queryFailed();
}

function createVideoComment(mysqli $link, string $filehash, string $username, string $comment): bool
{
    $sql = "INSERT INTO videocomments (filehash,username,comment_data) VALUES (?,?,?)";

    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "sss", $filehash, $username, $comment);

        // Attempt to execute the prepared statement
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            queryFailed();
        }

        // Close statement
        mysqli_stmt_close($stmt);
        return true;
    }
    queryFailed();
}

function getVideoComments(mysqli $link, string $filehash): array|null
{
    $sql = "SELECT * FROM videocomments WHERE filehash = ? ORDER BY comment_date DESC";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $filehash);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            $commentdata = array();
            while (($row = mysqli_fetch_assoc($result))) {
                $commentdata[] = $row; // add the row in to the results (data) array
            }

        } else {
            queryFailed();
        }

        // Close statement
        mysqli_stmt_close($stmt);
        return $commentdata;
    }
    queryFailed();
}

function disable_ob(): void
{
    // Turn off output buffering
    ini_set('output_buffering', 'off');
    // Turn off PHP output compression
    ini_set('zlib.output_compression', false);
    // Implicitly flush the buffer(s)
    ini_set('implicit_flush', true);
    ob_implicit_flush();
    // Clear, and turn off output buffering
    while (ob_get_level() > 0) {
        // Get the curent level
        $level = ob_get_level();
        // End the buffering
        ob_end_clean();
        // If the current level has not changed, abort
        if (ob_get_level() == $level) break;
    }
    // Disable apache output buffering/compression
    if (function_exists('apache_setenv')) {
        apache_setenv('no-gzip', '1');
        apache_setenv('dont-vary', '1');
    }
}

function checkIfLiked(mysqli $link, string $filehash, string $username): bool|null
{
    $sql = "SELECT username FROM videolikes WHERE filehash = ? AND username = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $filehash, $username);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            $exists = mysqli_num_rows($result) == 1;

        } else {
            echo "Something went wrong. Please try again later.";
            return null;
        }
        // Close statement
        mysqli_stmt_close($stmt);
        return $exists;

    } else {
        echo "Something went wrong. Please try again later.";
        return null;
    }
}
