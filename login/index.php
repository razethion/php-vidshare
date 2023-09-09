<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect him to welcome page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: /");
    die;
}

// Include config file
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
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

    #Create a random key to store
    $rand = rand() . $username . time();

    // Validate credentials
    if (empty($username_err) && empty($password_err) && empty($login_err)) {
        // Prepare a select statement
        $sql = "SELECT username, password FROM users WHERE username = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Set parameters
            $param_username = $username;

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);

                // Check if username exists, if yes then verify password
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $username, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_start();

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["username"] = $username;

                            // Update last-logon
                            // Prepare an insert statement
                            $sql = "UPDATE userdata SET lastlogon = now() WHERE username = ?";

                            if ($stmt = mysqli_prepare($link, $sql)) {
                                // Bind variables to the prepared statement as parameters
                                mysqli_stmt_bind_param($stmt, "s", $param_username);

                                // Set parameters
                                $param_username = $username;

                                // Attempt to execute the prepared statement
                                if (!mysqli_stmt_execute($stmt)) {
                                    echo "Something went wrong. Please try again later.";
                                }

                                // Close statement
                                mysqli_stmt_close($stmt);

                            }

                            // Set remember me if chosen
                            if (isset($_POST['remember'])) {
                                $rememberhash = password_hash($rand . $username, PASSWORD_DEFAULT);
                                // Prepare an insert statement
                                $sql = "INSERT INTO remembertokens SET set_time = now(), token = ?, username = ?";

                                if ($stmt = mysqli_prepare($link, $sql)) {
                                    // Bind variables to the prepared statement as parameters
                                    mysqli_stmt_bind_param($stmt, "ss", $rememberhash, $param_username);

                                    // Attempt to execute the prepared statement
                                    if (!mysqli_stmt_execute($stmt)) {
                                        echo "Something went wrong. Please try again later.";
                                    }

                                    setcookie("rememberme", $rememberhash, time() + (86400 * 30), "/");

                                    // Close statement
                                    mysqli_stmt_close($stmt);

                                }
                            }

                            // Redirect user to welcome page
                            header("location: /");
                        } else {
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    // Username doesn't exist, display a generic error message
                    $login_err = "Invalid username or password.";
                }
            } else {
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Close connection
    mysqli_close($link);
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

    <title>Toypics | Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-body text-body">
<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/header.php"); ?>
<div class="container mt-4" style="max-width:330px;">
    <h2>Login to Toypics</h2>
    <h6>Legacy user? You will have to reset your password to login first.</h6>
    <div class="row mt-4">
        <div class="col">
            <?php
            if (!empty($login_err)) {
                echo '<div class="alert alert-danger">' . $login_err . '</div>';
            }
            ?>
            <form method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input name="username" type="text"
                           class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" id="username"
                           maxlength="16"
                           value="<?php echo $username; ?>" aria-describedby="usernamefeedback">
                    <div id="usernamefeedback" class="invalid-feedback">
                        <?php echo $username_err; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input name="password" type="password"
                           class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
                           id="password" aria-describedby="passwordfeedback">
                    <div id="passwordfeedback" class="invalid-feedback">
                        <?php echo $password_err; ?>
                    </div>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" value="1" id="flexCheckDefault">
                    <label class="form-check-label" for="flexCheckDefault">
                        Remember me
                    </label>
                </div>

                <div class="mb-3">
                    <div id="captcha" class="g-recaptcha"></div>
                </div>
                <button type="submit" class="btn btn-danger">Login</button>
            </form>
            <p>Forgot your password? <a href="/forgotpassword">Reset it here</a>.</p>
            <p>Don't have an account? <a href="/newuser">Sign up now</a>.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
<script src='https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit' async defer></script>
</body>
</html>