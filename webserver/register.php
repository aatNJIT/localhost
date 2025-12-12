<?php
require_once 'logger.php';

$errorMessage = '';
$successMessage = '';

log_message("register.php page loaded. Method=".$_SERVER['REQUEST_METHOD']);

if (isset($_POST['username']) && isset($_POST['password'])) {
    require_once('rabbitMQ/RabbitClient.php');
    require_once('identifiers.php');

    $username = $_POST["username"];
    $password = $_POST["password"];

    if (empty($username) || empty($password) || strlen($username) > 64 || strlen($password) > 64) {
        $errorMessage = 'Invalid username or password';
        log_message("User registration failed due to invalid input.");
    } else {
        $request = array();
        $request['type'] = RequestType::REGISTER;
        $request[Identifiers::USERNAME] = $username;
        $request[Identifiers::PASSWORD] = hash('sha256', $password);

        $client = RabbitClient::getConnection();
        log_message("Attempting to register user $username.");
        $response = $client->send_request($request);

        if ($response) {
            $successMessage = 'Registered Successfully';
            log_message("User $username registered successfully.");
        } else {
            $errorMessage = 'Failed To Register';
            log_message("User $username failed to register.");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Register</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/pico.min.css">
    <link rel="stylesheet" href="css/custom.css"/>
    <link rel="stylesheet" href="css/fontawesome/css/all.min.css"/>
</head>

<body>
<main>
    <article style="border: 1px var(--pico-form-element-border-color) solid">
        <nav style="justify-content: center">
            <ul>
                <li>
                    <a href="index.php"> <i class="fa-solid fa-house">
                        </i> Index
                    </a>
                </li>
                <li>
                    <a href="login.php"> <i class="fa-solid fa-right-to-bracket">
                        </i> Login
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
        <article class="error">'
            <?= $errorMessage ?>
        </article>'
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

            <button type="submit" style="border: 2px var(--pico-form-element-border-color) solid">Register</button>
        </div>
    </form>
</main>
</body>
</html>
