<?php
require_once '/var/www/toypics/vendor/autoload.php';
require_once '/var/www/toypics/includes/vars.php';
require_once '/var/www/toypics/includes/conn.php';

header('Access-Control-Allow-Origin: ' . SITE_DOMAIN);
header('Access-Control-Allow-Methods: POST');

use JetBrains\PhpStorm\NoReturn;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
$url_components = array();
$url_components = parse_url($url);

if (empty($url_components['query'])) {
    print ("400 Bad Request.");
    http_response_code(400);
    die();
}

$exploded = explode(",", $url_components['query']);
$hash = $exploded[0] ?? '';
$username = $exploded[1] ?? '';
$extension = $exploded[2] ?? '';

if (!empty(trim($hash)) && !empty(trim($username)) && !empty(trim($extension))) {

    $s3userdir = "/mnt/s3/" . $username . "/"; #user directory in s3
    $s3userprocessdir = $s3userdir . "processing/"; #user processing directory in s3
    $originalFile = $s3userprocessdir . $hash . "." . $extension; #original non-encoded file


    //make sure we can access the video file
    if (!is_readable($originalFile)) {
        print ("500 Internal Server Error.");
        http_response_code(500);
        die();
    }

    // do the actual request
    $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    $channel = $connection->channel();
    // Declare a quorum queue
    $channel->queue_declare(
        "old_process_queue",
        false,  // passive
        true,   // durable
        false,  // exclusive
        false,  // auto_delete
        false,  // nowait
        [
            'x-queue-type' => ['S', 'quorum'] // Set queue type to quorum
        ]
    );

    // Get your web request data here, e.g., from $_POST or $_GET
    $data = $hash . "," . $username . "," . $extension;

    $msg = new AMQPMessage($data, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
    $channel->basic_publish($msg, '', 'old_process_queue');
    $channel->close();
    $connection->close();

    print $url_components['query'];
    http_response_code(201);

} else {
    print ("400 Bad Request.");
    http_response_code(400);
    die();
}
