<?php
function processThumbnail(string $tmp_name, string $finaldir): bool
{
    #Set the desired dimensions
    $max_width = 960;
    $max_height = 540;
    #Get image details
    list($width, $height) = getimagesize($tmp_name);
    #If image is too large on both sides, get one of the sides the correct size
    $croptoggle = TRUE;
    $desratio = $max_width / $max_height;
    $ogratio = $width / $height;
    if ($ogratio > $desratio) {
        #Image is too wide, so set the height to max and scale the width
        $scaleratio = $height / $max_height;
        $new_width = $width / $scaleratio;
        $new_height = $max_height;
    } elseif ($ogratio < $desratio) {
        #Image is too tall, so set the width to max and scale the height
        $scaleratio = $width / $max_width;
        $new_width = $max_width;
        $new_height = $height / $scaleratio;
    } else {
        #image is correct ratio, but not the right size, so do that
        $new_width = $max_width;
        $new_height = $max_height;
    }

    $src = imagecreatefromstring(file_get_contents($tmp_name));
    $dst = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    imagedestroy($src);

    if ($croptoggle) {
        #Image is not 16:9, so we need to crop it's edges
        $src_x = $src_y = 0;
        if ($new_width > $max_width) {
            $src_x = ($new_width - $max_width) / 2;
        }
        if ($new_height > $max_height) {
            $src_y = ($new_height - $max_height) / 2;
        }
        $dst = imagecrop($dst, array("x" => $src_x, "y" => $src_y, "width" => $max_width, "height" => $max_height));
    }

    imagejpeg($dst, $finaldir);
    imagedestroy($dst);
    return true;
}