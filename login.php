<?php

session_start();
$errorMessage = '';
$successMessage = '';

if (isset($_POST['username']) && isset($_POST['password'])) {
    require_once('rabbitMQ/RabbitClient.php');
    require_once('identifiers.php');
    require_once('logger.php');

    $username = $_POST["username"];
    $password = $_POST["password"];

    if (empty($username) || empty($password) || strlen($username) > 64 || strlen($password) > 64) {
        $errorMessage = 'Invalid username or password';
        log_message("User failed to log in: $username");
    } else {
        $request = [
            'type' => RequestType::TWO_FA_LOGIN,
            Identifiers::USERNAME => $username,
            Identifiers::PASSWORD => $password
        ];
        $response = RabbitClient::getConnection()->send_request($request);

        if (is_array($response)) {
            $_SESSION['temp_userid'] = $response[Identifiers::USER_ID];
            $_SESSION['temp_username'] = $response[Identifiers::USERNAME];
            header('Location: verify_otp.php');
            exit();
        } else {
            log_message("User failed to log in: $username");
            $errorMessage = 'Invalid Username or Password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Login</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/pico.min.css">
    <link rel="stylesheet" href="css/custom.css"/>
    <link rel="stylesheet" href="css/fontawesome/css/all.min.css"/>
</head>

<body>
<main class="main">
    <article style="border: 1px var(--pico-form-element-border-color) solid">
        <nav style="justify-content: center">
            <ul>
                <li>
                    <a href="index.php"> <i class="fa-solid fa-house">
                        </i> Index
                    </a>
                </li>
                <li>
                    <a href="register.php"> <i class="fa-solid fa-right-to-bracket">
                        </i> Register
                    </a>
                </li>
            </ul>
        </nav>
    </article>

    <?php if ($successMessage): ?>
        <article class="success">
            <?= $successMessage ?>
        </article>
    <?php elseif ($errorMessage): ?>
        <article class="error">
            <?= $errorMessage ?>
        </article>
    <?php endif; ?>

    <form method="post">
        <div class="container" style=" margin-top: 1rem; padding-left: 12px; padding-right: 12px">
            <label><b>Username</b></label>
            <label>
                <input type="text" placeholder="Username" name="username" required maxlength="64">
            </label>

            <label><b>Password</b></label>
            <label>
                <input type="password" placeholder="Password" name="password" required maxlength="64">
            </label>

            <button type="submit" style="border: 2px var(--pico-form-element-border-color) solid">Login</button>
        </div>
    </form>
</main>
</body>
</html>
