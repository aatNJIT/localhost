<?php
require_once('session.php');
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
                <img src="assets/albert.gif" alt="albert">
                <li><strong>IT-490</strong></li>
            </ul>
            <ul>
                <li>
                    <a href="index.php"> <i class="fa-solid fa-house">
                        </i> Index
                    </a>
                </li>
            </ul>
        </nav>
    </article>

    <!-- align has been deprecated for a long time now, but it still works great :D -->
    <div class="container" style=" margin-top: 1rem; padding-left: 12px; padding-right: 12px">
        <?php echo '<div align="center"> You are ' . $_SESSION['username'] . '</div>' ?>
    </div>
</main>
</body>
</html>
