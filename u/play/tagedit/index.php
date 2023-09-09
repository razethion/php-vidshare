<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/func-filenames.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/func-procthumb.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");

#Get updated data from DB
$videodata = getVideoDataID($link, $subpage);

#Check that uploader is the logged-in user
if ($_SESSION['username'] == $videodata['username']) {
    leavePage();
}

(string)$filehash = $videodata['filehash'];


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    array_walk_recursive($_POST, function (&$value) {
        $value = htmlentities($value, ENT_QUOTES);
    });

    // Process tags in post
    $seltags = $_POST['tags'];
    $seltags = substr($seltags, 1, -1);
    $exploded = explode(',', $seltags);
    $tagfield = array();
    foreach ($exploded as $token) {
        if (preg_match('{"value":"(.*)"}', html_entity_decode($token), $resp)) {
            $tagfield[] = htmlentities(mb_strtolower(strval($resp[1])));
        }
    }

    //Get tags from pending
    $ptag = getPendingTagsHash($link, $filehash);
    $addtags = $removetags = array();
    foreach ($ptag as $tag) {
        if ($tag['remove'] == 0) {
            $addtags[] = $tag['tag'];
        }
    }
    foreach ($ptag as $tag) {
        if ($tag['remove'] == 1) {
            $removetags[] = $tag['tag'];
        }
    }

    //Get current tags
    $currenttags = getVideoTagsHash($link, $filehash);

    $checkedtags = array();

    // For all the tags provided
    foreach ($tagfield as $tag) {
        if (!(in_array($tag, $currenttags) || in_array($tag, $addtags))) {
            // Tag is not in any array, so it should be added as pending
            suggestTagAction($link, $tag, $filehash, $_SESSION['username'], 0);
        } elseif (in_array($tag, $currenttags) && in_array($tag, $removetags)) {
            // Tag was removed but should be added back
            suggestTagAction($link, $tag, $filehash, $_SESSION['username'], 0);
        }
        $checkedtags[] .= $tag;
    }

    // For all the tags on the video
    foreach ($currenttags as $tag) {
        if (in_array($tag, $checkedtags)) {
            continue;
        }
        if (!in_array($tag, $tagfield)) {
            // Add the tag as a removal
            suggestTagAction($link, $tag, $filehash, $_SESSION['username'], 1);
        }
        $checkedtags[] .= $tag;
    }

    foreach ($removetags as $tag) {
        if (in_array($tag, $checkedtags)) {
            continue;
        }
        if (in_array($tag, $tagfield)) {
            // Change the tag as pending addition
            suggestTagAction($link, $tag, $filehash, $_SESSION['username'], 0);
        }
        $checkedtags[] .= $tag;
    }

    foreach ($addtags as $tag) {
        if (in_array($tag, $checkedtags)) {
            continue;
        }
        if (!in_array($tag, $tagfield)) {
            // Change the tag as pending removal
            suggestTagAction($link, $tag, $filehash, $_SESSION['username'], 1);
        }
        $checkedtags[] .= $tag;
    }

}

$tl = getVideoTagsHash($link, $filehash);

$videotagshtml = $removetagshtml = $addtagshtml = '';
foreach ($tl as $tag) {
    $videotagshtml .= '    
    <a href="/search/?t=' . $tag . '" class="badge text-bg-light text-decoration-none">' . $tag . '</a>
    ';
}

$pendingtags = getPendingTagsHash($link, $filehash);
foreach ($pendingtags as $pendingtag) {
    $tl[] .= $pendingtag['tag'];

    if ($pendingtag['remove']) {
        $removetagshtml .= '    
            <a href="/search/?t=' . $pendingtag['tag'] . '" class="badge text-bg-danger text-decoration-none">' . $pendingtag['tag'] . '</a>
        ';
    } else {
        $addtagshtml .= '    
            <a href="/search/?t=' . $pendingtag['tag'] . '" class="badge text-bg-success text-decoration-none">' . $pendingtag['tag'] . '</a>
        ';
    }

}

$vtaglist = implode(',', $tl);

#Set src attributes
$videoURL = S3_URL . $videodata['username'] . "/" . $filehash . "_enc.mp4";
$thumbURL = S3_URL . $videodata['username'] . "/" . $filehash . ".jpg";

#Get current tag list and store to tagify
$tagifylist = getTagifyTags($link);

#make BR lines actually display
$videodata['video_desc'] = preg_replace("/&lt;br \/&gt;/", "<br />", $videodata['video_desc']);
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
    <title>Toypics | Tag edit</title>
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
    <h3>Tag suggestion</h3>
    <div class="row">
        <div class="col-lg-6 mb-3">
            <div id="op-vid"></div>
        </div>
        <div class="col-lg-6 mb-3">
            <form id="form" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <h6>Video title</h6>
                    <p><?php echo $videodata['video_title'] ?? ''; ?></p>
                </div>
                <div class="mb-3">
                    <h6>Video description</h6>
                    <div><?php echo $videodata['video_desc'] ?? ''; ?></div>
                </div>
                <div class="mb-3">
                    <h6>Tag editor</h6>
                    <input name="tags" type="text"
                           class="form-control"
                           id="tags" aria-describedby="tagsdec"
                           value="<?php echo $vtaglist ?? ''; ?>">
                    <div id="taghelp" class="form-text">REMINDER: If a tag is pending removal, you must remove it from
                        this field, otherwise it will be added back.
                    </div>
                </div>
                <div class="mb-3">
                    <h6>Approved tags</h6>
                    <div><?php echo $videotagshtml ?: 'There are no tags.' ?></div>
                </div>
                <div class="mb-3">
                    <h6>Pending additions</h6>
                    <div><?php echo $addtagshtml ?: 'There are no pending additions.' ?></div>
                </div>
                <div class="mb-3">
                    <h6>Pending removals</h6>
                    <div><?php echo $removetagshtml ?: 'There are no pending removals.' ?></div>
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