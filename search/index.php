<?php
session_start();

$url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
$url_components = array();
$url_components = parse_url($url);

#handle redirect if searched thru header
if (isset($url_components['query'])) {
    parse_str($url_components['query'], $params);
}
#Sanatize query
if (!empty($params)) {
    array_walk_recursive($params, function (&$value) {
        $value = htmlentities($value, ENT_QUOTES);
    });
}

$appser = '';
if (!empty($params['s'])) {
    $appser = "&s=" . $params['s'];
}
if (!empty($params['t'])) {
    $appser = "&t=" . $params['t'];
}
if (!empty($params['o'])) {
    $appser = "&o=" . $params['o'];
}

if (!isset($params['p']) || $params['p'] == 0) {
    header("location: /search?p=1" . $appser);
    exit();
}

#handle post and redirect
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    #pagination handling
    if (isset($_POST['p-p'])) {
        $qry .= "p=" . $params['p'] - 1;
    } else if (isset($_POST['p-n'])) {
        $qry .= "p=" . $params['p'] + 1;
    } else {
        $qry .= "p=" . $params['p'];
    }

    if (!empty($_POST['searchdata'])) {
        $searchqry = $_POST['searchdata'];
        $qry .= "&s=" . $searchqry;
    }

    if (!empty($_POST['tags'])) {

        if (isset($qry)) {
            $qry = $qry . "&";
        }

        $seltags = $_POST['tags'];
        $seltags = substr($seltags, 1, -1);

        $exploded = explode(',', $seltags);
        $atags = array();

        foreach ($exploded as $token) {

            preg_match('{"value":"(.*)"}', $token, $resp);
            array_push($atags, mb_strtolower(strval($resp[1])));

        }

        $qry = $qry . "t=" . implode(',', $atags);
    }

    if (!empty($_POST['o'])) {
        $orderquery = $_POST['o'];
        $qry .= "&o=" . $orderquery;
    }

    if (!empty($qry)) {
        header("location: /search/?" . $qry);
    } else {
        header("location: /search/");
    }
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");

$pagination = $params['p'];

#set order filter
if (empty($params['o'])) {
    $params['o'] = 1;
}
$filter[$params['o']] = 'selected';
if ($params['o'] == 1) {
    $sqlorder = "ORDER BY views DESC";
}
if ($params['o'] == 2) {
    $sqlorder = "ORDER BY views ASC";
}
if ($params['o'] == 3) {
    $sqlorder = "ORDER BY upload_date DESC";
}
if ($params['o'] == 4) {
    $sqlorder = "ORDER BY upload_date ASC";
}
if ($params['o'] == 5) {
    $sqlorder = "ORDER BY rand()";
}

#query search
$videolist = array();

if ($pagination > 1) {
    $paginationm = ($pagination - 1) * 6;
} else {
    $paginationm = $pagination - 1;
}

if (isset($params['s']) && isset($params['t'])) {
    #TODO this should be re-written to prevent errors
    /** @noinspection SqlResolve */
    /** @noinspection SqlDerivedTableAlias */
    /** @noinspection SyntaxError */
    $sql = "select u.filehash, username, upload_date, video_title, id, views FROM (select v.filehash,username,upload_date,video_title,id from (select v.filehash from (select u.filehash, (";

    $exploded = explode(',', strtolower($params['t']));

    $tagcount = 0;
    foreach ($exploded as $tag) {
        $tagcount++;
        if ($tagcount == 1) {
            $sql .= "sum(u.hastag" . $tagcount . ")";
        } else {
            $sql .= "+ sum(u.hastag" . $tagcount . ")";
        }
    }

    $sql .= ") hastag from (select filehash,";

    $tagcount = 0;
    foreach ($exploded as $tag) {
        $tagcount++;
        if ($tagcount == 1) {
            $sql .= "if(tag = '" . $tag . "', 1, null) as hastag" . $tagcount;

        } else {
            $sql .= ",if(tag = '" . $tag . "', 1, null) as hastag" . $tagcount;
        }
    }

    $sql .= " from videotags) as u group by u.filehash) as v where v.hastag = '"
        . $tagcount . "' ) as v INNER JOIN uploads u ON v.filehash = u.filehash WHERE processed = 1 AND ("
        . " video_title LIKE '%" . $params['s'] . "%' 
            OR username LIKE '%" . $params['s'] . "%')
            ) as u
         INNER JOIN (
    SELECT sum(views) as views, filehash
    FROM videoviews
    GROUP BY filehash
) as v on v.filehash = u.filehash
" . $sqlorder . "
LIMIT " . $paginationm . ",6;
";

    if ($stmt = mysqli_prepare($link, $sql)) {

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            while (($row = mysqli_fetch_assoc($result))) {
                array_push($videolist, $row); // add the row in to the results (data) array
            }
        } else {
            echo "Something went wrong. Please try again later.";
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }
} elseif (isset($params['t'])) {
    #tags only
    #TODO this should be re-written to prevent errors
    /** @noinspection SqlResolve */
    /** @noinspection SqlDerivedTableAlias */
    /** @noinspection SyntaxError */
    $sql = "select u.filehash, username, upload_date, video_title, id, views FROM (select v.filehash,username,upload_date,video_title,id from (select v.filehash from (select u.filehash, (";

    $exploded = explode(',', strtolower($params['t']));

    $tagcount = 0;
    foreach ($exploded as $tag) {
        $tagcount++;
        if ($tagcount == 1) {
            $sql .= "sum(u.hastag" . $tagcount . ")";
        } else {
            $sql .= "+ sum(u.hastag" . $tagcount . ")";
        }
    }

    $sql .= ") hastag from (select filehash,";

    $tagcount = 0;
    foreach ($exploded as $tag) {
        $tagcount++;
        if ($tagcount == 1) {
            $sql .= "if(tag = '" . $tag . "', 1, null) as hastag" . $tagcount;

        } else {
            $sql .= ",if(tag = '" . $tag . "', 1, null) as hastag" . $tagcount;
        }
    }

    $sql .= " from videotags) as u group by u.filehash) as v where v.hastag = '"
        . $tagcount .
        "' ) as v INNER JOIN uploads u ON v.filehash = u.filehash
         WHERE processed = 1
     ) as u
         INNER JOIN (
    SELECT sum(views) as views, filehash
    FROM videoviews
    GROUP BY filehash
) as v on v.filehash = u.filehash
" . $sqlorder . "
LIMIT " . $paginationm . ",6;
";

    if ($stmt = mysqli_prepare($link, $sql)) {

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            while (($row = mysqli_fetch_assoc($result))) {
                array_push($videolist, $row); // add the row in to the results (data) array
            }
        } else {
            echo "Something went wrong. Please try again later.";
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }
} elseif (isset($params['s'])) {
    #search only
    $sql = "
select u.filehash, username, upload_date, video_title, id, views
FROM (
SELECT username,filehash,upload_date,video_title,id FROM uploads WHERE processed = 1 AND ("
        . " video_title LIKE '%" . $params['s'] . "%' 
            OR username LIKE '%" . $params['s'] . "%')
            ) as u
         INNER JOIN (
    SELECT sum(views) as views, filehash
    FROM videoviews
    GROUP BY filehash
) as v on v.filehash = u.filehash
" . $sqlorder . "
LIMIT " . $paginationm . ",6;
";


    if ($stmt = mysqli_prepare($link, $sql)) {

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            while (($row = mysqli_fetch_assoc($result))) {
                array_push($videolist, $row); // add the row in to the results (data) array
            }
        } else {
            echo "Something went wrong. Please try again later.";
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }
} else {
    #default rand search
    $sql = "
select u.filehash, username, upload_date, video_title, id, views
FROM (
SELECT username, filehash, upload_date, video_title, id
FROM uploads
WHERE processed = 1
     ) as u
         INNER JOIN (
    SELECT sum(views) as views, filehash
    FROM videoviews
    GROUP BY filehash
) as v on v.filehash = u.filehash
" . $sqlorder . "
LIMIT " . $paginationm . ",6;
";
    if ($stmt = mysqli_prepare($link, $sql)) {

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            while (($row = mysqli_fetch_assoc($result))) {
                array_push($videolist, $row); // add the row in to the results (data) array
            }
        } else {
            echo "Something went wrong. Please try again later.";
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }
}
#output result html
$resulthtml = '';
$linecount = 0;
foreach ($videolist as $list) {
    $titlestripped = $list['video_title'];
    if (strlen($titlestripped) > 37) {
        $titlestripped = substr($titlestripped, 0, 37);
        $titlestripped .= "...";
    }
    $open = $close = 0;
    if ($linecount % 3 == 0) {
        $open = 1;
    }
    if ($linecount + 1 % 3 == 0) {
        $close = 1;
    }
    if ($open) {
        $resulthtml .= '
        
        <div class="row">
        
        ';
    }
    $resulthtml .= '
    <div class="col-md-4">
        <a class="text-decoration-none" href="/u/' . $list['username'] . '/' . $list['id'] . '">
            <div class="row mx-0 mb-2">
                <div class="col-12 bg-black align-items-center p-0 mb-2" style="height:auto; max-width: 100%;">
                    <img class="img-fluid mx-auto d-block" style="max-height:232px;width: 100%; object-fit: contain;"
                         src="' . S3_URL . $list['username'] . "/" . $list['filehash'] . '.jpg"
                         alt="thumb">
                </div>
                <div class="col-12 p-0 m-0">
                    <h3 class="lh-1 fw-bold text-danger mb-1 text-wrap">' . $titlestripped . '</h3>
                    <p class="lh-1 text-body mb-1">' . $list['username'] . '</p>
                    <p class="lh-1 text-body">' . $list['views'] . ' views · ' . datediff($list['upload_date']) . '</p>
                </div>
            </div>
        </a>
    </div>
    ';
    if ($close) {
        $resulthtml .= '

        </div>
        
        ';
    }
    $linecount++;
}
if ($linecount == 0) {
    $resulthtml .= '
        
        <div class="row">
            <p>No more results.</p>
        </div>
        
        ';
}

#resolve pagination
if (isset($pagination)) {
    #parse page
    $disabledn = $disabledp = '';
    if ($linecount < 6) {
        $disabledn = "disabled";
    }
    if ($pagination == 1) {
        $disabledp = "disabled";
    }
    $paginationhtml = '
    
    <div class="input-group mb-3">
        <ul class="list-group list-group-horizontal">
        <!--suppress HtmlUnknownTag, HtmlUnknownTag -->
<button name="p-p" class="' . $disabledp . ' list-group-item list-group-item-action list-group-item-danger">Previous</button>
        <!--suppress HtmlUnknownTag, HtmlUnknownTag -->
<span class="list-group-item list-group-item-action list-group-item-dark">' . $pagination . '<!--suppress HtmlUnknownTag -->
</span>
        <!--suppress HtmlUnknownTag -->
<button name="p-n" class="' . $disabledn . ' list-group-item list-group-item-action list-group-item-danger">Next</button>
        </ul>
    </div>

    ';

}

#Get current tag list and store to tagify
$sql = "SELECT tag FROM tags";
if ($stmt = mysqli_prepare($link, $sql)) {

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        /* store result */
        $result = mysqli_stmt_get_result($stmt);

        $tl = array(); // create a variable to hold the information
        while (($row = mysqli_fetch_assoc($result))) {
            array_push($tl, $row['tag']); // add the row in to the results (data) array
        }

        $taglist = implode(',', $tl);
        $taglist = str_replace(',', '","', $taglist);

    } else {
        echo "Something went wrong. Please try again later.";
    }
    // Close statement
    mysqli_stmt_close($stmt);
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
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css"/>
    <title>Toypics | Search</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root, .tagify__tag, .tagify {
            --tagify-dd-bg-color: var(--bs-dark);
            --tagify-dd-color-primary: var(--bs-danger);
            --tags-border-color: var(--bs-border-color);
            --tag-bg: var(--bs-danger);
            --placeholder-color: revert;

        }
    </style>
</head>
<body class="bg-body text-body h-100">
<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/header.php"); ?>
<div class="container p-3 pt-0">
    <form method="post" enctype="multipart/form-data">
        <div class="row">
            <div class="col-lg-5 pt-3">
                <input name="searchdata" type="text" class="form-control"
                       placeholder="Search for titles, users, and more..." aria-label="search"
                       value="<?php echo $params['s'] ?? ''; ?>">
            </div>
            <div class="col-lg-7">
                <div class="row">
                    <div class="col-lg-5 pt-3">
                        <!--suppress HtmlFormInputWithoutLabel -->
                        <input name="tags" type="text"
                               class="form-control" placeholder="Search for a tag..."
                               id="tags" aria-describedby="tagsdec"
                               value="<?php echo $params['t'] ?? ''; ?>">
                    </div>
                    <div class="col-lg-5 pt-3">
                        <select name='o' class="form-select" aria-label="Default select example">
                            <option value="1" <?php echo $filter['1'] ?? ''; ?>>Most Views</option>
                            <option value="2" <?php echo $filter['2'] ?? ''; ?>>Least Views</option>
                            <option value="3" <?php echo $filter['3'] ?? ''; ?>>Newest</option>
                            <option value="4" <?php echo $filter['4'] ?? ''; ?>>Oldest</option>
                            <option value="5" <?php echo $filter['5'] ?? ''; ?>>Random</option>
                        </select>
                    </div>
                    <div class="col-md-2 pt-3">
                        <button type="submit" class="btn btn-danger">Submit</button>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <?php echo $resulthtml ?>
        <div class="row">
            <div class="col-md-12 mt-3">
                <?php echo $paginationhtml ?>
            </div>
        </div>
    </form>
</div>
<script async defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
<script>
    var input = document.querySelector('input[name="tags"]'),
        // init Tagify script on the above inputs
        tagify = new Tagify(input, {
            pattern: /^.{0,32}$/,
            whitelist: ["<?php echo $taglist ?>"],
            dropdown: {
                maxItems: 20,           // <- mixumum allowed rendered suggestions
                classname: "tags-look", // <- custom classname for this dropdown, so it could be targeted
                enabled: 0,             // <- show suggestions on focus
                closeOnSelect: false    // <- do not hide the suggestions dropdown once an item has been selected
            }
        })
</script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    $(document).ready(function () {
        $('#records-limit').change(function () {
            $('form').submit();
        })
    });
</script>
</body>
