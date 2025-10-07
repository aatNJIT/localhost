<?php session_start(); ?>
<html lang="en" xmlns="http://www.w3.org/1999/html" data-theme="dark">

<head>
    <title>IT-490 - Index</title>
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
                <li><strong>IT-490</strong></li>
            </ul>
            <ul>
                <?php
                if (isset($_SESSION['sessionID']) && isset($_SESSION['userID']) && isset($_SESSION['username'])) {
                    echo '<li>
                        <a href="profile.php"> <i class="fa-solid fa-user"></i> Profile</a>
                    </li>';
                    echo '<li>
                        <a href="logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </li>';
                } else {
                    echo '<li>
                        <a href="login.php"> <i class="fa-solid fa-right-to-bracket"></i> Login</a>
                    </li>';
                    echo '<li>
                        <a href="register.php"> <i class="fa-solid fa-right-to-bracket"></i> Register</a>
                    </li>';
                }
                ?>
            </ul>
        </nav>
    </article>
</main>
</body>
</html>