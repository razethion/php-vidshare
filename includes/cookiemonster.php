<?php
#Check if user asked to be remembered
if (isset($_COOKIE["rememberme"]) && !isset($_SESSION['loggedin'])) {
    #verify cookie hash
    $sql = "SELECT username, set_time  FROM remembertokens WHERE token = ?";
    if ($stmt1 = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt1, "s", $_COOKIE["rememberme"]);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt1)) {
            // Store result
            mysqli_stmt_store_result($stmt1);
            // Check if token exists
            if (mysqli_stmt_num_rows($stmt1) == 1) {
                // Bind result variables
                mysqli_stmt_bind_result($stmt1, $username, $set_time);
                if (mysqli_stmt_fetch($stmt1)) {
                    // Verify token is still valid
                    if ((strtotime($set_time) + (86400 * 30)) > time()) {

                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["username"] = $username;

                        // Update last-logon
                        // Prepare an insert statement
                        $sql = "UPDATE userdata SET lastlogon = now() WHERE username = ?";

                        if ($stmt2 = mysqli_prepare($link, $sql)) {
                            // Bind variables to the prepared statement as parameters
                            mysqli_stmt_bind_param($stmt2, "s", $param_username);

                            // Set parameters
                            $param_username = $username;

                            // Attempt to execute the prepared statement
                            if (!mysqli_stmt_execute($stmt2)) {
                                echo "Something went wrong. Please try again later.";
                            }

                            // Close statement
                            mysqli_stmt_close($stmt2);

                        }
                    } else {
                        #Cookie expired, delete it
                        setcookie("rememberme", "", time() - 3600, "/");
                    }
                }
            }
        } else {
            echo "Something went wrong. Please try again later.";
        }
        // Close statement
        mysqli_stmt_close($stmt1);
    }
}