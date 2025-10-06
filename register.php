<?php
$errorMessage = '';
$successMessage = '';

if (isset($_POST['username']) && isset($_POST['password'])) {
    require_once('rabbitMQ/RabbitClient.php');

    $username = $_POST["username"];
    $password = $_POST["password"];

    if (empty($username) || empty($password) || strlen($username) > 64 || strlen($password) > 64) {
        $errorMessage = 'Invalid username or password';
    } else {
        $request = array();
        $request['type'] = 'register';
        $request['username'] = $username;
        $request['password'] = password_hash($password, PASSWORD_BCRYPT);

        $client = RabbitClient::getConnection();
        $response = $client->send_request($request);

        if ($response) {
            $successMessage = 'Registered Successfully';
        } else {
            $errorMessage = 'Failed To Register';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-491 - Register</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/pico.min.css">
    <link rel="stylesheet" href="css/custom.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
</head>

<body>
<main class="container" style="padding-left: 1rem; padding-right: 1rem">
    <article style="border: 1px var(--pico-form-element-border-color) solid">
        <nav>
            <ul>
                <img src="assets/albert.gif" alt="albert">
                <li><strong>IT-491</strong></li>
            </ul>
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

    <?php
    if ($successMessage) {
        echo '<article style="background-color: lightgreen; margin-top: 1rem; margin-bottom: 1rem; color: darkgreen; border: 2px green solid">';
        echo $successMessage;
        echo '</article>';
    }

    if ($errorMessage) {
        echo '<article style="background-color: indianred; margin-top: 1rem; margin-bottom: 1rem; color: darkred; border: 2px darkred solid">';
        echo $errorMessage;
        echo '</article>';
    }
    ?>

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