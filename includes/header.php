<?php

// Login user if remembered
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/cookiemonster.php";

if (isset($_SESSION["username"])) {
    $userpic = "https://static.toypics.net/" . $_SESSION['username'] . "/pfp.jpg";
    if (@fopen($userpic, 'r')) {
        $userpichtml = '
        
            <img style="object-fit: cover;" src="' . $userpic . '" width="30" height="30" class="rounded-circle" alt="pic">
        
        ';
    } else {
        $userpichtml = '
        
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="#dc3545" class="bi bi-person-circle" viewBox="0 0 16 16">
            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
        </svg>
        
        ';
    }
    $userprofile = "/u/" . $_SESSION["username"];
    $usersettings = "/u/" . $_SESSION["username"] . "/settings";

    $greeting = "Hi, " . $_SESSION["username"] . "!";

    $logged = '
<div class="col-6 align-items-center d-flex">
<div class="dropdown">
  <a class="btn btn-dark border dropdown-toggle" style="width:160px;color:var(--bs-secondary-color);" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <span class="align-middle">' . $greeting . '</span>' . $userpichtml . '
  </a>

  <ul class="dropdown-menu">
    <li><a class="dropdown-item" href="/upload"><i class="bi bi-upload"></i> Upload</a></li>
    <li><a class="dropdown-item" href="' . $userprofile . '"><i class="bi bi-person-circle"></i> Profile</a></li>
    <li><a class="dropdown-item" href="' . $usersettings . '"><i class="bi bi-gear"></i> Settings</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item text-success" href="/includes/tagrandom.php"><i class="bi bi-tags-fill"></i> Help tag!</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item text-danger" href="/logout"><i class="bi bi-door-closed"></i> Logout</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item" style="color: #4D4D4D">' . gethostname() . '</a></li>
  </ul>
</div>
</div>
';
}


$notlogged = '
<div class="col-6 align-items-center d-flex" style="z-index: 2;">
    <a class="btn btn-outline-light me-2" href="/login"><i class="bi bi-door-open"></i> Login</a>
    <a class="btn btn-danger" href="/newuser"><i class="bi bi-person-plus"></i> Sign-up</a>
</div>
';

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $usersection = $logged;
} else {
    $usersection = $notlogged;
}

?>
<style>.dropdown-toggle::after {
        vertical-align: middle !important;
    }</style>
<header class="p-0 m-0 bg-dark-subtle" style="position: relative;">
    <img src="/assets/toy-bg.jpg"
         style="width: 100%; height: 100%; position: absolute; top: 0; left: 0; opacity: 0.25; object-fit: cover;"
         alt="">
    <div class="p-3 container">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
            <a href="/" class="d-flex align-items-center mb-2 mb-lg-0 text-white text-decoration-none navbar-brand">
                <img style="z-index: 2;" class="me-3" src="/assets/logo.png" alt="Toypics" height="30">
            </a>

            <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
                <!--
                <li><a href="/tags" class="nav-link px-2 text-white"><i class="bi bi-bookmarks"></i> Tags</a></li>
                <li><a href="/users" class="nav-link px-2 text-white"><i class="bi bi-people"></i> Users</a></li>
                -->
            </ul>

            <div class="row px-0 mx-0">
                <form class="col-6 m-0 z-1" role="search" action="/search">
                    <input name="s" type="search" class="form-control form-control-dark text-bg-dark"
                           placeholder="Search..." style="height:44px;"
                           aria-label="Search">
                </form>
                <?php echo $usersection; ?>
            </div>
        </div>
    </div>
</header>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1030;">
    <div id="cookie-banner" class="toast align-items-center text-white bg-danger border-0">
        <div class="d-flex">
            <div class="toast-body">
                This site relies on cookies to function properly. Just so you know.
            </div>
            <button id="cookie-ok-button" type="button" class="btn-close btn-close-white me-2 m-auto"></button>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        // Check for cookie
        if (!getCookie('cookieConsent')) {
            // Show the banner if cookie is not found
            const banner = document.getElementById("cookie-banner");
            banner.classList.add('show');

            // Auto-hide the banner after 10 seconds
            setTimeout(() => {
                closeBannerAndSetCookie();
            }, 10000);

            // Close banner and set cookie when OK is clicked
            document.getElementById("cookie-ok-button").addEventListener("click", closeBannerAndSetCookie);
        }
    });

    function closeBannerAndSetCookie() {
        // Close the banner
        const banner = document.getElementById("cookie-banner");
        banner.classList.remove('show');

        // Set the cookie
        setCookie('cookieConsent', 'true', 365);
    }

    // Function to set a cookie
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    // Function to get a cookie
    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

</script>