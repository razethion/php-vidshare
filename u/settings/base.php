<?php
$s3url = "https://static.toypics.net/";
$s3local = "/mnt/s3/";

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");

$uploadscript = "";

if ($setpage == "profile") {

    $file_err = "";

    #Handle setting change posts
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        #Sanitize post values
        $_POST['profdesc'] = preg_replace("/\\r\\n/", "", nl2br($_POST['profdesc']));
        array_walk_recursive($_POST, function (&$value) {
            $value = htmlentities($value, ENT_QUOTES);
        });

        #Handle post of profile settings page


        #If file submitted
        if (!empty($_FILES['file']['name'])) {
            $name = filter_filename($_FILES['file']['name']);
            $tmp_name = $_FILES['file']['tmp_name'];
            $position = strpos($name, ".");
            $fileextension = substr($name, $position + 1);
            $fileextension = strtolower($fileextension);
            $pfprenamed = "pfp.jpg";
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

                $maxDim = 50;
                list($width, $height, $type, $attr) = getimagesize($tmp_name);
                if ($width > $maxDim || $height > $maxDim) {
                    $ratio = $width / $height;
                    if ($ratio > 1) {
                        $new_width = $maxDim;
                        $new_height = $maxDim / $ratio;
                    } else {
                        $new_width = $maxDim * $ratio;
                        $new_height = $maxDim;
                    }
                    $src = imagecreatefromstring(file_get_contents($tmp_name));
                    $dst = imagecreatetruecolor($new_width, $new_height);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    imagedestroy($src);
                    imagejpeg($dst, $finaldir); // adjust format as needed
                    imagedestroy($dst);
                }

            }

        }
        ### Update links
        #set vars
        $url_web = (!empty($_POST['url-web'])) ? $_POST['url-web'] : '';
        preg_match("/([^:\/\s]+)\.([^:\/\s]+)/", $url_web, $urlregmatch);
        $url_web_desc = (!empty($_POST['url-web-desc'])) ? $_POST['url-web-desc'] : $urlregmatch[0] ?? '';
        $url_twitter = (!empty($_POST['url-twitter'])) ? $_POST['url-twitter'] : '';
        $url_telegram = (!empty($_POST['url-telegram'])) ? $_POST['url-telegram'] : '';
        $url_discord = (!empty($_POST['url-discord'])) ? $_POST['url-discord'] : '';
        $url_fetlife = (!empty($_POST['url-fetlife'])) ? $_POST['url-fetlife'] : '';

        #create link array
        $urlparamsarr = array(
            "web" => $url_web,
            "webdesc" => $url_web_desc,
            "twitter" => $url_twitter,
            "telegram" => $url_telegram,
            "discord" => $url_discord,
            "fetlife" => $url_fetlife
        );

        $urlparamsjson = json_encode($urlparamsarr);

        #update profile
        $sql = "UPDATE userdata SET prof_desc = ?, links = ? WHERE username = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sss", $_POST['profdesc'], $urlparamsjson, $username);

            // Attempt to execute the prepared statement
            if (!mysqli_stmt_execute($stmt)) {
                echo "Couldn't update profile";
            }

            // Close statement
            mysqli_stmt_close($stmt);

        }

    }

    #Get current description
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

        } else {
            echo "Something went wrong. Please try again later.";
        }
        // Close statement
        mysqli_stmt_close($stmt);
    }

    #cleanup BR for textfield
    $userprofdata['prof_desc'] = preg_replace("/&lt;br \/&gt;/", "\r\n", $userprofdata['prof_desc']);

    #decode user links json to array
    $userprofjson = json_decode($userprofdata['links'], true);

}
if ($setpage == "uploads") {

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        #Sanitize post values
        array_walk_recursive($_POST, function (&$value) {
            $value = htmlentities($value, ENT_QUOTES);
        });

        if (!empty($_POST['confirmdelete'])) {

            #Try to delete video
            require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/deletevideo.php");
            $errorno = deleteVideoByID($username, $_POST['confirmdelete'], $link);

            if ($errorno != 0) {
                $del_err_txt = parseDeleteVideoError($errorno);
                echo $del_err_txt;
            }

        }

    }

    #check if any videos processing


    #list latest uploads
    $sql = "SELECT username,id,v.filehash,upload_date,video_title,views
            FROM (
                SELECT filehash, sum(views) as views
                FROM videoviews
                GROUP BY filehash
            ) as v
            LEFT JOIN uploads u on v.filehash = u.filehash
            WHERE processed = 1 AND username = ?
            ORDER BY upload_date DESC";
    if ($stmt = mysqli_prepare($link, $sql)) {

        mysqli_stmt_bind_param($stmt, "s", $username);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);

            $alluploads = array(); // create a variable to hold the information
            while (($row = mysqli_fetch_assoc($result))) {
                $alluploads[] = $row; // add the row in to the results (data) array
            }

        } else {
            echo "Something went wrong. Please try again later.";
        }
        // Close statement
        mysqli_stmt_close($stmt);
    }

    $videolisthtml = "";
    foreach ($alluploads as $singlefile) {
        $videolisthtml .= '
        
        <div class="border rounded mb-3">
    <div class="p-3">
        <a class="text-decoration-none" href="/u/' . $singlefile['username'] . '/' . $singlefile['id'] . '">
            <div class="row mx-0 mb-2">
                <div class="col-6 bg-black align-items-center p-0" style="max-height:140px; max-width:250px;">
                    <img class="img-fluid mx-auto d-block" style="height:140px;width:250px; object-fit: contain;"
                         src="' . S3_URL . $singlefile['username'] . "/" . $singlefile['filehash'] . '.jpg"
                         alt="thumb">
                </div>
                <div class="col-6 d-inline">
                    <h3 class="lh-1 fw-bold text-danger mb-1 text-wrap">' . $singlefile['video_title'] . '</h3>
                    <h5 class="lh-1 text-body mb-1">' . $singlefile['username'] . '</h5>
                    <h5 class="lh-1 text-body">' . $singlefile['views'] . " views · " . datediff($singlefile['upload_date']) . '</h5>
                </div>
            </div>
        </a>
        <div class="row mx-0">
                <div class="col-sm-6 mb-2 px-0">
                    <a href="/u/' . $singlefile['username'] . '/' . $singlefile['id'] . '/edit" class="btn btn-danger">Edit
                        video</a>
                </div>
                <div class="col-sm-6 mb-2 px-0">
                    <form class="m-0 p-0" method="post" enctype="multipart/form-data">
                        <div class="input-group justify-content-sm-end">
                            <div class="input-group-text">
                                <span class="me-2">Confirm delete</span>
                                <input name="confirmdelete" id="confirmdeletecheck" class="form-check-input mt-0"
                                       type="checkbox" value="' . $singlefile['id'] . '"
                                       aria-label="Checkbox for following text input" onclick="alertWarn()">
                            </div>
                            <button class="btn btn-danger" type="submit">
                                Delete
                            </button>
                        </div>
                    </form>
            </div>
        </div>
    </div>
</div>
        
        ';
    }

    if (empty($videolisthtml)) {
        $videolisthtml = "No uploads found.";
    }

    $uploadscript = '
    <script>
    // Define a function that fetches data and updates the content.
function fetchData() {
  // Make AJAX request to your endpoint
  $.ajax({
    url: "/includes/getuploadpercent.php?' . $username . '",  // Replace with your API endpoint
    method: "GET",
    dataType: "json",
    success: function(response) {
      // Empty the content div first
      $("#content").empty();
      
      if(response.video_title == null) {
          //do nothing
          console.log("No videos uploading");
      } else {
          console.log("Checking video percentage");
// Fetch data every 15 seconds
setTimeout(function(){
   window.location.reload();
}, 15000);

        // Append the HTML to the content div
        $("#content").html(`
<div class="border rounded mb-3">
    <div class="p-3">
            <div class="row mx-0 mb-2">
                <div class="col-6 bg-black align-items-center p-0" style="max-height:140px; max-width:250px;">
                    <img class="img-fluid mx-auto d-block" style="height:140px;width:250px; object-fit: contain;"
                         src="' . S3_URL . '${response.username}/${response.filehash}.jpg"
                         alt="Thumbnail is generating">
                </div>
                <div class="col-6 d-inline">
                    <h3 class="lh-1 fw-bold text-danger mb-1 text-wrap">${response.video_title}</h3>
                    <h5 class="lh-1 text-body mb-1">${response.username}</h5>
                    <h5 class="lh-1 text-body">Submitted: ${response.upload_date}</h5>
                </div>
            </div>
        <div class="row mx-0 mb-2">
            <div class="d-flex ps-0">
                <div class="w-100 mt-3">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-default" id="prog"
                         role="progressbar" style="width: ${response.percentage}%; transition: all 2s ease 0s;"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>
        `);
      }
    },
    error: function(error) {
      console.error("Error fetching data: ", error);
    }
  });
}
fetchData();

    </script>
    ';

    $processinglisthtml = "
    <div id='content'></div>
    ";

}

?>
<!DOCTYPE html>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <title>Toypics | Profile settings</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-body text-body">
<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/header.php"); ?>
<div class="container p-3">
    <h2>Account settings</h2>
    <div class="row pt-3">
        <div class="col-md-3 mb-3">
            <div class="list-group">
                <!--suppress HtmlUnknownTarget -->
                <a href="../settings/profile" class="list-group-item
                <?php echo ($setpage == "profile") ? 'list-group-item-danger' : 'list-group-item-action'; ?>
                ">Profile settings</a>
                <!--suppress HtmlUnknownTarget -->
                <a href="../settings/uploads"
                   class="list-group-item list-group-item-action list-group-item-action
                <?php echo ($setpage == "uploads") ? 'list-group-item-danger' : 'list-group-item-action'; ?>
                ">Upload settings</a>
                <a href="/changepassword"
                   class="list-group-item list-group-item-action list-group-item-action">Change password</a>
                <!--suppress HtmlUnknownTarget -->
                <!--
                <a href="../settings/deleteaccount"
                   class="list-group-item list-group-item-action list-group-item-action disabled
                <?php echo ($setpage == "deleteaccount") ? 'list-group-item-danger' : 'list-group-item-action'; ?>
                ">Delete account</a>
                -->
            </div>
        </div>
        <div class="col-md-9">
            <?php require_once($chosenpagehtml); ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
<script>
    function alertWarn() {
        alert("Warning! This cannot be undone!")
    }
</script>
<?php echo $uploadscript ?? '' ?>
</body>
</html>
