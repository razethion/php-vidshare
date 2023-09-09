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

    <title>Toypics | Migration</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-body text-body">
<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/header.php"); ?>
<div class="container mt-4" style="max-width:330px;">
    <h2 class="text-danger">Toypics has returned!</h2>
    <h6>What changed, and what does this mean for you?</h6>
    <div class="row mt-4">
        <div class="col">
            <p>Toypics was built over a decade ago, some videos being as old as 2009. It had over 14,500 videos uploaded
                and over 58,000 user accounts.</p>
            <p>Over the years, the site gained more and popularity, and over time fell into disrepair. Near the end of
                its life, it was full of spam content and bots. Eventually, the site was taken offline due to functional
                issues.</p>
            <p>In 2022 the site was revived, and eventually brought online in 2023. It is entirely brand-new, and was
                completely rebuilt from the ground up.</p>
        </div>
    </div>
    <h2 class="text-danger">FAQ!</h2>
    <h4>What happens to the old content?</h4>
    <div class="row mt-4">
        <div class="col">
            <p>We are working to migrate it all over! Well, most of it. There is a large amount of content that won't
                be,
                like private videos, spam, broken videos, and the like. Additionally, some content that was ripped by
                bots
                and re-uploaded won't be migrated either.</p>
        </div>
    </div>
    <h4>A video I'm looking for is missing.</h4>
    <div class="row mt-4">
        <div class="col">
            <p>If the video was public, and you can't find it on the site, we may still be able to migrate it over.
                Soon we will have a way to request archived content, so please check back later on this.</p>
        </div>
    </div>
    <h4>I had an account before, how do I sign in?</h4>
    <div class="row mt-4">
        <div class="col">
            <p>Two sets of accounts were migrated: those active after 2019, and users that uploaded videos. If you were
                part of that batch, you just need to reset your password to login.</p>
            <p>If you are having trouble logging in, contact us.</p>
        </div>
    </div>
    <h4>Who owns the site now?</h4>
    <div class="row mt-4">
        <div class="col">
            <p>Same people.</p>
        </div>
    </div>
    <h4>Something isn't working, help!</h4>
    <div class="row mt-4">
        <div class="col">
            <p>Reach out on telegram, let us know what's up.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
<script src='https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit' async defer></script>
</body>
</html>