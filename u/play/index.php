<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");

#redirect if subpage not set
if (!isset($subpage)) {
    leavePage();
}

#get video data
$sql = "SELECT u.*, v.views, vl.likes
FROM (
    SELECT filehash, SUM(views) AS views
    FROM videoviews
    GROUP BY filehash
) AS v
LEFT JOIN uploads AS u ON v.filehash = u.filehash
LEFT JOIN (
    SELECT filehash, COUNT(username) AS likes
    FROM videolikes
    GROUP BY filehash
) AS vl ON v.filehash = vl.filehash
WHERE u.ID = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "s", $subpage);

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $videodata = mysqli_fetch_assoc($result);

        if (!mysqli_num_rows($result) == 1) {
            leavePage();
        }
        if ($videodata['processed'] !== 1) {
            leavePage();
        }
    } else {
        echo "Something went wrong. Please try again later.";
    }

    // Close statement
    mysqli_stmt_close($stmt);
}

#redirect to correct user if ID accessed from wrong username
if ($username != $videodata['username']) {
    header("Location: /u/" . $videodata['username'] . "/" . $subpage);
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_SESSION['username'])) {
        header("Location: /login");
        die();
    }

    if (!empty($_POST['comment']) && !empty($_SESSION['username'])) {

        #Sanitize post values
        $_POST['comment'] = preg_replace("/\\r\\n/", "", nl2br(trim($_POST['comment'])));
        array_walk_recursive($_POST, function (&$value) {
            $value = htmlentities($value, ENT_QUOTES);
        });

        createVideoComment($link, $videodata['filehash'], $_SESSION['username'], $_POST['comment']);
    }
}

#get comments
$videocomments = getVideoComments($link, $videodata['filehash']);

#create comments html
if (!empty($videocomments)) {
    $videocommentshtml = "";
    foreach ($videocomments as $comment) {
        #get user pic from s3 if set
        $cPicURL = S3_URL . $comment["username"] . "/" . "pfp.jpg";
        if (@fopen($cPicURL, 'r')) {
            $cpichtml = '
        
            <img style="object-fit: cover;" src="' . $cPicURL . '" width="50" height="50" class="rounded-circle" alt="pic">
        
        ';
        } else {
            $cpichtml = '
        
        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="#dc3545" class="bi bi-person-circle" viewBox="0 0 16 16">
            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
        </svg>
        
        ';
        }
        if (isset($_SESSION['username'])) {
            if ($_SESSION['username'] == $comment['username']) {
                $deleteCommentHtml = "
                            <div class=\"col-auto ms-auto\">
                                <a class=\"btn btn-outline-danger\" href=\"\">
                                    <i class=\"bi bi-trash3\"></i> Delete
                                </a>
                            </div>
                            ";
            } else {
                $deleteCommentHtml = "";
            }
        } else {
            $deleteCommentHtml = "";
        }

        $videocommentshtml .= "
        
        <div class=\"row align-items-center\">
                            <a class=\"text-decoration-none d-inline-flex w-auto\"
                               href=\"/u/" . $comment["username"] . "\">
                                <div class=\"col-auto me-2\">
                                    " . $cpichtml . "
                                </div>
                                <div class=\"col-auto\">
                                    <div class=\"row lh-1 h-100 align-items-center\">
                                        <span class=\"text-body w-auto m-0 p-0 ms-3 fw-bold\">" . $comment["username"] . "</span>
                                        <span class=\"w-auto m-0 p-0 ms-2 text-body-secondary\"> · " . datediff($comment["comment_date"]) . "</span>
                                    </div>
                                </div>
                            </a>
                            " . $deleteCommentHtml . "
                        </div>
                        <div class=\"row mt-2 mb-4\">
                            <span>" . $comment["comment_data"] . "</span>
                        </div>
        
        ";

    }
}

#get user data
$sql = "SELECT * FROM users INNER JOIN userdata u on users.username = u.username WHERE users.username = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "s", $videodata['username']);

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $userdata = mysqli_fetch_assoc($result);
    } else {
        echo "Something went wrong. Please try again later.";
    }

    // Close statement
    mysqli_stmt_close($stmt);
}

$videoURL = S3_URL . $videodata['username'] . "/" . $videodata['filehash'] . "_enc.mp4";
$thumbURL = S3_URL . $videodata['username'] . "/" . $videodata['filehash'] . ".jpg";
$upPicURL = S3_URL . $videodata['username'] . "/" . "pfp.jpg";

#get video tags
$sql = "SELECT tag FROM videotags WHERE filehash = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "s", $videodata['filehash']);

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $videotags = array(); // create a variable to hold the information
        while (($row = mysqli_fetch_assoc($result))) {
            $videotags[] = $row['tag']; // add the row in to the results (data) array
        }
    } else {
        echo "Something went wrong. Please try again later.";
    }

    // Close statement
    mysqli_stmt_close($stmt);
}
$videotagshtml = '';
foreach ($videotags as $tag) {
    $videotagshtml .= '    
    <a href="/search/?t=' . $tag . '" class="badge text-bg-danger text-decoration-none">' . $tag . '</a>
    ';
}

#get random videos
$sql = "SELECT u.username, u.filehash, upload_date, video_title, id, views
FROM (
         SELECT filehash, sum(views) as views
         FROM videoviews
         GROUP BY filehash
     ) as v
         LEFT JOIN uploads u on v.filehash = u.filehash
WHERE processed = 1
ORDER BY RAND()
LIMIT 10";
if ($stmt = mysqli_prepare($link, $sql)) {

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $suggestedlist = array(); // create a variable to hold the information
        while (($row = mysqli_fetch_assoc($result))) {
            $suggestedlist[] = $row; // add the row in to the results (data) array
        }
    } else {
        echo "Something went wrong. Please try again later.";
    }

    // Close statement
    mysqli_stmt_close($stmt);
}
$suggestedlisthtml = '';
foreach ($suggestedlist as $list) {
    $titlestripped = $list['video_title'];
    if (strlen($titlestripped) > 37) {
        $titlestripped = substr($titlestripped, 0, 37);
        $titlestripped .= "...";
    }
    $suggestedlisthtml .= '
    
        <a class="text-decoration-none" href="/u/' . $list['username'] . '/' . $list['id'] . '">
            <div class="row mx-0 mb-2">
                <div class="col-6 bg-black align-items-center p-0" style="max-height:94px; max-width: 164px;">
                    <img class="img-fluid mx-auto d-block" style="height:94px;width: 164px; object-fit: contain;"
                         src="' . S3_URL . $list['username'] . "/" . $list['filehash'] . '.jpg"
                         alt="thumb">
                </div>
                <div class="col-6 d-inline">
                    <p class="lh-1 fw-bold text-danger mb-1 text-wrap">' . $titlestripped . '</p>
                    <p class="lh-1 text-body mb-1">' . $list['username'] . '</p>
                    <p class="lh-1 text-body">' . $list['views'] . ' views · ' . datediff($list['upload_date']) . '</p>
                </div>
            </div>
        </a>
    
    ';
}

#get viewcount
$sql = "SELECT sum(views) FROM videoviews WHERE filehash = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $videodata['filehash']);
    if (mysqli_stmt_execute($stmt)) {

        $result = mysqli_stmt_get_result($stmt);

        $viewcount = array(); // create a variable to hold the information
        while (($row = mysqli_fetch_assoc($result))) {
            $viewcount[] = $row; // add the row in to the results (data) array
        }
    } else {
        echo "Something went wrong. Please try again later.";
    }

    // Close statement
    mysqli_stmt_close($stmt);
}

#get uploader pic
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

#Display edit button for uploader
if (isset($_SESSION['username'])) {
    if ($_SESSION['username'] == $userdata['username']) {
        $editbuttonhtml = '
            <div class="col-auto mb-2">
                <a class="btn btn-danger" href="' . $subpage . '/edit">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
            </div>
        ';
    } else {
        $editbuttonhtml = '
            <div class="col-auto mb-2">
                <a class="btn btn-danger" href="' . $subpage . '/tagedit">
                    <i class="bi bi-tags-fill"></i> Tag editor
                </a>
            </div>
        ';
    }
}

#make BR lines actually display
$videodata['video_desc'] = preg_replace("/&lt;br \/&gt;/", "<br />", $videodata['video_desc']);

function encodedViewPath(string $filehash): string
{
    return openssl_encrypt($filehash, "AES-128-CTR", "T0yP1c$", null, "1234567891011122");
}

if (isset($_SESSION['username']) && $_SESSION['loggedin'] === true) {
    $liked = checkIfLiked($link, $videodata['filehash'], $_SESSION['username']);
    $attrs = $liked ? 'btn-light active' : 'btn-outline-danger';
    $likebutton = '
        <a id="likebutton" href=""
           class="btn ' . $attrs . '"
           role="button">
            <i class="bi bi-hand-thumbs-up"></i> <span id="buttonlikes"></span>
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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Toypics | <?php echo $videodata['video_title'] ?></title>
    <meta property="og:site_name" content="Toypics">
    <meta property="og:url" content="https://dev.toypics.net/">
    <meta property="og:title"
          content="<?php echo $videodata['video_title'] ?>">
    <meta property="og:image" content="<?php echo $thumbURL ?>">

    <meta property="og:description"
          content="<?php echo $videodata['video_desc'] ? $videodata['video_desc'] : 'Uploaded by ' . $userdata['username'] ?>">

    <meta property="og:type" content="video">
    <meta property="og:video:url" content="<?php echo $videoURL ?>">
    <meta property="og:video:secure_url" content="<?php echo $videoURL ?>">

    <meta name="twitter:card" content="player">
    <meta name="twitter:url" content="https://dev.toypics.net">
    <meta name="twitter:title"
          content="<?php echo $videodata['video_title'] ?>">
    <meta name="twitter:description"
          content="<?php echo $videodata['video_desc'] ? $videodata['video_desc'] : 'Uploaded by ' . $userdata['username'] ?>">
    <meta name="twitter:image" content="<?php echo $thumbURL ?>">

    <style>
        #op-vid {
            --op-accent-color: var(--bs-danger);
        }
    </style>
</head>
<body class="bg-body text-body">
<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/header.php"); ?>
<div class="container mt-3">
    <div class="row">
        <div class="col-lg-8">
            <div id="oploader" class="bg-black" style="
                background-image: url('<?php echo $thumbURL ?>');
                height: auto;
                background-size:cover;
                ">
                <div class="ratio ratio-16x9" style="background:#000000A0;">
                    <div class="w-auto h-auto position-absolute top-50 start-50 translate-middle">
                        <div class="spinner-border shadow" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div id="opwrapper" class="d-none">
                <div id="op-vid"></div>
            </div>
            <div class="mt-2">
                <h4><?php echo $videodata['video_title'] ?></h4>
                <div class="mt-3">
                    <?php echo $videotagshtml ?>
                </div>
                <div class="row mt-3 pb-5">
                    <div class="col-4">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="row mb-2">
                                    <a class="text-decoration-none d-inline-flex"
                                       href="/u/<?php echo $userdata['username'] ?>">
                                        <div class="col-auto me-2">
                                            <?php echo $uplpichtml ?>
                                        </div>
                                        <div class="col-auto">
                                            <div class="row lh-1 h-100 align-items-center">
                                                <h5 class="text-danger"><?php echo $userdata['username'] ?></h5>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <!--
                            <div class="col-auto">
                                <a class="btn btn-outline-danger" href="followuser">
                                    Follow
                                </a>
                            </div>
                            -->
                        </div>
                    </div>
                    <div class="col-8">
                        <div class="row justify-content-end align-items-center h-100">
                            <?php echo $editbuttonhtml ?? ''; ?>
                            <div class="col-auto mb-2">
                                <?php echo $likebutton ?? '' ?>
                            </div>
                            <div class="col-auto mb-2">
                                <a class="btn btn-outline-danger" onclick="shareBtn();">
                                    <i class="bi bi-share"></i> Copy URL
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="my-1">
                        <hr>
                    </div>
                    <div class="col-12">
                        <div class="rounded bg-light-subtle p-2">
                            <div class="row">
                                <div class="col-12">
                                    <span>
                                        <?php echo datediff($videodata['upload_date']); ?>
                                        ·
                                        <?php echo $videodata['views'] ?> views
                                    </span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 <?php echo $videodata['video_desc'] ? 'mt-2' : '' ?>">
                                    <p class="text-light m-0">
                                        <?php echo $videodata['video_desc']; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-3" id="addcomment">
                        <form id="form" method="post" enctype="multipart/form-data">
                            <div class="row align-items-end">
                                <div class="col-9">
                                    <label for="comment">Leave a comment</label>
                                    <textarea class="form-control" name="comment"
                                              id="comment"></textarea>
                                </div>
                                <div class="col-3">
                                    <button id="submit" class="btn btn-danger w-100 h-100" type="submit">Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php echo $videocommentshtml ?? "" ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <h3 class="mb-3">Suggested videos</h3>
            <?php echo $suggestedlisthtml ?>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/ovenplayer/dist/ovenplayer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script>
    $(document).ready(function () {
        // Event listener for button toggle
        const button = $('#likebutton');
        const likestext = $('#buttonlikes');
        // Set button likes
        let likes = <?php echo $videodata['likes'] ?? '0' ?>;
        likestext.text(likes + " likes");
        button.click(function (e) {
            e.preventDefault();
            // Determine the new state based on the current state
            const newState = button.hasClass('active') ? 'inactive' : 'active';

            // Make AJAX request to update the state
            $.ajax({
                url: '/includes/likevideo.php', // Replace with your API URL
                method: 'POST',
                data: {
                    state: newState,
                    filehash: '<?php echo $videodata['filehash']; ?>',
                    username: '<?php echo $_SESSION['username']; ?>'
                },
                success: function (response) {
                    // Update button state based on API response
                    if (newState === 'active') {
                        likes++;
                        likestext.text(likes + " likes");
                        button.addClass('active');
                        button.removeClass('inactive');
                        button.addClass('btn-light');
                        button.removeClass('btn-outline-danger');
                    } else {
                        likes--;
                        likestext.text(likes + " likes");
                        button.addClass('inactive');
                        button.removeClass('active');
                        button.addClass('btn-outline-danger');
                        button.removeClass('btn-light');
                    }
                },
                error: function (err) {
                    console.error('Error updating like state:', err);
                }
            });
        });
    });
</script>
<script type="text/javascript">
    function videoPlay() {
        $.ajax({
            url: "<?php echo "https://dev.toypics.net/includes/vvid.php?" . encodedViewPath($videodata['filehash']); ?>",
            type: 'get'
        });
    }
</script>
<script>
    // Initialize OvenPlayer
    const player = OvenPlayer.create('op-vid', {
        image: '<?php echo $thumbURL ?>',
        loop: true,
        showSeekControl: true,
        expandFullScreenUI: false,
        sources: [
            {
                label: 'MP4',
                // Set the type to 'mp4', 'webm' or etc
                type: 'mp4',
                file: '<?php echo $videoURL ?>'
            }
        ]
    });

    let blockrender = false;
    player.on('metaChanged', function (event) {
        if (blockrender) {
            return;
        }

        console.log(event.duration);

        if (parseInt(event.duration) >= "0") {
            blockrender = true;
            $("#oploader").addClass('d-none');
            $("#opwrapper").removeClass('d-none');
        }

    });

    player.on('stateChanged', function (event) {
        console.log(event.newstate);
        if ((event.prevstate === 'loading' || event.prevstate === 'idle') && event.newstate === 'playing') {
            videoPlay();
            gtag('event', 'video_played');
        }
    });

</script>
<script>
    function shareBtn() {

        // Copy the text inside the text field
        navigator.clipboard.writeText("<?php echo "https://dev.toypics.net" . $_SERVER['REQUEST_URI'] ?>");

        // Alert the copied text
        alert("Link copied to clipboard");
    }
</script>
</body>
</html>