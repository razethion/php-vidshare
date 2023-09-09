<?php
session_start();

use JetBrains\PhpStorm\NoReturn;

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");
disable_ob();

#[NoReturn] function leavePage(): void
{
    header("Location: /");
    die();
}

#Ensure user is logged in first
if (!(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true)) {
    header("location: /login");
    die;
}
$username = $_SESSION["username"];

$video_title = '';

#Get tagify tags
$taglist = getTagifyTags($link);

?>
<!DOCTYPE html>
<html data-bs-theme="dark" lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <title>Toypics | Upload</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root, .tagify__tag, .tagify {
            --tagify-dd-bg-color: var(--bs-dark);
            --tagify-dd-color-primary: var(--bs-danger);
            --tags-border-color: var(--bs-border-color);
            --tag-bg: var(--bs-danger);

        }
    </style>
</head>
<body class="bg-body text-body">
<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/header.php"); ?>
<div class="container mt-4" style="max-width:330px;">
    <h2 class="mb-4">Upload to Toypics</h2>
    <ul>
        <li>Must be longer than 10 seconds.</li>
        <li>Must be smaller than 1 GB.</li>
        <li>Tags help discoverability!</li>
    </ul>
    <div class="row mt-3">
        <div class="col">
            <div class="mb-3">
                <!-- finished alert goes here -->
            </div>
            <form id="form" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="formFile" class="form-label">Video file</label>
                    <input name="file" class="form-control" type="file" accept="video/*" id="formFile"
                           aria-describedby="filefeedback" required>
                </div>
                <div id="previewdiv" class="ratio ratio-16x9 mb-3">
                    <video class="rounded" id="preview" height=150px controls src="#"></video>
                </div>
                <div class="mb-3">
                    <label for="videoname" class="form-label">Video title</label>
                    <input name="videoname" type="text"
                           class="form-control" id="videoname" aria-describedby="videonamefeedback" required>
                </div>
                <div class="mb-3">
                    <label for="videodescription" class="form-label">Video description (optional)</label>
                    <textarea class="form-control" name="videodescription" id="videodescription"></textarea>
                </div>
                <div class="mb-3">
                    <label for="tags" class="form-label">Tags (optional)</label>
                    <input name="tags" type="text"
                           class="form-control"
                           id="tags" aria-describedby="tagsdec">
                    <div id="tagsdesc">
                        Tag your video so it can be found!
                        <br>Don't see the tag you want? Just add it!
                    </div>
                </div>
                <div class="mb-3">
                    <span>You can change the thumbnail later in the video settings.</span>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="prog"
                         role="progressbar" style="width: 0; transition: all 2s ease 0s;"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="alert alert-danger d-none" role="alert" id="uploaderr">
                    <?php echo $curlerr ?? '' ?>
                </div>
                <div class="mb-3 d-none" id="processingnotice">
                    <span class="badge text-bg-warning">Processing file, please wait...
                        <span class="spinner-grow spinner-grow-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </span>
                </div>
                <button id="sub" type="submit" class="btn btn-danger mb-3">Upload</button>
                <button id="spinner" class="btn btn-danger mb-3" type="button" disabled>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <span class="sr-only">Uploading...</span>
                </button>
            </form>
        </div>
    </div>
</div>
<script async defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
<!--suppress EqualityComparisonWithCoercionJS -->
<script>

    var submitbutton = $('#sub');
    var processingnotice = $('#processingnotice');

    // Create the worker blob
    const blob = new Blob([`
  importScripts('https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js');

  let md5Hash = CryptoJS.algo.MD5.create();

  self.addEventListener('message', function(e) {
    const { fileChunk } = e.data;

    if (fileChunk) {
      const wordArray = CryptoJS.lib.WordArray.create(fileChunk);
      md5Hash.update(wordArray);
    } else {
      const hash = md5Hash.finalize().toString();
      self.postMessage({ hash });
    }
  }, false);
`], {type: 'application/javascript'});

    const worker = new Worker(URL.createObjectURL(blob));

    // Listen for worker responses
    worker.addEventListener('message', function (e) {
        const {hash} = e.data;

        if (hash) {
            console.log("Hash: " + hash);
            window.hash = hash;
            submitbutton.removeClass('disabled');
            processingnotice.addClass('d-none');
        }
    }, false);

    function startHashing(file) {

        const chunkSize = 1024 * 1024; // 1MB
        let offset = 0;

        function readChunk() {
            const reader = new FileReader();

            const slice = file.slice(offset, offset + chunkSize);

            reader.onload = function (e) {
                worker.postMessage({fileChunk: e.target.result});

                if (offset < file.size) {
                    offset += chunkSize;
                    readChunk();
                } else {
                    worker.postMessage({fileChunk: null}); // Finalize hash
                }
            };

            reader.readAsArrayBuffer(slice);
        }

        readChunk();
    }

    document.getElementById('formFile').addEventListener('change', function (event) {
        submitbutton.addClass('disabled');
        processingnotice.removeClass('d-none');
        const file = event.target.files[0];
        const reader = new FileReader();

        reader.onload = function () {
            startHashing(file)
        };

        reader.readAsArrayBuffer(file);
    });

    let wakeLock = null;

    // Function to request a wake lock
    const requestWakeLock = async () => {
        try {
            wakeLock = await navigator.wakeLock.request('screen');
            wakeLock.addEventListener('release', () => {
                console.log('Wake Lock was released');
            });
            console.log('Wake Lock is active');
        } catch (err) {
            console.info(`Could not obtain wake lock: ${err.name}, ${err.message}`);
        }
    };

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

    $('#form').on('submit', function (e) {
        requestWakeLock();
        e.preventDefault();

        var file = document.getElementById('formFile').files[0];
        var form = document.getElementById('form');
        var chunkSize = 5 * 1024 * 1024;
        var chunks = Math.ceil(file.size / chunkSize);
        var uploadedchunks = 0;
        var uploaderr = $('#uploaderr');
        uploaderr.addClass('d-none');
        uploaderr.text('');

        function checkFormFields() {
            const formData1 = new FormData();
            // Iterate through each input, select, and textarea element
            for (const input of form.elements) {
                // Check if the element has a name and is not a file input
                if (input.name && input.type !== 'file') {
                    formData1.append(input.name, input.value);
                }
            }
            formData1.append('fileName', file.name);
            formData1.append('filesize', file.size);
            formData1.append('username', '<?php echo $username ?>');
            formData1.append('filehash', hash);

            $.ajax({
                url: 'check-fields.php',
                type: 'POST',
                data: formData1,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response == "TRUE") {
                        //run chunk upload
                        uploadChunk(0);
                        setTimeout(function () {
                            uploadChunk(1);
                        }, 1000);
                        setTimeout(function () {
                            uploadChunk(2);
                        }, 2000);
                        setTimeout(function () {
                            uploadChunk(3);
                        }, 3000);
                    } else {
                        uploaderr.removeClass('d-none');
                        uploaderr.text(response);
                        sub.removeAttribute('hidden');
                        spinner.setAttribute("hidden", true);
                    }
                },
                error: function (response) {
                    uploaderr.removeClass('d-none');
                    uploaderr.text("There was an error with the server. Please try again later.");
                    sub.removeAttribute('hidden');
                    spinner.setAttribute("hidden", true);
                }
            });
        }

        function getFileExtension(fileName) {
            // Split the filename by periods
            const parts = fileName.split('.');

            // Handle filenames with no extension or starting with a period
            if (parts.length < 2) return null;

            // Take the last part of the array, which should be the extension
            return parts.pop();
        }

        function uploadChunk(index) {
            if (index < chunks) {
                var start = index * chunkSize;
                var end = Math.min(start + chunkSize, file.size);
                var chunk = file.slice(start, end);


                var formData = new FormData();
                formData.append('index', index);
                formData.append('totalChunks', chunks);
                formData.append('fileName', file.name);
                formData.append('chunk', chunk);
                formData.append('username', '<?php echo $username ?>');
                formData.append('filehash', hash);

                $.ajax({
                    url: 'chunk.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        uploadedchunks++;
                        var percentage = (uploadedchunks / chunks) * 100;
                        $('#prog').css('width', percentage + '%'); // Update the progress div's width
                        uploadChunk(index + 4); // Call the next chunk upload
                    }
                });

            } else if (index == chunks) {
                $('#prog').removeClass('progress-bar-striped'); // Remove the class when percentage hits 100=

                const releaseWakeLock = () => {
                    if (wakeLock) {
                        wakeLock.release();
                        wakeLock = null;
                    }
                };

                // Rabbit notify section
                let attempts = 0;
                const maxAttempts = 10;
                const expectedValue = hash + ",<?php print $username ?>," + getFileExtension(file.name);

                const makeRequest = () => {
                    attempts++;

                    $.ajax({
                        url: "<?php print RABBIT_NOTIFY_URL ?>?" + expectedValue,
                        type: "POST",
                        success: function (response) {
                            // Check if the response matches the expected value
                            if (response == expectedValue) {
                                console.log("Success: Matched expected value!");
                                setTimeout(function () {
                                    window.location.href = "/u/<?php echo $username ?>/settings/uploads";
                                }, 2500);
                            } else {
                                console.warn("Warning: Did not match expected value!");

                                // Retry if the maximum number of attempts has not been reached
                                if (attempts < maxAttempts) {
                                    console.log(`Retrying... (Attempt ${attempts}/${maxAttempts})`);
                                    setTimeout(function () {
                                        makeRequest();
                                    }, 2000);
                                } else {
                                    console.error(`Failed after ${maxAttempts} attempts.`);
                                    // Perform an action here upon failure
                                    // For example: showAlert(), logError(), etc.
                                }
                            }
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.log(`Error: ${textStatus}, ${errorThrown}`);

                            // Retry if the maximum number of attempts has not been reached
                            if (attempts < maxAttempts) {
                                console.warn(`Retrying... (Attempt ${attempts}/${maxAttempts})`);
                                setTimeout(function () {
                                    makeRequest();
                                }, 2000);
                            } else {
                                console.error(`Failed after ${maxAttempts} attempts.`);
                                // Perform an action here upon failure
                                // For example: showAlert(), logError(), etc.
                            }
                        }
                    });
                };

                // Initiate the first request
                makeRequest();

            }
        }

        checkFormFields();
    });
</script>
<script>
    document.getElementById('formFile').addEventListener('change', function () {
        var maxSize = 1024 * 1024 * 1024; // 1 GB
        var file = this.files[0];

        if (file.size > maxSize) {
            alert('File is too large! Must be less than 1 GB.');
            this.value = ''; // Clear the file input
        }
    });
</script>
<script>
    previewdiv.style.display = "none";
    formFile.onchange = evt => {
        const [file] = formFile.files
        if (file) {
            preview.src = URL.createObjectURL(file);
            previewdiv.style.display = "block"
            preview.onloadedmetadata = function () {
                console.log(preview.duration.toFixed(0));
                var form = document.getElementById('formFile')
                if (preview.duration.toFixed(0) < 10) {
                    alert('File is too short, must be longer than 10 seconds.');
                    form.value = ''; // Clear the file input
                }
            };
        }
    }
</script>
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
</body>
</html>