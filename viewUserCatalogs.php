<?php
session_start();
require_once('identifiers.php');
require_once('steam/SteamUtils.php');
require_once('rabbitMQ/RabbitClient.php');

if (!isset($_GET[Identifiers::USER_ID])) {
    header("Location: index.php");
    exit();
} else {
    $userID = $_GET[Identifiers::USER_ID];
}
?>

<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html" data-theme="dark">

<head>
    <title>IT-490 - My Catalogs</title>
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
                    <a href="index.php"> <i class="fa-solid fa-house">
                        </i> Index
                    </a>
                </li>

                <?php if (isset($_SESSION[Identifiers::SESSION_ID]) && isset($_SESSION[Identifiers::USER_ID])): ?>
                    <li>
                        <a href="users.php"> <i class="fa-solid fa-users"></i> Users</a>
                    </li>
                    <li>
                        <a href="profile.php"> <i class="fa-solid fa-user"></i> Profile</a>
                    </li>
                <?php endif; ?>

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
                <?php endif; ?>

                <?php if (isset($_SESSION[Identifiers::SESSION_ID]) && isset($_SESSION[Identifiers::USER_ID])): ?>
                    <li>
                        <a href="logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </article>

    <article style="margin-top: 1rem; text-align: center; border: 1px solid var(--pico-form-element-border-color); padding: 1rem;">

        <?php
        $request = ['type' => RequestType::GET_USER_CATALOGS, Identifiers::USER_ID => $userID];
        $response = RabbitClient::getConnection()->send_request($request);
        ?>

        <?php if (is_array($response) && !empty($response)): ?>
            <div style="max-height: 80vh; overflow-y: auto; padding-right: 1rem;">

                <?php foreach ($response as $catalog): ?>
                    <div style="margin-bottom: 0.5rem; cursor: pointer; font-weight: bold; font-size: 1.2rem;">
                        <?= $catalog['Title'] ?> (<?= count($catalog['games']) ?> games)
                    </div>

                    <div id="catalog-<?= $catalog['CatalogID'] ?>" class="hidden"
                         style="margin-bottom: 1rem; padding-left: 1rem;">
                        <?php if (!empty($catalog['games'])): ?>
                            <?php foreach ($catalog['games'] as $game): ?>
                                <?php
                                $appid = $game['AppID'];
                                $rating = $game['Rating'];
                                $gameName = $game['GameName'];
                                ?>
                                <div class="game-div"
                                     style="display:flex; align-items:center; padding:0.5rem; margin-bottom:0.4rem; border:1px solid var(--pico-form-element-border-color); border-radius:4px;">
                                    <img src="<?= SteamUtils::getAppImage($appid) ?>" alt="<?= $gameName ?>"
                                         style="max-width:120px; border-radius:2px; margin-right:1rem;">
                                    <div style="text-align:left;">
                                        <strong><?= $gameName ?></strong><br>
                                        <small style="color: var(--pico-muted-color);">
                                            Rating: <?= $rating ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--pico-muted-color);">No games in this catalog.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php else: ?>
            <p style="text-align: center; margin-top: 1rem">No catalogs found.</p>
        <?php endif; ?>

    </article>
</main>
</body>
</html>