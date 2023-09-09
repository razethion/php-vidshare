<?php
// Initialize the session
session_start();

// Include config file
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php";

// Check if the user used a reset link, and log them in automatically if they did
$url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
$url_components = array();
$url_components = parse_url($url);

$key = $url_components['query'] ?? '';

if (!empty($key) && !isset($_SESSION["loggedin"])) {
    //Check if the reset key provided exists
    $sql = "SELECT email, username FROM users WHERE reset_key = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $key);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            /* store result */
            $result = mysqli_stmt_get_result($stmt);
            $resulta = mysqli_fetch_assoc($result);

            if (mysqli_num_rows($result) == 1) {

                //Nuke the key and log the user in
                $sql1 = "UPDATE users SET reset_key = null WHERE username = ?";
                if ($stmt1 = mysqli_prepare($link, $sql1)) {
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt1, "s", $resulta['username']);

                    // Attempt to execute the prepared statement
                    if (!mysqli_stmt_execute($stmt1)) {
                        print "Error updating key";
                        die();
                    }

                    // Close statement
                    mysqli_stmt_close($stmt1);

                }

                // Store data in session variables
                $_SESSION["loggedin"] = true;
                $_SESSION["username"] = $resulta['username'];

                // Update last-logon
                // Prepare an insert statement
                $sql2 = "UPDATE userdata SET lastlogon = now() WHERE username = ?";

                if ($stmt2 = mysqli_prepare($link, $sql2)) {
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt2, "s", $resulta['username']);

                    // Attempt to execute the prepared statement
                    if (!mysqli_stmt_execute($stmt2)) {
                        echo "Something went wrong. Please try again later.";
                    }

                    // Close statement
                    mysqli_stmt_close($stmt2);

                }

            } else {
                //Results returned more than one key, this shouldn't happen
                header("location: /forgotpassword");
                die;
            }

        } else {
            //Issue checking key
            print "Something went wrong. Please try again later.";
            die();
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }
}

// Check if the user is already logged in, if no then redirect him to forgot page
if (!isset($_SESSION["loggedin"])) {
    header("location: /forgotpassword");
    die;
}

// Define variables and initialize with empty values
$password_err = $confirm_password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 8) {
        $password_err = "Password must have at least 8 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }

    // Check if captcha valid
    if (empty(trim($_POST['g-recaptcha-response']))) {
        $login_err = "Please complete the captcha";
    } else {
        $captcha = $_POST['g-recaptcha-response'];
        $secretKey = "6LdBMb8jAAAAANgqUDKx50BKzirV9t11eFvxzGEK";
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) . '&response=' . urlencode($captcha);
        $response = file_get_contents($url);
        $responseKeys = json_decode($response, true);
        // should return JSON with success as true
        if (!$responseKeys["success"]) {
            $login_err = "Captcha failed";
        }
    }

    if (empty($password_err) && empty($confirm_password_err) && empty($login_err)) {
        $sql = "UPDATE users SET password = ? WHERE username = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ss", $param_password, $param_username);

            // Set parameters
            $param_username = $_SESSION['username'];
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Redirect to login page
                header("location: /");
            } else {
                echo "Something went wrong. Please try again later.";
                die();
            }

            // Close statement
            mysqli_stmt_close($stmt);

        }
    }

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
    <script type="text/javascript">
        var onloadCallback = function () {
            grecaptcha.render('captcha', {
                'sitekey': '6LdBMb8jAAAAAGTsDE17iJBkN-Pp3ZHwhpqNTgsV',
                'theme': 'dark'
            });
        };
    </script>

    <title>Toypics | Change password</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-body text-body">
<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/header.php"); ?>
<div class="container mt-4" style="max-width:330px;">
    <h2>Change password</h2>
    <div class="row mt-4">
        <div class="col">
            <?php
            if (!empty($login_err)) {
                echo '<div class="alert alert-danger">' . $login_err . '</div>';
            }
            ?>
            <form method="post">
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input name="password" type="password"
                           class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
                           id="password" aria-describedby="passwordfeedback">
                    <div id="passwordfeedback" class="invalid-feedback">
                        <?php echo $password_err; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm password</label>
                    <input name="confirm_password" type="password"
                           class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>"
                           id="confirm_password" aria-describedby="confirmpassfeedback">
                    <div id="confirmpassfeedback" class="invalid-feedback">
                        <?php echo $confirm_password_err; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <div id="captcha" class="g-recaptcha"></div>
                </div>
                <button type="submit" class="btn btn-danger">Submit</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
<script src='https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit' async defer></script>
</body>
</html>