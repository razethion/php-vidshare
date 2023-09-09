<?php

$url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
$url_components = array();
$url_components = parse_url($url);

if (isset($url_components['query'])) {
    if (empty(trim($url_components['query']))) {
        die;
    } else {
        $username = trim($url_components['query']);
    }
} else {
    die;
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");

header('Access-Control-Allow-Origin: ' . SITE_DOMAIN);
header('Access-Control-Allow-Methods: GET');

$sql = "SELECT * FROM uploads WHERE processed = 0 AND username = ? ORDER BY upload_date LIMIT 1";

if ($stmt = mysqli_prepare($link, $sql)) {

    mysqli_stmt_bind_param($stmt, "s", $username);

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $processing = array(); // create a variable to hold the information
        while (($row = mysqli_fetch_assoc($result))) {
            $processing = $row; // add the row in to the results (data) array
        }

    } else {
        echo "Something went wrong. Please try again later.";
    }
    // Close statement
    mysqli_stmt_close($stmt);
}

echo json_encode($processing);