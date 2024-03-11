<?php
require "../env.php";
error_reporting(0);
ini_set('error_reporting', 0);

session_start([
    'cookie_lifetime' => 3600,
    'cookie_secure' => !empty($_SERVER['https']),
    'cookie_httponly' => true
]);

// Redirect to a different page if the user is already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: dashboard.php");
    exit;
}

if (empty($correctUsername) || empty($correctPassword))
{
    echo "Don't forget to insert the correctUsername and correctPassword variables in a file called env.php";
    exit;
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    if ($username == "admin" || $password == "password")
    {
        $login_err = "You still have to change the default env.php admin + password";
    }
    elseif ($username === $correctUsername && $password === $correctPassword) {
        session_regenerate_id(true);

        // Store data in session variables
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;

        // Redirect user to welcome page or dashboard
        header("Location: dashboard.php");
    } else {
        $login_err = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <div>
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>
        <?php 
        if (!empty($login_err)) {
            echo '<div>'.$login_err.'</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <label>Username</label>
                <input type="text" name="username" autocomplete="username" required>
            </div>    
            <div>
                <label>Password</label>
                <input type="password" name="password" autocomplete="current-password" required>
            </div>
            <div>
                <input type="submit" value="Login">
            </div>
        </form>
    </div>    
</body>
</html>