<?php

require_once 'cred.php';

$DB_SERVER = DB_SERVER;
$DB_USERNAME = DB_USERNAME;
$DB_PASSWORD = DB_PASSWORD;
$DB_NAME = 'toyp2';
$CACERT = $_SERVER['DOCUMENT_ROOT'] . '/includes/cert.crt';

/* Attempt to connect to MySQL database */
$link = mysqli_init();
mysqli_ssl_set($link, null, null, $CACERT, null, null);
mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 1);
mysqli_options($link, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);

if (!mysqli_real_connect($link, $DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_NAME, 3306, NULL, MYSQLI_CLIENT_SSL)) {
    die("Connect Error: " . mysqli_connect_error());
}