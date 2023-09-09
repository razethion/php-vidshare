<?php
// Initialize the session
session_start();

#user url prefixes
$webprefix = "https://";
$twitterprefix = "https://twitter.com/";
$telegramprefix = "https://t.me/";
$fetlifeprefix = "https://fetlife.com/users/";

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");

#list latest uploads
$sql = "SELECT username,id,v.filehash,upload_date,video_title,views
FROM (
         SELECT filehash, sum(views) as views
         FROM videoviews
         GROUP BY filehash
     ) as v
         LEFT JOIN uploads u on v.filehash = u.filehash
WHERE processed = 1 AND username = ?
ORDER BY upload_date DESC
LIMIT 6";

if ($stmt = mysqli_prepare($link, $sql)) {

    mysqli_stmt_bind_param($stmt, "s", $username);

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
$sql = "SELECT username,id,v.filehash,upload_date,video_title,views
FROM (
         SELECT filehash, sum(views) as views
         FROM videoviews
         GROUP BY filehash
     ) as v
         LEFT JOIN uploads u on v.filehash = u.filehash
WHERE processed = 1 AND username = ?
ORDER BY views DESC
LIMIT 6";
if ($stmt = mysqli_prepare($link, $sql)) {

    mysqli_stmt_bind_param($stmt, "s", $username);

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
$sql = "select tag, count(tag) as count
from (
         select tag
         from videotags v
                  right join uploads u on v.filehash = u.filehash
         where v.filehash is not null
           AND username = ?
         order by id
     ) as a
group by tag
order by count desc";
if ($stmt = mysqli_prepare($link, $sql)) {

    mysqli_stmt_bind_param($stmt, "s", $username);

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

#Get current userdata
$sql = "SELECT prof_desc, links FROM userdata WHERE username = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "s", $username);

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $userprofdata = array(); // create a variable to hold the information
        while (($row = mysqli_fetch_assoc($result))) {
            $userprofdata = $row; // add the row in to the results (data) array
        }

        $userprofdata['prof_desc'] = preg_replace("/&lt;br \/&gt;/", "<br />", $userprofdata['prof_desc']);

    } else {
        echo "Something went wrong. Please try again later.";
    }
    // Close statement
    mysqli_stmt_close($stmt);
}

#newest vids html
$newestvidshtml = '';
$linecount = 0;
$resultcount = count($latestuploads);
foreach ($latestuploads as $list) {
    $linecount++;
    $titlestripped = $list['video_title'];
    if (strlen($titlestripped) > 37) {
        $titlestripped = substr($titlestripped, 0, 37);
        $titlestripped .= "...";
    }
    $open = 0;
    $close = 0;
    if (($linecount - 1) % 3 == 0) {
        $open = 1;
    }
    if ($linecount > 2) {
        if (($linecount) % 3 == 0) {
            $close = 1;
        }
    }
    if ($open) {
        $newestvidshtml .= '
        
        <div class="row">
        
        ';
    }
    $newestvidshtml .= '
    <div class="col-md-4 text-white mb-3">
        <a class="text-decoration-none text-white" href="/u/' . $list['username'] . '/' . $list['id'] . '">
            <div class="bg-light-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block" style="max-height:135px;" src="' . S3_URL . $list['username'] . '/' . $list['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $titlestripped . '</p>
                    <p class="fs-6 lh-1">
                    <span style="display:inline-block;">' . $list['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($list['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $list['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
    ';
    if ($close || $resultcount == $linecount) {
        $newestvidshtml .= '

        </div>
        
        ';
    }
}
if ($linecount == 0) {
    $newestvidshtml .= '
        
        <div class="row">
            <p>No uploads.</p>
        </div>
        
        ';
}

#popular vids html
$popularvidshtml = '';
$linecount = 0;
$resultcount = count($latestuploads);
foreach ($popularvids as $list) {
    $linecount++;
    $titlestripped = $list['video_title'];
    if (strlen($titlestripped) > 37) {
        $titlestripped = substr($titlestripped, 0, 37);
        $titlestripped .= "...";
    }
    $open = 0;
    $close = 0;
    if (($linecount - 1) % 3 == 0) {
        $open = 1;
    }
    if ($linecount > 2) {
        if (($linecount) % 3 == 0) {
            $close = 1;
        }
    }
    if ($open) {
        $popularvidshtml .= '
        
        <div class="row">
        
        ';
    }
    $popularvidshtml .= '
    <div class="col-md-4 text-white mb-3">
        <a class="text-decoration-none text-white" href="/u/' . $list['username'] . '/' . $list['id'] . '">
            <div class="bg-light-subtle p-2 rounded h-100">
                <div>
                    <img class="shadow img-fluid mb-2 mx-auto d-block" style="max-height:135px;" src="' . S3_URL . $list['username'] . '/' . $list['filehash'] . '.jpg" alt="thumb">
                    <p class="fw-bold lh-1">' . $titlestripped . '</p>
                    <p class="fs-6 lh-1">
                    <span style="display:inline-block;">' . $list['username'] . '</span> · 
                    <span style="display:inline-block;">' . datediff($list['upload_date']) . '</span> · 
                    <span style="display:inline-block;">' . $list['views'] . ' views</span>
                    </p>
                </div>
            </div>
       </a>
    </div>
    ';
    if ($close || $resultcount == $linecount) {
        $popularvidshtml .= '

        </div>
        
        ';
    }
}
if ($linecount == 0) {
    $popularvidshtml .= '
        
        <div class="row">
            <p>No uploads.</p>
        </div>
        
        ';
}
$populartagshtml = null;
foreach ($alltags as $tag => $count) {
    $populartagshtml .= '
    
    <div class="row">
    <div class="col-8 text-white mb-3">
        <a class="link-danger" href="/search/?t=' . $tag . '&s=' . $username . '">' . $tag . '</a>
    </div>
    <div class="col-4 text-white mb-3">
        ' . $count . ' videos
    </div>
    </div>
    
    ';
}
if (empty($populartagshtml)) {
    $populartagshtml = '
    
    <div class="row">
            <p>No tags.</p>
    </div>
    
    ';
}

$statisticshtml = '

<div class="row">
    <div class="col-8 text-white mb-3">
        Uploads
    </div>
    <div class="col-4 text-white mb-3">
        num
    </div>
</div>
<div class="row">
    <div class="col-8 text-white mb-3">
        Tags
    </div>
    <div class="col-4 text-white mb-3">
        num
    </div>
</div>
<div class="row">
    <div class="col-8 text-white">
        Video views
    </div>
    <div class="col-4 text-white">
        num
    </div>
</div>

';

#get user pic from s3 if set
$upPicURL = S3_URL . $username . "/" . "pfp.jpg";
if (@fopen($upPicURL, 'r')) {
    $uplpichtml = '
        
            <img style="object-fit: cover;" src="' . $upPicURL . '" width="50" height="50" class="rounded-circle" alt="pic">
        
        ';
} else {
    $uplpichtml = '
        
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="#dc3545" class="bi bi-person-circle" viewBox="0 0 16 16">
            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
        </svg>
        
        ';
}

#parse user links
$userprofarr = json_decode($userprofdata['links'], true);
$linksectionhtml = "";
if (!empty($userprofarr['web'])) {
    $userweburl = $webprefix . $userprofarr['web'];
    $linksectionhtml .= '
    <a target="_blank" href="' . $userweburl . '" class="text-decoration-none">
        <div class="me-2 mb-3 border border-danger rounded d-inline-block">
            <h6 class="lh-1 p-2 m-0"><i class="bi bi-link-45deg"></i> ' . $userprofarr['webdesc'] . '</h6>
        </div>
    </a>
    ';
}
if (!empty($userprofarr['twitter'])) {
    $userweburl = $twitterprefix . $userprofarr['twitter'];
    $linksectionhtml .= '
    <a target="_blank" href="' . $userweburl . '" class="text-decoration-none">
        <div class="me-2 mb-3 border border-danger rounded d-inline-block">
            <h6 class="lh-1 p-2 m-0"><i class="bi bi-twitter"></i> ' . $userprofarr['twitter'] . '</h6>
        </div>
    </a>
    ';
}
if (!empty($userprofarr['telegram'])) {
    $userweburl = $telegramprefix . $userprofarr['telegram'];
    $linksectionhtml .= '
    <a target="_blank" href="' . $userweburl . '" class="text-decoration-none">
        <div class="me-2 mb-3 border border-danger rounded d-inline-block">
            <h6 class="lh-1 p-2 m-0"><i class="bi bi-telegram"></i> ' . $userprofarr['telegram'] . '</h6>
        </div>
    </a>
    ';
}
if (!empty($userprofarr['discord'])) {
    $linksectionhtml .= '
        <div class="me-2 mb-3 border border-light-subtle rounded d-inline-block">
            <h6 class="lh-1 p-2 m-0"><i class="bi bi-discord"></i> ' . $userprofarr['discord'] . '</h6>
        </div>
    ';
}
if (!empty($userprofarr['fetlife'])) {
    $userweburl = $fetlifeprefix . $userprofarr['fetlife'];
    $linksectionhtml .= '
    <a target="_blank" href="' . $userweburl . '" class="text-decoration-none">
        <div class="me-2 mb-3 border border-danger rounded d-inline-block">
            <h6 class="lh-1 p-2 m-0">
                <img style="filter: contrast(0) brightness(255)" width="auto" height="16rem"
                    src="/assets/fetlife.svg" alt="fetlife"/> ' . $userprofarr['fetlife'] . '</h6>
        </div>
    </a>
    ';
}

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
    <title>Toypics | <?php echo $username ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-body text-body">
<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/header.php"); ?>
<div class="container p-3">
    <div class="row">
        <div class="col-md-8">
            <div class="row px-2 mb-3">
                <div class="col-12 bg-black px-3 pt-3 text-white">
                    <div class="row">
                        <div class="col-auto pe-0 me-3">
                            <div class="row lh-1 h-100 align-items-center">
                                <?php echo $uplpichtml ?>
                            </div>
                        </div>
                        <div class="col-auto ps-0">
                            <div class="row lh-1 h-100 align-items-center">
                                <h3 class="text-danger"><?php echo $username ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="<?php echo (!empty($userprofdata['prof_desc'])) ? "mt-2" : ''; ?>">
                        <?php echo (!empty($userprofdata['prof_desc'])) ? $userprofdata['prof_desc'] : ''; ?>
                    </div>
                    <div class="mt-3">
                        <?php echo $linksectionhtml; ?>
                    </div>
                </div>
                <div class="col-12 bg-danger p-3 mt-3 text-white">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h4><i class="bi bi-camera-video"></i> New videos</h4>
                        </div>
                        <div class="col-6 text-end">
                            <a class="fw-bold btn btn-outline-light"
                               href="/search/?p=1&o=3&s=<?php echo $username ?>"><i class="bi bi-plus"></i>
                                More</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 bg-black pt-3">
                    <?php echo $newestvidshtml; ?>
                </div>
            </div>
            <div class="row px-2 mb-3">
                <div class="col-12 bg-danger p-3 text-white">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h4><i class="bi bi-fire"></i> Popular videos</h4>
                        </div>
                        <div class="col-6 text-end">
                            <a class="fw-bold btn btn-outline-light"
                               href="/search/?p=1&o=1&s=<?php echo $username ?>"><i class="bi bi-plus"></i>
                                More</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 bg-black pt-3">
                    <?php echo $popularvidshtml; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-grid gap-2 mb-3">
                <a href="/u/<?php echo $username ?>/likes" class="btn btn-outline-danger"><?php echo $username ?>'s
                    liked videos</a>
            </div>
            <div class="row px-2 mb-3">
                <div class="col-12 bg-danger p-3 text-white">
                    <div class="row align-items-center">
                        <div class="col-12">
                            <h4><i class="bi bi-bookmarks"></i> User's tags</h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 bg-black py-3 px-5">
                    <?php echo $populartagshtml; ?>
                </div>
            </div>
            <!--
            <div class="row px-2 mb-3">
                <div class="col-12 bg-danger p-3 text-white">
                    <div class="row align-items-center">
                        <div class="col-12">
                            <h4><i class="bi bi-graph-up-arrow"></i> Statistics</h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 bg-black py-3 px-5">
                    <?php echo $statisticshtml; ?>
                </div>
            </div>
            -->
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
</body>
</html>