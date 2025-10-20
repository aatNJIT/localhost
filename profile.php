<?php
require_once('session.php');
require 'steam/steamAuth.php';
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Login</title>
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
                <li>
                    <strong>IT-490 <?php echo '[' . $_SESSION['username'] . ']' ?></strong>
                </li>
            </ul>
            <ul>
                <li>
                    <a href="index.php">
                        <i class="fa-solid fa-house"></i> Index
                    </a>
                </li>
                <li>
                    <a href="games.php">
                        <i class="fa-solid fa-gamepad"></i> Games
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
    </article>

    <article style="margin-top: 1rem; text-align: center;">
        <?php

        if (!isset($_SESSION['steamid'])) {
            echo '<p>Link Your Steam Account!</p>';
            loginButton("rectangle");
        } else {
            include('steam/suppliers/userProfile.php');
            ?>

            <img src="<?php echo $steamProfile["avatar"]; ?>"
                 alt="Steam Avatar"
                 style="border-radius: 4px; margin-bottom: 1rem;"
            >

            <p>
                <strong><?php echo $steamProfile["personaname"] ?></strong><br>
                <small>Steam ID: <?php echo $steamProfile["steamid"] ?></small>
            </p>

            <a href="steam/consumers/unlinkSteamAccount.php" role="button" class="primary">
                <i class="fa-solid fa-unlink"></i> Unlink Steam Account
            </a>

            <?php
        }

        ?>
    </article>
</main>
</body>
</html>
