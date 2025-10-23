<?php
session_start();
require_once('steam/SteamUtils.php');
require_once('session.php');

if (!isset($_SESSION[Identifiers::STEAM_ID]) || !isset($_SESSION[Identifiers::STEAM_PROFILE])) {
    header("Location: profile.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Browse</title>
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

                <li>
                    <a href="profile.php"> <i class="fa-solid fa-user"></i> Profile</a>
                </li>

                <?php if (isset($_SESSION[Identifiers::STEAM_ID])): ?>
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

        <?php
        $lastChecked = $_SESSION[Identifiers::LAST_GAME_SESSION_CHECK] ?? 0;
        $shouldUpdate = time() - $lastChecked > 300;

        if ($shouldUpdate) {
            $request = ['type' => RequestType::GAMES, Identifiers::STEAM_ID => $_SESSION[Identifiers::STEAM_ID]];
            $response = RabbitClient::getConnection("SteamAPI")->send_request($request);

            if (is_array($response) && !empty($response)) {
                $steamGames = $response[Identifiers::STEAM_GAMES] ?? [];

                foreach ($steamGames as $game) {
                    $userID = $_SESSION[Identifiers::USER_ID];
                    $appID = $game['appid'];
                    $gameName = $game['name'];
                    $playtime = $game['playtime_forever'];

                    $storeRequest = [
                            'type' => RequestType::STORE_USER_GAME,
                            Identifiers::USER_ID => $userID,
                            'appid' => $appID,
                            'name' => $gameName,
                            'playtime' => $playtime,
                    ];

                    RabbitClient::getConnection()->send_request($storeRequest);
                }

                $_SESSION[Identifiers::LAST_GAME_SESSION_CHECK] = time();
            }
        }

        $request = ['type' => RequestType::GET_USER_GAMES, Identifiers::USER_ID => $_SESSION[Identifiers::USER_ID]];
        $response = RabbitClient::getConnection()->send_request($request);

        if (is_array($response)) {
            $steamGames = $response;
        } else {
            $steamGames = [];
        }

        $profile = $_SESSION[Identifiers::STEAM_PROFILE][Identifiers::STEAM_PROFILE] ?? [];
        ?>

        <img src="<?= $profile["avatar"] ?? "N/A" ?>"
             alt="Steam Avatar"
             style="border-radius: 4px; margin-bottom: 1rem;"
        >

        <p>
            <strong class="steam-username"><?= $profile["personaname"] ?? "N/A" ?></strong>
            <small style="color: var(--pico-muted-color);">(<?= round((time() - $_SESSION[Identifiers::LAST_GAME_SESSION_CHECK]) / 60, 1) ?>
                mins)</small><br>
            <small style="color: var(--pico-muted-color);">Steam ID: <?= $profile["steamid"] ?? "N/A" ?></small>
        </p>

        <?php if (!empty($steamGames)): ?>

            <?php
            usort($steamGames, function ($a, $b) {
                return $b['Playtime'] - $a['Playtime'];
            });
            ?>

            <label for="librarySearch">
                <input type="search" id="librarySearch" placeholder="Search library..." style="margin-bottom: 1rem;">
            </label>

            <div style="max-height: 80vh; overflow-y: auto; padding-right: 1rem;">
                <?php foreach ($steamGames as $game): ?>
                    <?php
                    $appid = $game['AppID'];
                    $name = $game['Name'];
                    $tags = json_decode($game['Tags']);
                    $playtime = $game['Playtime'];
                    ?>

                    <div class="game-div">
                        <img src="<?= SteamUtils::getAppImage($appid) ?>" alt="<?= $name ?>"
                             style="display: flex; max-width: 350px; border-radius: 2px; margin-right: 1rem;">

                        <div style="flex: 1; text-align: left;">
                            <strong style="font-size: 1.1rem;"><?= $name ?></strong><br>
                            <small style="color: var(--pico-muted-color);">
                                <?= SteamUtils::getGameTime($game) ?> hours played
                            </small>

                            <?php if (!empty($tags)): ?>
                                <div class="game-tags">
                                    <?php foreach ($tags as $tag): ?>
                                        <span class="game-tags-background">
                                            <?= htmlspecialchars($tag) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center;">No games found.</p>
        <?php endif; ?>
</main>
</body>
</html>

<script>
    document.getElementById('librarySearch')?.addEventListener('input', function (e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.game-div').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(search) ? 'flex' : 'none';
        });
    });
</script>