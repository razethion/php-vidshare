<?php

$url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
$url_components = array();
$url_components = parse_url($url);

if (isset($url_components['query'])) {
    if (empty(trim($url_components['query']))) {
        die;
    } else {
        $query = trim($url_components['query']);
    }
} else {
    die;
}

$exploded = explode(",", $query);
$username = $exploded[0] ?? '';
$index = $exploded[1] ?? '';

if (!is_numeric($index) || empty($username)) {
    header("HTTP/1.1 422 Unprocessable Entity");
    die();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");

header('Access-Control-Allow-Origin: ' . SITE_DOMAIN);
header('Access-Control-Allow-Methods: GET');

$sql = "SELECT id, username, fh.filehash, video_title, upload_date, views, COALESCE(vl.likes, 0) as likes
FROM (SELECT filehash FROM videolikes WHERE username = ?) as fh
         LEFT JOIN (SELECT filehash, COUNT(username) as likes FROM videolikes GROUP BY filehash) as vl
                   ON fh.filehash = vl.filehash
         LEFT JOIN (SELECT username, id, filehash, upload_date, video_title FROM uploads) as u
                   ON fh.filehash = u.filehash
         LEFT JOIN (SELECT filehash, sum(views) as views FROM videoviews GROUP BY filehash) as vv
                   ON fh.filehash = vv.filehash
                   ORDER BY upload_date LIMIT ?,3";

if ($stmt = mysqli_prepare($link, $sql)) {

    mysqli_stmt_bind_param($stmt, "ss", $username, $index);

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $processing = array(); // create a variable to hold the information
        while (($row = mysqli_fetch_assoc($result))) {
            $processing[] = $row; // add the row in to the results (data) array
        }

    } else {
        header("HTTP/1.1 500 Internal Server Error");
        die();
    }
    // Close statement
    mysqli_stmt_close($stmt);
}

echo json_encode($processing);