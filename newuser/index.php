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
$username = $password = $confirm_password = $email = $code = "";
$username_err = $password_err = $confirm_password_err = $email_err = $code_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate sign-up code
    if (empty(trim($_POST['code']))) {
        $code_err = "Please enter a valid sign-up code.";
    } elseif (strtolower(trim($_POST['code'])) != "toypics23") {
        $code_err = "Invalid sign-up code.";
    }

    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } elseif (!preg_match('/^[A-Za-z0-9_]+$/', trim($_POST["username"]))) {
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } elseif (strlen(trim($_POST["username"])) < 4) {
        $username_err = "Username must be at least 4 characters.";
    } elseif (strlen(trim($_POST["username"])) > 16) {
        $username_err = "Username cannot be longer than 16 characters.";
    } else {
        // Prepare a select statement
        $sql = "SELECT * FROM users WHERE username = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Set parameters
            $param_username = trim($_POST["username"]);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                /* store result */
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

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

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } elseif (!filter_var(trim($_POST["email"], FILTER_VALIDATE_EMAIL))) {
        $email_err = "This does not appear to be an email.";
    } else {
        // Prepare a select statement
        $sql = "SELECT * FROM users WHERE email = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);

            // Set parameters
            $param_email = trim($_POST["email"]);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                /* store result */
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $email_err = "This email is already taken.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
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

    // Check input errors before inserting in database
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err) && empty($login_err) && empty($code_err)) {

        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password, $param_email);

            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_email = $email;

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Redirect to login page
                header("location: /login");
            } else {
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);

        }

        // Prepare an insert statement
        $sql = "INSERT INTO userdata (username) VALUES (?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Set parameters
            $param_username = $username;

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Redirect to login page
                header("location: /login");
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
    <h2>Sign up for Toypics</h2>
    <div class="row mt-4">
        <div class="col">
            <?php
            if (!empty($login_err)) {
                echo '<div class="alert alert-danger">' . $login_err . '</div>';
            }
            ?>
            <form method="post">
                <div class="mb-3">
                    <label for="code" class="form-label">Sign-up code</label>
                    <input name="code" type="text"
                           class="form-control <?php echo (!empty($code_err)) ? 'is-invalid' : ''; ?>" id="code"
                           maxlength="16"
                           value="<?php echo $code; ?>" aria-describedby="codefeedback">
                    <div id="codefeedback" class="invalid-feedback">
                        <?php echo $code_err; ?>
                    </div>
                </div>
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
                    <label for="email" class="form-label">Email</label>
                    <input name="email" type="email"
                           class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
                           id="email" value="<?php echo $email; ?>" aria-describedby="emailfeedback">
                    <div id="emailfeedback" class="invalid-feedback">
                        <?php echo $email_err; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <div id="captcha" class="g-recaptcha"></div>
                </div>
                <button type="submit" class="btn btn-danger">Login</button>
            </form>
            <p>Forgot your password? <a href="/forgotpassword">Reset it here</a>.</p>
            <p>Already have an account? <a href="/login">Login here</a>.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
<script src='https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit' async defer></script>
</body>
</html>