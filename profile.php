<?php
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
<main class="container" style="padding-left: 1rem; padding-right: 1rem">
    <article style="border: 1px var(--pico-form-element-border-color) solid">
        <nav style="justify-content: center">
            <ul>
                <li>
                    <a href="index.php">
                        <i class="fa-solid fa-house"></i> Index
                    </a>
                </li>

                <li>
                    <a href="users.php"> <i class="fa-solid fa-users"></i> Users</a>
                </li>

                <?php if (isset($_SESSION[Identifiers::STEAM_ID])): ?>
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
                    <li>
                        <a href="followUser.php">
                            <i class="fa-solid fa-user-group"></i> Friends
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

    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">

        <?php if (!isset($_SESSION[Identifiers::STEAM_ID])): ?>
            <a href='?login' role="button" class="primary">
                <i class="fa-solid fa-link"></i> Link Steam Account
            </a>
        <?php else: ?>
            <?php
            $sessionProfile = $_SESSION[Identifiers::STEAM_PROFILE][Identifiers::STEAM_PROFILE] ?? null;
            $update = $sessionProfile === null || (isset($sessionProfile['profileupdatetime']) && time() - $sessionProfile['profileupdatetime'] > 300);

            if ($update) {
                $request = ['type' => RequestType::PROFILE, Identifiers::STEAM_ID => $_SESSION[Identifiers::STEAM_ID]];
                $response = RabbitClient::getConnection("SteamAPI")->send_request($request);
                if (is_array($response) && !empty($response)) {
                    $_SESSION[Identifiers::STEAM_PROFILE] = $response;
                }
            }

            $profile = $_SESSION[Identifiers::STEAM_PROFILE][Identifiers::STEAM_PROFILE] ?? [];
            ?>

            <?php if (!empty($profile)): ?>
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