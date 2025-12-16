<?php
require_once 'logger.php';
log_message("profile.php page loaded. Method=".$_SERVER['REQUEST_METHOD']);

session_start();
require('steam/steamAuth.php');
require_once('identifiers.php');
require_once('session.php');
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Profile</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/pico.min.css">
    <link rel="stylesheet" href="css/custom.css"/>
    <link rel="stylesheet" href="css/fontawesome/css/all.min.css"/>
</head>

<body>
<main class="main">
    <article class="bordered-article">
        <nav class="main-navigation">
            <ul>
                <li>
                    <a href="index.php">
                        <i class="fa-solid fa-house"></i> Index
                    </a>
                </li>

                <li>
                    <a href="users.php"> <i class="fa-solid fa-users"></i> Users</a>
                </li>

                <?php if (isset($_SESSION[Identifiers::STEAM_ID])): log_message("Steam ID found for user " . $_SESSION[Identifiers::USERNAME]); ?>
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
                    <li>
                        <a href="recommendations.php">
                            <i class="fa-solid fa-lightbulb"></i> Recommendations
                        </a>
                    </li>
                <?php endif; ?>

                <li>
                    <a href="viewUserCatalogs.php?userid=<?= $_SESSION[Identifiers::USER_ID] ?>"> <i
                                class="fa-solid fa-list"></i> My Catalogs
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

    <article class="bordered-article" style="text-align: center">

        <?php if (!isset($_SESSION[Identifiers::STEAM_ID]) || !isset($_SESSION[Identifiers::STEAM_PROFILE])):
        log_message("User does not have a linked Steam account.");
        ?>
            <a href='?login' role="button" class="primary">
                <i class="fa-solid fa-link"></i> Link Steam Account
            </a>
        <?php else:
        $profile = $_SESSION[Identifiers::STEAM_PROFILE][Identifiers::STEAM_PROFILE];
        $steam_username = $profile['personaname'];
        $steamid = $_SESSION[Identifiers::STEAM_ID];
        log_message("User has a linked Steam account under Username $steam_username and Steam ID $steamid.");
        ?>
            <?php
            $profile = $_SESSION[Identifiers::STEAM_PROFILE][Identifiers::STEAM_PROFILE];
            ?>

            <?php if (!empty($profile)):?>
                <img src="<?= $profile["avatar"] ?? "N/A" ?>"
                     alt="Steam Avatar"
                     style="border-radius: 4px; margin-bottom: 1rem;"
                >

                <p>
                    <strong style="font-size: 1.1rem;"><?= $profile["personaname"] ?? "N/A" ?></strong>
                    <br>
                    <small style="color: var(--pico-muted-color);">Steam ID: <?= $profile["steamid"] ?? "N/A" ?></small>
                </p>

                <a href="steam/consumers/unlinkSteamAccount.php" role="button" class="primary">
                    <i class="fa-solid fa-unlink"></i> Unlink Steam Account
                </a>
            <?php else: ?>
                <p>No Steam profile data found.</p>
            <?php endif; ?>

        <?php endif; ?>

    </article>
</main>
</body>
</html>