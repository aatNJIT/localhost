<?php
$errorMessage = '';
$successMessage = '';

if (isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password'])) {
    require_once('rabbitMQ/RabbitClient.php');
    require_once('identifiers.php');

    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    if (empty($username) || empty($email) || empty($password) || strlen($username) > 64 || strlen($password) > 64) {
        $errorMessage = 'Invalid username, email or password';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Invalid email address';
    } else {
        $request = array();
        $request['type'] = RequestType::REGISTER;
        $request[Identifiers::USERNAME] = $username;
        $request['email'] = $email;
        $request[Identifiers::PASSWORD] = password_hash($password, PASSWORD_BCRYPT);

        $client = RabbitClient::getConnection();
        $response = $client->send_request($request);

        if ($response) {
            $successMessage = 'Registered Successfully';
        } else {
            $errorMessage = 'Failed To Register - Username or Email already exists';
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

            <label><b>Email</b></label>
            <label>
                <input type="email" placeholder="email@example.com" name="email" required maxlength="255">
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