<?php
// Initialize the session
session_start();

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/functions.php");

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
    <title>Toypics | <?php echo $username . "'s liked videos" ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-body text-body h-100" id="body">
<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/header.php"); ?>
<div class="container p-3">
    <div class="row">
        <h3><?php echo $username ?>'s liked videos</h3>
        <hr>
    </div>
    <div class="row" id="results">
    </div>
    <div id="loading">Loading...</div>
</div>
<script async defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>

    function checkScrollable() {
        const documentHeight = $(document).height();
        const windowHeight = $(window).height();

        if (documentHeight <= windowHeight) {
            loadData();
            console.log('Loading more for window height');
        }
    }

    // Variables to keep track of the scroll position and data to load
    let loadingData = false;
    const perPage = 10;
    let currentPage = 0;

    let lastResultEmpty = false;

    function loadData() {
        if (lastResultEmpty) {
            return;
        }
        // Only load new data if we are not currently loading data
        if (loadingData) return;
        loadingData = true;

        // Show a loading message
        $('#loading').show();

        // Perform the AJAX request to your API
        $.ajax({
            url: '/includes/getlikedvideos.php?<?php echo $username ?>,' + currentPage, // Replace with your API endpoint
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                // Hide loading message
                $('#loading').hide();
                if ($.isEmptyObject(response) || response.length === 0) {
                    lastResultEmpty = true;
                    return;
                }

                // Append the new data to the #content div
                for (let i = 0; i < response.length; i++) {

                    const upldate = new Date(response[i].upload_date).toLocaleDateString("en-US");

                    const resultHTML = `
                        <div class="col-md-4">
                            <a class="text-decoration-none" href="/u/${response[i].username}/${response[i].id}">
                                <div class="row mx-0 mb-2">
                                    <div class="col-12 bg-black align-items-center p-0 mb-2" style="height:auto; max-width: 100%;">
                                        <img class="img-fluid mx-auto d-block"
                                             style="max-height:232px;width: 100%; object-fit: contain;"
                                             src="https://static.toypics.net/${response[i].username}/${response[i].filehash}.jpg"
                                             alt="thumb">
                                    </div>
                                    <div class="col-12 p-0 m-0">
                                        <h3 class="lh-1 fw-bold text-danger mb-1 text-wrap">${response[i].video_title}</h3>
                                        <p class="lh-1 text-body mb-1">${response[i].username}</p>
                                        <p class="lh-1 text-body">${response[i].views} views · ${upldate} · ${response[i].likes} likes</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    `;

                    $('#results').append(resultHTML);
                }

                // Increment the current page and allow more data to be loaded
                currentPage = currentPage + 3;
                loadingData = false;
                checkScrollable();
            },
            error: function (error) {
                // Handle the error appropriately
                console.error("An error occurred:", error);
                loadingData = false;
            }
        });
    }

    // Initial data load
    loadData();

    let fntr;

    // Listen for scroll events on the #content div
    $(window).on('scroll', function () {
        const scrollHeight = $(document).height();
        const scrollPosition = $(window).height() + $(window).scrollTop();
        const remainingScroll = scrollHeight - scrollPosition;

        // Check if we're 100px away from the bottom of the page
        if (remainingScroll <= 100) {
            if (!fntr) {
                loadData();
                fntr = true;  // Make sure it only triggers once
            }
        } else {
            fntr = false; // Reset flag if user scrolls up
        }
    });
</script>
</body>

