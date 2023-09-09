<?php

function deleteVideoByID($username, $id, $link): int
{

    $err_no = 0;

    #verify paramaters supplied
    if (!empty($username) && !empty($id) && !empty($link)) {
        $err_no = 1;
    }

    #verify connection is available
    if (empty($link) || $link === FALSE) {
        $err_no = 2;
    } else {

        #get video filehash
        $sql = "SELECT filehash FROM uploads WHERE username = ? AND id = ? AND processed = 1";
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ss", $username, $id);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                /* store result */
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);

                if (mysqli_num_rows($result) == 1) {

                    ### Found the video, so lets start cleanup
                    $hash = $row['filehash'];

                    #Delete file from uploads section
                    $sql = "DELETE FROM uploads WHERE filehash = ?";
                    if ($stmt = mysqli_prepare($link, $sql)) {
                        // Bind variables to the prepared statement as parameters
                        mysqli_stmt_bind_param($stmt, "s", $hash);

                        // Attempt to execute the prepared statement
                        if (!mysqli_stmt_execute($stmt)) {
                            $err_no = 3;
                            // Close statement
                            mysqli_stmt_close($stmt);
                        }
                        #File deleted successfully
                    }

                    #Do tag cleanup
                    $sql = "SELECT tag FROM videotags WHERE filehash = ?";
                    if ($stmt1 = mysqli_prepare($link, $sql)) {
                        // Bind variables to the prepared statement as parameters
                        mysqli_stmt_bind_param($stmt1, "s", $hash);

                        // Attempt to execute the prepared statement
                        if (mysqli_stmt_execute($stmt1)) {
                            /* store result */
                            $result = mysqli_stmt_get_result($stmt1);

                            $taglist = array();
                            while (($row = mysqli_fetch_assoc($result))) {
                                array_push($taglist, $row['tag']); // add the row in to the results (data) array
                            }

                            foreach ($taglist as $tag) {

                                #check and delete from tags
                                $sql = "SELECT tag FROM videotags WHERE tag = ?";
                                if ($stmt2 = mysqli_prepare($link, $sql)) {
                                    // Bind variables to the prepared statement as parameters
                                    mysqli_stmt_bind_param($stmt2, "s", $tag);

                                    // Attempt to execute the prepared statement
                                    if (mysqli_stmt_execute($stmt2)) {

                                        $result = mysqli_stmt_get_result($stmt2);

                                        #delete from tags if tag is only used once
                                        if (mysqli_num_rows($result) == 1) {
                                            $sql = "DELETE FROM tags WHERE tag = ?";
                                            if ($stmt3 = mysqli_prepare($link, $sql)) {
                                                // Bind variables to the prepared statement as parameters
                                                mysqli_stmt_bind_param($stmt3, "s", $tag);

                                                // Attempt to execute the prepared statement
                                                if (!mysqli_stmt_execute($stmt3)) {
                                                    $err_no = 3;
                                                }

                                                // Close statement
                                                mysqli_stmt_close($stmt3);
                                            }
                                        }
                                    }
                                    // Close statement
                                    mysqli_stmt_close($stmt2);
                                }

                            }

                            #delete tags from videotags
                            $sql = "DELETE FROM videotags WHERE filehash = ?";
                            if ($stmt4 = mysqli_prepare($link, $sql)) {
                                // Bind variables to the prepared statement as parameters
                                mysqli_stmt_bind_param($stmt4, "s", $hash);

                                // Attempt to execute the prepared statement
                                if (!mysqli_stmt_execute($stmt4)) {
                                    $err_no = 3;
                                }

                                // Close statement
                                mysqli_stmt_close($stmt4);
                            }

                        } else {
                            $err_no = 3;
                        }
                        // Close statement
                        mysqli_stmt_close($stmt1);
                    }

                    #Do file cleanup (assumes exists in s3)
                    $s3userdir = "/mnt/s3/" . $username . "/"; #user directory in s3
                    $s3thumbpath = $s3userdir . $hash . ".jpg"; #thumb moved to s3
                    $s3encpath = $s3userdir . $hash . "_enc.mp4"; #encoded file moved to s3

                    if (is_file($s3thumbpath)) {
                        if (!unlink($s3thumbpath)) {
                            $err_no = 5;
                        }
                    } else {
                        $err_no = 6;
                    }
                    if (is_file($s3encpath)) {
                        if (!unlink($s3encpath)) {
                            $err_no = 5;
                        }
                    } else {
                        $err_no = 6;
                    }

                    ### Delete complete

                } else {
                    $err_no = 4;
                }

            } else {
                $err_no = 3;
                // Close statement
                mysqli_stmt_close($stmt);
            }

        }
    }

    return $err_no;
}

function parseDeleteVideoError($errorno): string
{

    if (empty($errorno)) {
        return "No error number supplied";
    } else {

        if ($errorno == 0) {
            return "No error";
        } else if ($errorno == 1) {
            return "Parameters empty";
        } else if ($errorno == 2) {
            return "Unable to connect to database";
        } else if ($errorno == 3) {
            return "Error executing database query";
        } else if ($errorno == 4) {
            return "Unexpected number of rows recieved";
        } else if ($errorno == 5) {
            return "Error deleting files from s3";
        } else if ($errorno == 6) {
            return "Error finding files in s3";
        } else {
            return "Unknown error occoured";
        }

    }

}