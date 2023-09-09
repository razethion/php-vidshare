<?php
// Initialize the session
session_start();

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");

#list latest uploads
$sql = "SELECT username,id,v.filehash,upload_date,video_title,views, COALESCE(likes, 0) as likes
FROM (
         SELECT filehash, sum(views) as views
         FROM videoviews
         GROUP BY filehash
     ) as v
         LEFT JOIN uploads u on v.filehash = u.filehash
LEFT JOIN (
    SELECT filehash, COUNT(username) AS likes
    FROM videolikes
    GROUP BY filehash
) AS vl ON v.filehash = vl.filehash
WHERE processed = 1
ORDER BY upload_date DESC
LIMIT 6";

if ($stmt = mysqli_prepare($link, $sql)) {

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $latestuploads = array(); // create a variable to hold the information
        while (($row = mysqli_fetch_assoc($result))) {
            $latestuploads[] = $row; // add the row in to the results (data) array
        }

    } else {
        echo "Something went wrong. Please try again later.";
    }
    // Close statement
    mysqli_stmt_close($stmt);
}

#list popular uploads
$sql = "SELECT *
FROM (SELECT username, id, v.filehash, upload_date, video_title, views, COALESCE(likes, 0) as likes
      FROM (SELECT filehash, sum(views) as views
            FROM videoviews
            GROUP BY filehash) as v
               LEFT JOIN uploads u on v.filehash = u.filehash
      LEFT JOIN (
    SELECT filehash, COUNT(username) AS likes
    FROM videolikes
    GROUP BY filehash
) AS vl ON v.filehash = vl.filehash
      WHERE processed = 1
      ORDER BY views DESC
      LIMIT 100) as a
ORDER BY rand()
LIMIT 6";

if ($stmt = mysqli_prepare($link, $sql)) {

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $popularvids = array(); // create a variable to hold the information
        while (($row = mysqli_fetch_assoc($result))) {
            $popularvids[] = $row; // add the row in to the results (data) array
        }

    } else {
        echo "Something went wrong. Please try again later.";
    }
    // Close statement
    mysqli_stmt_close($stmt);
}

#list popular tags
$sql = "SELECT tag, count(tag) as count FROM videotags group by tag order by count desc limit 8";
if ($stmt = mysqli_prepare($link, $sql)) {

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $alltags = array(); // create a variable to hold the information
        while (($row = mysqli_fetch_assoc($result))) {
            $alltags += array($row['tag'] => $row['count']); // add the row in to the results (data) array
        }

    } else {
        echo "Something went wrong. Please try again later.";
    }
    // Close statement
    mysqli_stmt_close($stmt);
}

#list recent users
$sql = "SELECT username FROM userdata ORDER BY lastlogon DESC LIMIT 4";
if ($stmt = mysqli_prepare($link, $sql)) {

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $recentuserlist = array(); // create a variable to hold the information
        $num = 0;
        while (($row = mysqli_fetch_assoc($result))) {
            $recentuserlist += [$num => array('username' => $row['username'], 'uploads' => '0')];
            $num++;
        }

    } else {
        echo "Something went wrong. Please try again later.";
    }
    // Close statement
    mysqli_stmt_close($stmt);
}

#get video count for recent users
$num = 0;
foreach ($recentuserlist as $ru => $arr) {
    $upcount = 0;
    $sql = "SELECT filehash FROM uploads WHERE username = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {

        mysqli_stmt_bind_param($stmt, "s", $arr['username']);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {

            /* store result */
            mysqli_stmt_store_result($stmt);
            $upcount = mysqli_stmt_num_rows($stmt);

            $recentuserlist[$num]['uploads'] = $upcount;
            $num++;

        } else {
            echo "Something went wrong. Please try again later.";
        }
        // Close statement
        mysqli_stmt_close($stmt);
    }

}

#get stats
$sql = "SELECT (SELECT count(filehash) FROM uploads)                 as totaluploads,
       (SELECT count(username)
        FROM userdata
        WHERE lastlogon >= DATE_SUB(NOW(), INTERVAL 1 HOUR)) as activeusers,
       (SELECT count(username) from users)                   as totalusers,
       (SELECT count(processed)
        from uploads
        WHERE processed = 0)                                 as queuelength,
       (SELECT count(filehash) from videoviews)              as totalviews";

if ($stmt = mysqli_prepare($link, $sql)) {

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $stats = array(); // create a variable to hold the information
        while (($row = mysqli_fetch_assoc($result))) {
            $stats = $row; // add the row in to the results (data) array
        }

    } else {
        echo "Something went wrong. Please try again later.";
    }
    // Close statement
    mysqli_stmt_close($stmt);
}

#TODO this section should be looped
$newestvidshtml = '

<div class="row">
    <div class="col-md-4 text-white mb-3 vidhov">
        <a class="text-decoration-none text-white" href="/u/' . $latestuploads[0]['username'] . '/' . $latestuploads[0]['id'] . '">
            <div class="bg-secondary-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block rounded" style="max-height:135px;" src="' . S3_URL . $latestuploads[0]['username'] . '/' . $latestuploads[0]['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $latestuploads[0]['video_title'] . '</p>
                    <p class="fs-6 lh-1 text-secondary-emphasis">
                    <span style="display:inline-block;" class="text-danger fw-bold">' . $latestuploads[0]['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($latestuploads[0]['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $latestuploads[0]['likes'] . ' likes</span> · 
                    <span style="display:inline-block;">' . $latestuploads[0]['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
    <div class="col-md-4 text-white mb-3 vidhov">
        <a class="text-decoration-none text-white" href="/u/' . $latestuploads[1]['username'] . '/' . $latestuploads[1]['id'] . '">
            <div class="bg-secondary-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block rounded" style="max-height:135px;" src="' . S3_URL . $latestuploads[1]['username'] . '/' . $latestuploads[1]['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $latestuploads[1]['video_title'] . '</p>
                    <p class="fs-6 lh-1 text-secondary-emphasis">
                    <span style="display:inline-block;" class="text-danger fw-bold">' . $latestuploads[1]['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($latestuploads[1]['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $latestuploads[1]['likes'] . ' likes</span> · 
                    <span style="display:inline-block;">' . $latestuploads[1]['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
    <div class="col-md-4 text-white mb-3 vidhov">
        <a class="text-decoration-none text-white" href="/u/' . $latestuploads[2]['username'] . '/' . $latestuploads[2]['id'] . '">
            <div class="bg-secondary-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block rounded" style="max-height:135px;" src="' . S3_URL . $latestuploads[2]['username'] . '/' . $latestuploads[2]['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $latestuploads[2]['video_title'] . '</p>
                    <p class="fs-6 lh-1 text-secondary-emphasis">
                    <span style="display:inline-block;" class="text-danger fw-bold">' . $latestuploads[2]['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($latestuploads[2]['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $latestuploads[2]['likes'] . ' likes</span> · 
                    <span style="display:inline-block;">' . $latestuploads[2]['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
</div>
<div class="row">
    <div class="col-md-4 text-white mb-3 vidhov">
        <a class="text-decoration-none text-white" href="/u/' . $latestuploads[3]['username'] . '/' . $latestuploads[3]['id'] . '">
            <div class="bg-secondary-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block rounded" style="max-height:135px;" src="' . S3_URL . $latestuploads[3]['username'] . '/' . $latestuploads[3]['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $latestuploads[3]['video_title'] . '</p>
                    <p class="fs-6 lh-1 text-secondary-emphasis">
                    <span style="display:inline-block;" class="text-danger fw-bold">' . $latestuploads[3]['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($latestuploads[3]['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $latestuploads[3]['likes'] . ' likes</span> · 
                    <span style="display:inline-block;">' . $latestuploads[3]['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
    <div class="col-md-4 text-white mb-3 vidhov">
        <a class="text-decoration-none text-white" href="/u/' . $latestuploads[4]['username'] . '/' . $latestuploads[4]['id'] . '">
            <div class="bg-secondary-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block rounded" style="max-height:135px;" src="' . S3_URL . $latestuploads[4]['username'] . '/' . $latestuploads[4]['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $latestuploads[4]['video_title'] . '</p>
                    <p class="fs-6 lh-1 text-secondary-emphasis">
                    <span style="display:inline-block;" class="text-danger fw-bold">' . $latestuploads[4]['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($latestuploads[4]['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $latestuploads[4]['likes'] . ' likes</span> · 
                    <span style="display:inline-block;">' . $latestuploads[4]['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
    <div class="col-md-4 text-white mb-3 vidhov">
        <a class="text-decoration-none text-white" href="/u/' . $latestuploads[5]['username'] . '/' . $latestuploads[5]['id'] . '">
            <div class="bg-secondary-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block rounded" style="max-height:135px;" src="' . S3_URL . $latestuploads[5]['username'] . '/' . $latestuploads[5]['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $latestuploads[5]['video_title'] . '</p>
                    <p class="fs-6 lh-1 text-secondary-emphasis">
                    <span style="display:inline-block;" class="text-danger fw-bold">' . $latestuploads[5]['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($latestuploads[5]['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $latestuploads[5]['likes'] . ' likes</span> · 
                    <span style="display:inline-block;">' . $latestuploads[5]['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
</div>

';

$popularvidshtml = '

<div class="row">
    <div class="col-md-4 text-white mb-3 vidhov">
        <a class="text-decoration-none text-white" href="/u/' . $popularvids[0]['username'] . '/' . $popularvids[0]['id'] . '">
            <div class="bg-secondary-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block rounded" style="max-height:135px;" src="' . S3_URL . $popularvids[0]['username'] . '/' . $popularvids[0]['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $popularvids[0]['video_title'] . '</p>
                    <p class="fs-6 lh-1 text-secondary-emphasis">
                    <span style="display:inline-block;" class="text-danger fw-bold">' . $popularvids[0]['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($popularvids[0]['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $popularvids[0]['likes'] . ' likes</span> · 
                    <span style="display:inline-block;">' . $popularvids[0]['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
    <div class="col-md-4 text-white mb-3 vidhov">
        <a class="text-decoration-none text-white" href="/u/' . $popularvids[1]['username'] . '/' . $popularvids[1]['id'] . '">
            <div class="bg-secondary-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block rounded" style="max-height:135px;" src="' . S3_URL . $popularvids[1]['username'] . '/' . $popularvids[1]['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $popularvids[1]['video_title'] . '</p>
                    <p class="fs-6 lh-1 text-secondary-emphasis">
                    <span style="display:inline-block;" class="text-danger fw-bold">' . $popularvids[1]['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($popularvids[1]['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $popularvids[1]['likes'] . ' likes</span> · 
                    <span style="display:inline-block;">' . $popularvids[1]['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
    <div class="col-md-4 text-white mb-3 vidhov">
        <a class="text-decoration-none text-white" href="/u/' . $popularvids[2]['username'] . '/' . $popularvids[2]['id'] . '">
            <div class="bg-secondary-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block rounded" style="max-height:135px;" src="' . S3_URL . $popularvids[2]['username'] . '/' . $popularvids[2]['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $popularvids[2]['video_title'] . '</p>
                    <p class="fs-6 lh-1 text-secondary-emphasis">
                    <span style="display:inline-block;" class="text-danger fw-bold">' . $popularvids[2]['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($popularvids[2]['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $popularvids[2]['likes'] . ' likes</span> · 
                    <span style="display:inline-block;">' . $popularvids[2]['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
</div>
<div class="row">
    <div class="col-md-4 text-white mb-3 vidhov">
        <a class="text-decoration-none text-white" href="/u/' . $popularvids[3]['username'] . '/' . $popularvids[3]['id'] . '">
            <div class="bg-secondary-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block rounded" style="max-height:135px;" src="' . S3_URL . $popularvids[3]['username'] . '/' . $popularvids[3]['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $popularvids[3]['video_title'] . '</p>
                    <p class="fs-6 lh-1 text-secondary-emphasis">
                    <span style="display:inline-block;" class="text-danger fw-bold">' . $popularvids[3]['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($popularvids[3]['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $popularvids[3]['likes'] . ' likes</span> · 
                    <span style="display:inline-block;">' . $popularvids[3]['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
    <div class="col-md-4 text-white mb-3 vidhov">
        <a class="text-decoration-none text-white" href="/u/' . $popularvids[4]['username'] . '/' . $popularvids[4]['id'] . '">
            <div class="bg-secondary-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block rounded" style="max-height:135px;" src="' . S3_URL . $popularvids[4]['username'] . '/' . $popularvids[4]['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $popularvids[4]['video_title'] . '</p>
                    <p class="fs-6 lh-1 text-secondary-emphasis">
                    <span style="display:inline-block;" class="text-danger fw-bold">' . $popularvids[4]['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($popularvids[4]['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $popularvids[4]['likes'] . ' likes</span> · 
                    <span style="display:inline-block;">' . $popularvids[4]['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
    <div class="col-md-4 text-white mb-3 vidhov">
        <a class="text-decoration-none text-white" href="/u/' . $popularvids[5]['username'] . '/' . $popularvids[5]['id'] . '">
            <div class="bg-secondary-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block rounded" style="max-height:135px;" src="' . S3_URL . $popularvids[5]['username'] . '/' . $popularvids[5]['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $popularvids[5]['video_title'] . '</p>
                    <p class="fs-6 lh-1 text-secondary-emphasis">
                    <span style="display:inline-block;" class="text-danger fw-bold">' . $popularvids[5]['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($popularvids[5]['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $popularvids[5]['likes'] . ' likes</span> · 
                    <span style="display:inline-block;">' . $popularvids[5]['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
</div>

';

$populartagshtml = '';
foreach ($alltags as $tag => $count) {
    $populartagshtml .= '
    
    <div class="row">
        <div class="col-8 text-white mb-3">
            <a class="link-danger" href="/search/?t=' . $tag . '">' . $tag . '</a>
        </div>
        <div class="col-4 text-white mb-3">
            ' . $count . ' videos
        </div>
    </div>
    
    ';
}

$recentusershtml = '

<div class="row">
    <div class="col-8 text-white mb-3">
        <a class="link-danger" href="/u/' . $recentuserlist[0]['username'] . '">' . $recentuserlist[0]['username'] . '</a>
    </div>
    <div class="col-4 text-white mb-3">
        ' . $recentuserlist[0]['uploads'] . ' uploads
    </div>
</div>
<div class="row">
    <div class="col-8 text-white mb-3">
        <a class="link-danger" href="/u/' . $recentuserlist[1]['username'] . '">' . $recentuserlist[1]['username'] . '</a>
    </div>
    <div class="col-4 text-white mb-3">
        ' . $recentuserlist[1]['uploads'] . ' uploads
    </div>
</div>
<div class="row">
    <div class="col-8 text-white mb-3">
        <a class="link-danger" href="/u/' . $recentuserlist[2]['username'] . '">' . $recentuserlist[2]['username'] . '</a>
    </div>
    <div class="col-4 text-white mb-3">
        ' . $recentuserlist[2]['uploads'] . ' uploads
    </div>
</div>
<div class="row">
    <div class="col-8 text-white">
        <a class="link-danger" href="/u/' . $recentuserlist[3]['username'] . '">' . $recentuserlist[3]['username'] . '</a>
    </div>
    <div class="col-4 text-white">
        ' . $recentuserlist[3]['uploads'] . ' uploads
    </div>
</div>

';

$statisticshtml = '

<div class="row">
    <div class="col-8 text-danger mb-3">
        Uploads
    </div>
    <div class="col-4 text-white mb-3">
        ' . $stats['totaluploads'] . '
    </div>
</div>
<div class="row">
    <div class="col-8 text-danger mb-3">
        Online users
    </div>
    <div class="col-4 text-white mb-3">
        ' . $stats['activeusers'] . '
    </div>
</div>
<div class="row">
    <div class="col-8 text-danger mb-3">
        Total users
    </div>
    <div class="col-4 text-white mb-3">
        ' . $stats['totalusers'] . '
    </div>
</div>
<div class="row">
    <div class="col-8 text-danger mb-3">
        Total views
    </div>
    <div class="col-4 text-white mb-3">
        ' . $stats['totalviews'] . '
    </div>
</div>
<div class="row">
    <div class="col-8 text-danger">
        Queued uploads
    </div>
    <div class="col-4 text-white">
        ' . $stats['queuelength'] . '
    </div>
</div>

';

?>
<html data-bs-theme="dark" lang="en">
<head>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Z5Q13GMGXG"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        gtag('config', 'G-Z5Q13GMGXG');
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <title>Toypics | Home</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .vidhov {
            cursor: pointer;
            transition: all 0.2s cubic-bezier(.46, .03, .52, .96);
        }

        .vidhov:hover {
            transform: scale(0.9);
        }
    </style>
</head>
<body class="bg-body text-body">
<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/header.php"); ?>
<div class="container p-3">
    <div class="row">
        <div class="col-md-8">
            <div class="row px-2 mb-3">
                <div class="col-12 bg-black p-3 shadow text-white rounded">
                    <h3 class="text-danger">We're back, baby!</h3>
                    <p>Toypics has gotten a complete refresh, built from the ground up and on brand-new systems!</p>
                    <p>We are still working on migrating over old content and adding new features, check out
                        <a class="link-danger" href="/migration">this page</a>
                        for more information.</p>
                    <p>Follow the development and report issues on telegram:
                        <a class="link-danger" href="https://t.me/toypics">Toypics Telegram</a>
                    </p>

                </div>
                <div class="col-12 bg-danger p-3 mt-3 text-white rounded-top">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h4><i class="bi bi-camera-video"></i> New videos</h4>
                        </div>
                        <div class="col-6 text-end">
                            <a class="fw-bold btn btn-outline-light" href="/search/?p=1&o=3"><i class="bi bi-plus"></i>
                                More</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 bg-black pt-3 rounded-bottom">
                    <?php echo $newestvidshtml; ?>
                </div>
            </div>
            <div class="row px-2 mb-3">
                <div class="col-12 bg-danger p-3 text-white rounded-top">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h4><i class="bi bi-fire"></i> Top 100 videos</h4>
                        </div>
                        <div class="col-6 text-end">
                            <a class="fw-bold btn btn-outline-light" href="/search/?p=1&o=1"><i class="bi bi-plus"></i>
                                More</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 bg-black pt-3 rounded-bottom">
                    <?php echo $popularvidshtml; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row px-2 mb-3">
                <div class="col-12 bg-danger p-3 text-white rounded-top">
                    <div class="row align-items-center">
                        <div class="col-12">
                            <h4><i class="bi bi-bookmarks"></i> Popular tags</h4>
                        </div>
                        <!--
                        <div class="col-6 text-end">
                            <a class="fw-bold btn btn-outline-light" href="/tags"><i class="bi bi-plus"></i> More</a>
                        </div>
                        -->
                    </div>
                </div>
                <div class="col-12 bg-black py-3 px-5 rounded-bottom">
                    <?php echo $populartagshtml; ?>
                </div>
            </div>
            <div class="row px-2 mb-3">
                <div class="col-12 bg-danger p-3 text-white rounded-top">
                    <div class="row align-items-center">
                        <div class="col-12">
                            <h4><i class="bi bi-people"></i> Recently active</h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 bg-black py-3 px-5 pb-4 rounded-bottom">
                    <?php echo $recentusershtml; ?>
                </div>
            </div>
            <div class="row px-2 mb-3">
                <div class="col-12 bg-danger p-3 text-white rounded-top">
                    <div class="row align-items-center">
                        <div class="col-12">
                            <h4><i class="bi bi-people"></i> Site stats</h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 bg-black py-3 px-5 pb-4 rounded-bottom">
                    <?php echo $statisticshtml; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
</body>
</html>