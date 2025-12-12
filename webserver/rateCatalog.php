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
<main class="main">
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
        $request = ['type' => RequestType::GET_USER_GAMES, Identifiers::USER_ID => $_SESSION[Identifiers::USER_ID]];
        $userGames = RabbitClient::getConnection()->send_request($request);

        if (!is_array($userGames)) {
            $userGames = [];
        }

        $filteredGames = array_filter($userGames, function($game) use ($games) {
            return in_array($game['AppID'], $games);
        });

        usort($filteredGames, function ($a, $b) {
            return $b['Playtime'] - $a['Playtime'];
        });
        ?>

        <h3>Rate Your Games</h3>
        <p><strong><?= $title ?></strong></p>

        <?php if (!empty($filteredGames)): ?>
            <form method="POST" action="saveCatalog.php">
                <input type="hidden" name="title" value="<?= htmlspecialchars($title) ?>">

                <div style="max-height: 70vh; overflow-y: auto; padding-right: 1rem;">
                    <?php foreach ($filteredGames as $game): ?>
                        <?php
                        $appid = $game['AppID'];
                        $name = $game['Name'];
                        $playtime = $game['Playtime'];
                        $tags = json_decode($game['Tags'], true);
                        ?>

                        <div class="game-rating-div" style="display: flex; align-items: center; padding: 1rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px;">

                            <img src="<?= SteamUtils::getAppImage($appid) ?>"
                                 alt="<?= $name ?>"
                                 style="max-width: 350px; border-radius: 2px; margin-right: 1rem;"
                            >

                            <div style="flex: 1; text-align: left;">
                                <strong style="font-size: 1.1rem;"><?= $name ?></strong>

                                <br>

                                <small style="color: var(--pico-muted-color);">
                                    <?= round($playtime / 60, 1) ?> hours played
                                </small>

                                <?php if (!empty($tags)): ?>
                                    <div style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.3rem;">
                                        <?php foreach ($tags as $tag): ?>
                                            <span style="background-color: var(--pico-muted-border-color); color: white; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.75rem;">
                                                    <?= htmlspecialchars($tag) ?>
                                </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

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