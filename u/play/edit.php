<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/func-filenames.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/func-procthumb.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");

#Handle setting change posts
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    #Get current data from db
    $videodata = getVideoDataID($link, $subpage);
    (string)$filehash = $videodata['filehash'];
    $tl = getVideoTagsHash($link, $filehash);

    #Check that uploader is the logged-in user
    if ($_SESSION['username'] != $videodata['username']) {
        leavePage();
    }

    if (empty($_POST['tags'])) {
        $_POST['tags'] = "";
    }

    #Sanitize post values
    $_POST['videodescription'] = preg_replace("/\\r\\n/", "", nl2br(trim($_POST['videodescription'])));
    array_walk_recursive($_POST, function (&$value) {
        $value = htmlentities($value, ENT_QUOTES);
    });

    #Validate title
    if (empty($_POST['videotitle'])) {
        $title_err = "Please select a name";
    } else {
        $video_title = $_POST['videotitle'];
    }

    #parse tags

    $seltags = $_POST['tags'];
    $seltags = substr($seltags, 1, -1);

    $exploded = explode(',', $seltags);
    $atags = array();

    foreach ($exploded as $token) {

        if (preg_match('{"value":"(.*)"}', html_entity_decode($token), $resp)) {
            array_push($atags, htmlentities(mb_strtolower(strval($resp[1]))));
        }


    }

    ###Oh boy, the tags handling section
    $newtags = $deltags = array();

    #Find new tags to create
    foreach ($atags as $giventag) {
        if (!in_array($giventag, $tl, TRUE)) {
            array_push($newtags, $giventag);
        }
    }

    #Create new tags
    if (!empty($newtags)) {
        foreach ($newtags as $newtag) {
            if (!checkTagExists($link, $newtag)) {
                #Tag is unique, so create it in `tags`
                createTag($link, $newtag);
            }
            #Create entry for `videotags`
            createVideotag($link, $newtag, $filehash);
        }
    }

    #Find tags to remove
    foreach ($tl as $giventag) {
        if (!in_array($giventag, $atags, TRUE)) {
            array_push($deltags, $giventag);
        }
    }

    #Remove old tags
    if (!empty($deltags)) {
        foreach ($deltags as $deltag) {
            if (countTagUses($link, $deltag) == 1) {
                #Tag is unique, so remove it from `tags`
                deleteTag($link, $deltag);
            }
            #Delete entry for `videotags`
            deleteVideoTag($link, $deltag, $filehash);
        }
    }

    #Process thumbnail if provided
    if (!empty($_FILES['thumbfile']['name'])) {
        $name = filter_filename($_FILES['thumbfile']['name']);
        $tmp_name = $_FILES['thumbfile']['tmp_name'];
        $position = strpos($name, ".");
        $fileextension = substr($name, $position + 1);
        $fileextension = strtolower($fileextension);
        $pfprenamed = $filehash . ".jpg";
        $finaldir = S3_LOCAL . $username . "/" . $pfprenamed;

        #Make user dir if needed
        if (!is_dir(S3_LOCAL . $username . '/')) {
            mkdir(S3_LOCAL . $username . '/');
        }

        if (filesize($tmp_name) > 15000000) {
            $file_err = "File is too big.";
        }

        if ($fileextension != "png" && $fileextension != "jpg" && $fileextension != "jpeg") {
            $file_err = "File is not a PNG or JPEG.";
        }

        if (empty($file_err)) {

            processThumbnail($tmp_name, $finaldir);

        }

    }

    #Update title and description
    updateVideoTitleDesc($link, $video_title, $_POST['videodescription'], $filehash);

}

#Get updated data from DB
$videodata = getVideoDataID($link, $subpage);
(string)$filehash = $videodata['filehash'];
$tl = getVideoTagsHash($link, $filehash);
$vtaglist = implode(',', $tl);

#Check that uploader is the logged-in user
if ($_SESSION['username'] != $videodata['username']) {
    leavePage();
}

#replace BRs with linebreaks in textfield
$videodata['video_desc'] = preg_replace("/&lt;br \/&gt;/", "\r\n", $videodata['video_desc']);

#Set src attributes
$videoURL = S3_URL . $videodata['username'] . "/" . $filehash . "_enc.mp4";
$thumbURL = S3_URL . $videodata['username'] . "/" . $filehash . ".jpg";

#Get current tag list and store to tagify
$tagifylist = getTagifyTags($link);
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
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css"/>
    <title>Toypics | Home</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        #op-vid {
            --op-accent-color: var(--bs-danger);
        }

        :root, .tagify__tag, .tagify {
            --tagify-dd-bg-color: var(--bs-dark);
            --tagify-dd-color-primary: var(--bs-danger);
            --tags-border-color: var(--bs-border-color);
            --tag-bg: var(--bs-danger);

        }
    </style>
</head>
<body class="bg-body text-body" style="min-height:100vh;">
<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/header.php"); ?>
<div class="container mt-3">
    <h3>Editing video id: <?php echo $videodata['id'] ?? ''; ?></h3>
    <div class="row">
        <div class="col-lg-6 mb-3">
            <div id="op-vid"></div>
        </div>
        <div class="col-lg-6 mb-3">
            <form id="form" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="videotitle" class="form-label">Video title</label>
                    <input type="text" class="form-control" name="videotitle" id="videotitle"
                           value="<?php echo $videodata['video_title'] ?? ''; ?>">
                </div>
                <div class="mb-3">
                    <label for="tags" class="form-label">Tags</label>
                    <input name="tags" type="text"
                           class="form-control"
                           id="tags" aria-describedby="tagsdec"
                           value="<?php echo $vtaglist ?? ''; ?>">
                </div>
                <div class="mb-3">
                    <label for="videodescription" class="form-label">Video description</label>
                    <textarea class="form-control" name="videodescription"
                              id="videodescription"><?php echo $videodata['video_desc'] ?? ''; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="thumbfile" class="form-label">Thumbnail</label>
                    <input class="form-control" type="file" name="thumbfile" id="thumbfile"
                           accept="image/png,image/jpeg,image/jpg">
                    <span>Max 15mb. Images will be automatically resized to 16:9</span>
                </div>
                <button id="sub" class="btn btn-danger" type="submit">Submit</button>
                <button id="spinner" class="btn btn-danger" type="button" disabled>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <span class="sr-only">Please wait...</span>
                </button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/ovenplayer/dist/ovenplayer.js"></script>
<script>
    // Initialize OvenPlayer
    const player = OvenPlayer.create('op-vid', {
        image: '<?php echo $thumbURL ?>',
        loop: true,
        showSeekControl: true,
        sources: [
            {
                label: 'MP4',
                // Set the type to 'mp4', 'webm' or etc
                type: 'mp4',
                file: '<?php echo $videoURL ?>'
            }
        ]
    });
</script>
<script>

    var input = document.querySelector('input[name="tags"]'),
        // init Tagify script on the above inputs
        tagify = new Tagify(input, {
            pattern: /^.{0,32}$/,
            whitelist: ["<?php echo $tagifylist ?>"],
            dropdown: {
                maxItems: 20,           // <- mixumum allowed rendered suggestions
                classname: "tags-look", // <- custom classname for this dropdown, so it could be targeted
                enabled: 0,             // <- show suggestions on focus
                closeOnSelect: false    // <- do not hide the suggestions dropdown once an item has been selected
            }
        })

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
<script>
    const spinner = document.querySelector('#spinner');
    const sub = document.querySelector('#sub');
    spinner.setAttribute("hidden", true);
    window.addEventListener('load', () => {
        const decisionsForm = document.querySelector('#form');
        // Capture submit event
        decisionsForm.addEventListener("submit", (event) => {
            // Enable spinner
            spinner.removeAttribute('hidden');
            sub.setAttribute("hidden", true);
        });
    });
</script>
</body>
</html>