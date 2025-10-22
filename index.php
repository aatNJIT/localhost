<?php
session_start();
require_once('identifiers.php');
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html" data-theme="dark">

<head>
    <title>IT-490 - Index</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/pico.min.css">
    <link rel="stylesheet" href="css/custom.css"/>
    <link rel="stylesheet" href="css/fontawesome/css/all.min.css"/>
</head>

<body>
<main class="container" style="padding-left: 1rem; padding-right: 1rem">
    <article style="border: 1px var(--pico-form-element-border-color) solid">
        <nav style="justify-content: center">
            <ul>
                <?php if (isset($_SESSION[Identifiers::SESSION_ID]) && isset($_SESSION[Identifiers::USER_ID])): ?>
                    <li>
                        <a href="profile.php"> <i class="fa-solid fa-users"></i> Profile</a>
                    </li>

                    <li>
                        <a href="users.php"> <i class="fa-solid fa-users"></i> Users</a>
                    </li>

                    <?php if (isset($_SESSION[Identifiers::STEAM_ID])) : ?>
                        <li>
                            <a href="browse.php">
                                <i class="fa-solid fa-gamepad"></i> Browse
                            </a>
                        </li>
                        <li>
                            <a href="createCatalog.php">
                                <i class="fa-solid fa-plus"></i> Create Catalog
                            </a>
                        </li>
                    <?php endif; ?>

                    <li>
                        <a href="viewUserCatalogs.php?userid=<?= $_SESSION[Identifiers::USER_ID] ?>"> <i
                                    class="fa-solid fa-list"></i> My Catalogs
                        </a>
                    </li>

                    <li>
                        <a href="logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </li>

                <?php else: ?>
                    <li>
                        <a href="login.php"> <i class="fa-solid fa-right-to-bracket"></i> Login</a>
                    </li>

                    <li>
                        <a href="register.php"> <i class="fa-solid fa-right-to-bracket"></i> Register</a>
                    </li>

                    <li>
                        <a href="users.php"> <i class="fa-solid fa-users"></i> Users</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </article>
</main>
</body>
</html>