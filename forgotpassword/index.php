<?php /** @noinspection PhpUndefinedNamespaceInspection */
// Initialize the session
session_start();

// Include config file
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/conn.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/vars.php";

// Check if the user is already logged in, if yes then redirect him to change page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: /changepassword");
    die;
}

// Define variables and initialize with empty values
$email = "";
$email_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    #Sanitize post values
    array_walk_recursive($_POST, function (&$value) {
        $value = htmlentities($value, ENT_QUOTES);
    });

    function getResetKey($link, $username): string
    {
        $key = bin2hex(random_bytes(22));

        #update profile
        $sql = "UPDATE users SET reset_key = ? WHERE username = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ss", $key, $username);

            // Attempt to execute the prepared statement
            if (!mysqli_stmt_execute($stmt)) {
                print "Internal server error.";
                die();
            }

            // Close statement
            mysqli_stmt_close($stmt);

        }

        return $key;
    }

    /** @noinspection PhpUndefinedClassInspection */
    function sendResetNotice($email, $username, $key): void
    {
        require_once '/var/www/toypics/vendor/autoload.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer();

        // Server settings
        $mail->SMTPDebug = 0; // Enable verbose debug output. Set to 0 for production.
        $mail->isSMTP(); // Set mailer to use SMTP
        $mail->Host = 'in-v3.mailjet.com'; // Specify main and backup SMTP servers
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = MAILER_API_KEY; // SMTP username
        $mail->Password = MAILER_API_SECRET; // SMTP password
        $mail->SMTPSecure = 'ssl'; // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 465; // TCP port to connect to

        // Recipients
        $mail->setFrom('info@toypics.net', 'toypics.net');
        $mail->addAddress($email, $username); // Add a recipient

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = 'Toypics password reset request';
        $mail->Body = $mail->AltBody = 'Hi ' . $username . ', this is the password reset you requested. Visit the following link to login and change your password: ' . SITE_DOMAIN . "/changepassword?" . $key;

        $mail->send();

    }

    // Check if email is empty
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
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

    // Validate credentials
    if (empty($email_err) && empty($login_err)) {
        // Prepare a select statement
        $sql = "SELECT email, username FROM toyp2.users WHERE email = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $email);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {

                /* store result */
                $result = mysqli_stmt_get_result($stmt);
                $resulta = mysqli_fetch_assoc($result);
                if (mysqli_num_rows($result) == 1) {
                    $resetkey = getResetKey($link, $resulta['username']);
                    sendResetNotice($resulta['email'], $resulta['username'], $resetkey);
                }

                $successalert = "Success! If your email was on file, we sent a link to reset your password. Please check your email.";
            } else {
                $login_err = "Something went wrong. Please try again later.";
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

    <title>Toypics | Forgot password</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-body text-body">
<?php require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/header.php"); ?>
<div class="container mt-4" style="max-width:330px;">
    <h2>Forgot password</h2>
    <div class="row mt-4">
        <div class="col">
            <?php
            if (!empty($login_err)) {
                echo '<div class="alert alert-danger">' . $login_err . '</div>';
            }
            ?>
            <?php
            if (!empty($successalert)) {
                echo '<div class="alert alert-success">' . $successalert . '</div>';
            }
            ?>
            <form method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input name="email" type="text"
                           class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="email"
                           maxlength="319"
                           value="<?php echo $email; ?>" aria-describedby="emailfeedback">
                    <div id="emailfeedback" class="invalid-feedback">
                        <?php echo $email_err; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <div id="captcha" class="g-recaptcha"></div>
                </div>
                <button type="submit" class="btn btn-danger">Submit</button>
            </form>
            <p>Know your password? <a href="/login">Login here</a>.</p>
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