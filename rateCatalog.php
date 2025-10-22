<?php
session_start();
require_once('session.php');

$title = $_POST['title'];
$games = $_POST['games'];

if (!isset($title) || !isset($games)) {
    header("Location: createCatalog.php");
    exit();
}

if (strlen($title) < 1 || strlen($title) > 255) {
    header("Location: createCatalog.php");
    exit();
}

require_once('steam/SteamUtils.php');
require_once('identifiers.php');
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Catalog Ratings</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/pico.min.css">
    <link rel="stylesheet" href="css/custom.css"/>
    <link rel="stylesheet" href="css/fontawesome/css/all.min.css"/>
</head>

<body>
<main style="padding-left: 10vh; padding-right: 10vh;">
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

                <li>
                    <a href="profile.php"> <i class="fa-solid fa-user"></i> Profile</a>
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
                <?php endif; ?>

                <li>
                    <a href="logout.php">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
    </article>

    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">

        <?php
        $games = array_filter($_SESSION[Identifiers::STEAM_GAMES][Identifiers::STEAM_GAMES] ?? [], function($game) {
            return in_array($game['appid'], $_POST['games']);
        });

        usort($games, function ($a, $b) {
            return $b['playtime_forever'] - $a['playtime_forever'];
        });
        ?>

        <h3>Rate Your Games</h3>
        <p><strong><?= $title ?></strong></p>

        <?php if (!empty($games)): ?>
            <form method="POST" action="saveCatalog.php">
                <input type="hidden" name="title" value="<?= htmlspecialchars($title) ?>">

                <div style="max-height: 70vh; overflow-y: auto; padding-right: 1rem;">
                    <?php foreach ($games as $game): ?>
                        <?php
                        $appid = $game['appid'];
                        $name = $game['name'];
                        ?>

                        <input type="hidden" name="gameNames[<?= $appid ?>]" value="<?= htmlspecialchars($name) ?>">
                        <div class="game-rating-div" style="display: flex; align-items: center; padding: 1rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px;">

                            <img src="<?= SteamUtils::getAppImage($appid) ?>"
                                 alt="<?= $name ?>"
                                 style="max-width: 350px; border-radius: 2px; margin-right: 1rem;"
                            >

                            <div style="flex: 1; text-align: left;">
                                <strong style="font-size: 1.1rem;"><?= $name ?></strong>

                                <br>

                                <small style="color: var(--pico-muted-color);">
                                    <?= SteamUtils::getGameTime($game) ?> hours played
                                </small>

                                <div style="margin-top: 1rem;">
                                    <label for="rating-<?= $appid ?>" style="display: flex; align-items: center; gap: 1rem;">
                                        <span style="min-width: 80px; padding-bottom: 16px">Rating: <strong><span id="value-<?= $appid ?>">5</span></strong></span>
                                        <input
                                            type="range"
                                            id="rating-<?= $appid ?>"
                                            name="ratings[<?= $appid ?>]"
                                            min="1"
                                            max="10"
                                            value="5"
                                            style="flex: 1;"
                                            oninput="document.getElementById('value-<?= $appid ?>').textContent = this.value"
                                        >
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" style="margin-top: 1rem; width: 100%;">
                    <i class="fa-solid fa-save"></i> Save Catalog
                </button>
            </form>
        <?php else: ?>
            <p style="text-align: center;">No games selected.</p>
        <?php endif; ?>

    </article>
</main>
</body>
</html>