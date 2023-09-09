<?php
session_start();

#[NoReturn] function leavePage(): void
{
    header("Location: /");
    die();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
    leavePage();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");

$sql = "SELECT u.username as username, u.id as id
FROM uploads u
LEFT JOIN videotags vt ON u.filehash = vt.filehash
GROUP BY u.filehash
HAVING COUNT(vt.filehash) <= 4
ORDER BY RAND()
LIMIT 1;";

if ($stmt = mysqli_prepare($link, $sql)) {

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $row = mysqli_fetch_assoc($result);

    } else {
        mysqli_stmt_close($stmt);
        leavePage();
    }
    // Close statement
    mysqli_stmt_close($stmt);
} else {
    leavePage();
}

header("Location: /u/" . $row['username'] . "/" . $row['id'] . "/tagedit");
