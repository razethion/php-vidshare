<?php

print "[INFO] - Starting rabbit worker" . PHP_EOL;

require_once '/var/www/toypics/vendor/autoload.php';
require_once "/var/www/toypics/includes/conn.php";
require_once '/var/www/toypics/rabbit/ffmpeg-runner.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

//Verify we can communicate with the database
if (!mysqli_ping($link)) {
    die("[ERROR] Unable to connect to DB: " . mysqli_error($link));
} else {
    print "[INFO] - Database connection verified" . PHP_EOL;
}

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

print "[INFO] - " . gethostname() . " is ready to process requests" . PHP_EOL;

$callback = function ($msg) use ($link) {

    // Convert message body to request data
    $requestData = $msg->body;

    echo "[INFO] Processing request: " . $requestData . PHP_EOL;

    //Verify we can communicate with the database
    if (!mysqli_ping($link)) {
        die("[ERROR] Unable to connect to DB: " . mysqli_error($link));
    }

    $dataa = explode(",", $requestData);
    startProcessUpload($dataa[0], $dataa[1], $dataa[2], $link);

    print "[INFO] Done processing request: " . $requestData . PHP_EOL;
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    echo "[INFO] Waiting for next message" . PHP_EOL;
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('old_process_queue', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
print "[INFO] - " . gethostname() . " is exiting the queue" . PHP_EOL;
