<?php
/** @noinspection PhpIncludeInspection */

use JetBrains\PhpStorm\NoReturn;

error_reporting(E_ERROR | E_PARSE);

#[NoReturn] function startCleanup(string $hash, string $username, string $fileext, mysqli $link): void
{
    $s3userdir = "/mnt/s3/" . $username . "/"; #user directory in s3
    $s3userprocessdir = $s3userdir . "processing/"; #user processing directory in s3

    $originalFile = $s3userprocessdir . $hash . "." . $fileext; #original non-encoded file

    $localencodingdir = "/processing/";
    $localOriginalFile = $localencodingdir . $hash . "." . $fileext;
    $generatedThumb = $localencodingdir . $hash . ".jpg"; #thumb created from file
    $croppedThumb = $localencodingdir . $hash . "_crop.jpg"; #thumb created from file
    $encodedFile = $localencodingdir . $hash . "_enc.mp4"; #encoded file

    $s3thumb = $s3userdir . $hash . ".jpg"; #thumb moved to s3
    $s3video = $s3userdir . $hash . "_enc.mp4"; #encoded file moved to s3

    $unlink_files = array(
        $originalFile, $localOriginalFile, $generatedThumb, $croppedThumb, $encodedFile, $s3thumb, $s3video
    );

    foreach ($unlink_files as $file) {
        if (unlink($file)) {
            print "[INFO] [" . $username . " " . $hash . "] Successfully unlinked " . $file . PHP_EOL;
        } else {
            print "[INFO] [" . $username . " " . $hash . "] Failed to unlink " . $file . PHP_EOL;
        }
    }

    //Remove video from uploads
    $sql = "DELETE FROM uploads WHERE filehash = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $hash);

        if (mysqli_stmt_execute($stmt)) {
            print "[INFO] [" . $username . " " . $hash . "] Deleted uploads DB entry." . PHP_EOL;
        } else {
            print "[INFO] [" . $username . " " . $hash . "] Error deleting uploads DB entry." . PHP_EOL;
        }
        mysqli_stmt_close($stmt);
    }

    //Remove video from videoviews
    $sql = "DELETE FROM videoviews WHERE filehash = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $hash);

        if (mysqli_stmt_execute($stmt)) {
            print "[INFO] [" . $username . " " . $hash . "] Deleted uploads DB entry." . PHP_EOL;
        } else {
            print "[INFO] [" . $username . " " . $hash . "] Error deleting uploads DB entry." . PHP_EOL;
        }
        mysqli_stmt_close($stmt);
    }

    //Remove tags
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
                                    print "[INFO] [" . $username . " " . $hash . "] Error deleting tag " . $tag . PHP_EOL;
                                } else {
                                    print "[INFO] [" . $username . " " . $hash . "] Deleted unique tag " . $tag . PHP_EOL;

                                }

                                // Close statement
                                mysqli_stmt_close($stmt3);
                            }
                        }
                    } else {
                        print "[INFO] [" . $username . " " . $hash . "] Error getting tags." . PHP_EOL;

                    }
                    // Close statement
                    mysqli_stmt_close($stmt2);
                }

            }

            //Delete tags from videotags
            $sql = "DELETE FROM videotags WHERE filehash = ?";
            if ($stmt4 = mysqli_prepare($link, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt4, "s", $hash);

                // Attempt to execute the prepared statement
                if (mysqli_stmt_execute($stmt4)) {
                    print "[INFO] [" . $username . " " . $hash . "] Deleted from videotags." . PHP_EOL;
                } else {
                    print "[INFO] [" . $username . " " . $hash . "] Error deleting from videotags." . PHP_EOL;
                }

                // Close statement
                mysqli_stmt_close($stmt4);
            }

        } else {
            print "[INFO] [" . $username . " " . $hash . "] Error getting tags from videotags." . PHP_EOL;
        }
        // Close statement
        mysqli_stmt_close($stmt1);
    }


    die();
}

function doProgressionStore($link, string $username, string $hash, string $percentage): void
{

    $updatePercentages = array(15, 30, 45, 60, 75, 90, 99);

    if (in_array($percentage, $updatePercentages)) {

        $sql = "UPDATE uploads SET percentage = ? WHERE filehash = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ss", $percentage, $hash);

            // Attempt to execute the prepared statement
            if (!mysqli_stmt_execute($stmt)) {
                print "[WARN] [" . $username . " " . $hash . "] couldn't update proc percentage" . PHP_EOL;
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }

    }

}

function startProcessUpload(string $hash, string $username, string $fileext, mysqli $link): void
{
    print "[INFO] FFMPEG RUNNER starting" . PHP_EOL;

    if (empty($hash) || empty($username) || empty($fileext)) {
        print "[ERROR] Missing attrbutes. I got: hash=" . $hash . " user=" . $username . " ext=" . $fileext . PHP_EOL;
        startCleanup($hash, $username, $fileext, $link);
    }

    $s3userdir = "/mnt/s3/" . $username . "/"; #user directory in s3
    $s3userprocessdir = $s3userdir . "processing/"; #user processing directory in s3

    $originalFile = $s3userprocessdir . $hash . "." . $fileext; #original non-encoded file

    $localencodingdir = "/processing/";
    $localOriginalFile = $localencodingdir . $hash . "." . $fileext;
    $generatedThumb = $localencodingdir . $hash . ".jpg"; #thumb created from file
    $croppedThumb = $localencodingdir . $hash . "_crop.jpg"; #thumb created from file
    $encodedFile = $localencodingdir . $hash . "_enc.mp4"; #encoded file

    $s3thumb = $s3userdir . $hash . ".jpg"; #thumb moved to s3
    $s3video = $s3userdir . $hash . "_enc.mp4"; #encoded file moved to s3

    require_once '/var/www/toypics/vendor/autoload.php';

    print "[INFO] [" . $username . " " . $hash . "] Getting files from S3" . PHP_EOL;
    if (!is_readable($originalFile)) {
        print "[ERROR] [" . $username . " " . $hash . "] Error reading file from S3" . PHP_EOL;
        startCleanup($hash, $username, $fileext, $link);
    } else {
        if (!copy($originalFile, $localOriginalFile)) {
            print "[ERROR] [" . $username . " " . $hash . "] Error copying file from S3 to disk" . PHP_EOL;
            startCleanup($hash, $username, $fileext, $link);
        }
    }

    print "[INFO] [" . $username . " " . $hash . "] Starting FFMPEG" . PHP_EOL;

    $ffmpeg = FFMpeg\FFMpeg::create(
        array(
            'timeout' => 3600,
            'ffmpeg.binaries' => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            'ffmpeg.threads' => 2,   // The number of threads that FFMpeg should use
        )
    );
    try {
        $format = new FFMpeg\Format\Video\X264();
        $format->on('progress', function ($video, $format, $percentage) use ($username, $hash, $link) {
            print "[INFO] [" . $username . " " . $hash . "] $percentage% encoded" . PHP_EOL;
            doProgressionStore($link, $username, $hash, $percentage);
        });
        $video = $ffmpeg->open($originalFile);
        $video
            ->filters()
            ->synchronize();
    } catch (exception $e) {
        print "[ERROR] [" . $username . " " . $hash . "] Error in FFMPEG" . PHP_EOL;
        print $e->getMessage();
        startCleanup($hash, $username, $fileext, $link);
    }

    try {
        print "[INFO] [" . $username . " " . $hash . "] Starting FFMPEG thumbnail creation" . PHP_EOL;
        $video
            ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(10))
            ->save($generatedThumb);
    } catch (exception $e) {
        print "[ERROR] [" . $username . " " . $hash . "] Error creating thumbnail" . PHP_EOL;
        print $e->getMessage();
        startCleanup($hash, $username, $fileext, $link);
    }

    if (!is_readable($generatedThumb)) {
        print "[ERROR] [" . $username . " " . $hash . "] Unable to read generated thumb file" . PHP_EOL;
        startCleanup($hash, $username, $fileext, $link);
    }

    print "[INFO] [" . $username . " " . $hash . "] Pausing to settle FFMPEG" . PHP_EOL;
    sleep(5);
    print "[INFO] [" . $username . " " . $hash . "] Cropping thumbnail" . PHP_EOL;

    #crop the thumbnail properly
    require_once("/var/www/toypics/includes/func-procthumb.php");
    processThumbnail($generatedThumb, $croppedThumb);

    print "[INFO] [" . $username . " " . $hash . "] Copy thumb to S3" . PHP_EOL;
    copy($croppedThumb, $s3thumb);

    print "[INFO] [" . $username . " " . $hash . "] Starting video encoding" . PHP_EOL;

    $format
        ->setKiloBitrate(7500)
        ->setAudioChannels(2)
        ->setAudioKiloBitrate(256);
    $video
        ->save($format, $encodedFile);

    print "[INFO] [" . $username . " " . $hash . "] Encoding complete" . PHP_EOL;

    if (!is_readable($encodedFile)) {
        print "[ERROR] [" . $username . " " . $hash . "] Unable to read encoded video" . PHP_EOL;
        startCleanup($hash, $username, $fileext, $link);
    }

    print "[INFO] [" . $username . " " . $hash . "] Copy video to S3" . PHP_EOL;
    copy($encodedFile, $s3video);

    print "[INFO] [" . $username . " " . $hash . "] Pausing to settle S3" . PHP_EOL;
    sleep(5);
    print "[INFO] [" . $username . " " . $hash . "] Verifying files in S3" . PHP_EOL;

    if (!is_readable($s3video)) {
        print "[ERROR] [" . $username . " " . $hash . "] Unable to read S3 video" . PHP_EOL;
        startCleanup($hash, $username, $fileext, $link);
    }

    if (!is_readable($s3thumb)) {
        print "[ERROR] [" . $username . " " . $hash . "] Unable to read S3 thumb" . PHP_EOL;
        startCleanup($hash, $username, $fileext, $link);
    }

    print "[INFO] [" . $username . " " . $hash . "] Cleaning up excess files" . PHP_EOL;

    unlink($croppedThumb);
    unlink($encodedFile);
    unlink($originalFile);
    unlink($localOriginalFile);
    unlink($generatedThumb);

    print "[INFO] [" . $username . " " . $hash . "] Marking processed in DB" . PHP_EOL;

    $sql = "UPDATE uploads SET processed = 1, percentage = 100 WHERE filehash = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $hash);

        // Attempt to execute the prepared statement
        if (!mysqli_stmt_execute($stmt)) {
            print "[ERROR] [" . $username . " " . $hash . "] Couldn't mark processed in DB" . PHP_EOL;
            startCleanup($hash, $username, $fileext, $link);
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }
    print "[INFO] [" . $username . " " . $hash . "] Completed" . PHP_EOL;
}