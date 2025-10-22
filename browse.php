<?php
session_start();
require_once('steam/SteamUtils.php');
require_once('session.php');

if (!isset($_SESSION[Identifiers::STEAM_ID])) {
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

                <li>
                    <a href="profile.php"> <i class="fa-solid fa-user"></i> Profile</a>
                </li>

                <?php if (isset($_SESSION[Identifiers::STEAM_ID])): ?>
                    <li>
                        <a href="createCatalog.php">
                            <i class="fa-solid fa-plus"></i> Create Catalog
                        </a>
                    </li>
                <?php endif; ?>

                <li>
                    <a href="viewUserCatalogs.php?userid=<?= $_SESSION[Identifiers::USER_ID] ?>"> <i class="fa-solid fa-list"></i> My Catalogs
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

        <?php
        $sessionGames = $_SESSION[Identifiers::STEAM_GAMES] ?? null;
        $update = $sessionGames === null || (isset($sessionGames['gameupdatetime']) && time() - $sessionGames['gameupdatetime'] > 300);

        if ($update) {
            $request = ['type' => RequestType::GAMES, Identifiers::STEAM_ID => $_SESSION[Identifiers::STEAM_ID]];
            $response = RabbitClient::getConnection("SteamAPI")->send_request($request);
            if (is_array($response) && !empty($response)) {
                $_SESSION[Identifiers::STEAM_GAMES] = $response;
            }
        }

        $profile = $_SESSION[Identifiers::STEAM_PROFILE][Identifiers::STEAM_PROFILE] ?? [];
        $games = $_SESSION[Identifiers::STEAM_GAMES][Identifiers::STEAM_GAMES] ?? [];
        ?>

        <img src="<?= $profile["avatar"] ?? "N/A" ?>"
             alt="Steam Avatar"
             style="border-radius: 4px; margin-bottom: 1rem;"
        >

        <p>
            <strong style="font-size: 1.1rem;"><?= $profile["personaname"] ?? "N/A" ?></strong>
            <small style="color: var(--pico-muted-color);">(<?= round((time() - $_SESSION[Identifiers::STEAM_GAMES]['gameupdatetime']) / 60, 1) ?>
                mins)</small><br>
            <small style="color: var(--pico-muted-color);">Steam ID: <?= $profile["steamid"] ?? "N/A" ?></small>
        </p>

        <?php if (!empty($games)): ?>
            <?php
            usort($games, function ($a, $b) {
                return $b['playtime_forever'] - $a['playtime_forever'];
            });
            ?>

            <label for="librarySearch">
                <input type="search" id="librarySearch" placeholder="Search library..." style="margin-bottom: 1rem;">
            </label>

            <div style="max-height: 80vh; overflow-y: auto; padding-right: 1rem;">
                <?php foreach ($games as $game): ?>
                    <?php
                    $appid = $game['appid'];
                    $name = $game['name'];
                    ?>

                    <div class="game-div"
                         style="display: flex; align-items: center; padding: 1rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px; cursor: pointer; transition: background-color .1s;"
                         onmouseover="this.style.backgroundColor='var(--pico-background-color)'"
                         onmouseout="this.style.backgroundColor='transparent'">

                        <img src="<?= SteamUtils::getAppImage($appid) ?>"
                             alt="<?= $name ?>"
                             style="display: flex; max-width: 350px; border-radius: 2px; margin-right: 1rem;">

                        <div style="text-align: left;">
                            <strong style="font-size: 1.1rem;"><?= $name ?></strong><br>
                            <small style="color: var(--pico-muted-color);">
                                <?= SteamUtils::getGameTime($game) ?> hours played
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center;">No games found or profile is private.</p>
        <?php endif; ?>

    </article>
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