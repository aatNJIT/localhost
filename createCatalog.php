<?php
session_start();
require_once('session.php');

$errorMessage = '';
$successMessage = '';

if (!isset($_SESSION[Identifiers::STEAM_ID])) {
    header("Location: profile.php");
    exit();
}

require_once('steam/SteamUtils.php');

if (isset($_GET['error'])) {
    $errorMessage = $_GET['error'];
} else if (isset($_GET['success'])) {
    $successMessage = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Catalog Creation</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/pico.min.css">
    <link rel="stylesheet" href="css/custom.css"/>
    <link rel="stylesheet" href="css/fontawesome/css/all.min.css"/>
</head>

<body>
<main style="margin-left: 20vh; margin-right: 20vh">
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
                        <a href="recommendations.php">
                            <i class="fa-solid fa-lightbulb"></i> Recommendations
                        </a>
                    </li>
                <?php endif; ?>

                <li>
                    <a href="viewUserCatalogs.php?userid=<?= $_SESSION[Identifiers::USER_ID] ?>"> <i
                                class="fa-solid fa-list"></i> My Catalogs</a>
                </li>

                <li>
                    <a href="logout.php">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
    </article>

    <?php
    if ($successMessage) {
        echo '<article style="background-color: lightgreen; margin-top: 1rem; margin-bottom: 1rem; color: darkgreen; border: 2px green solid">';
        echo $successMessage;
        echo '</article>';
    }

    if ($errorMessage) {
        echo '<article style="background-color: indianred; margin-top: 1rem; margin-bottom: 1rem; color: darkred; border: 2px darkred solid">';
        echo $errorMessage;
        echo '</article>';
    }
    ?>

    <div style="display: flex; gap: 10px; align-items: stretch; height: 100vh;">
        <article
                style="flex: 1; max-width: 80%; border: 1px var(--pico-form-element-border-color) solid; padding: 1rem; display: flex; flex-direction: column;">
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

            <div style="text-align: center; flex-shrink: 0;">
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
            </div>

            <?php if (!empty($games)): ?>
                <?php
                usort($games, function ($a, $b) {
                    return $b['playtime_forever'] - $a['playtime_forever'];
                });
                ?>

                <label for="librarySearch" style="flex-shrink: 0;">
                    <input type="search" id="librarySearch" placeholder="Search library..."
                           style="margin-bottom: 1rem;">
                </label>

                <div style="flex: 1; overflow-y: auto; padding-right: 1rem; min-height: 0;">
                    <?php foreach ($games as $game): ?>
                        <?php
                        $appid = $game['appid'];
                        $name = $game['name'];
                        ?>

                        <div class="game-div" appid="<?= $appid ?>"
                             style="display: flex; padding: 1rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px; cursor: pointer; transition: background-color .1s;"
                             onmouseover="this.style.backgroundColor='var(--pico-background-color)'"
                             onmouseout="this.style.backgroundColor='transparent'"
                             onclick="selectGame(<?= $appid ?>, <?= htmlspecialchars(json_encode($name)) ?>)">

                            <img src="<?= SteamUtils::getAppImage($appid) ?>"
                                 alt="<?= $name ?>"
                                 style="display: flex; max-width: 25vh; border-radius: 2px; margin-right: 1rem;">

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
                <p style="text-align: left;">No games found or profile is private.</p>
            <?php endif; ?>
        </article>

        <article
                style="flex: 1; max-width: 50%; border: 1px var(--pico-form-element-border-color) solid; padding: 1rem;">
            <form method="POST" action="rateCatalog.php" style="display: flex; flex-direction: column; height: 100%;">
                <div style="text-align: center; margin-bottom: 1rem; flex-shrink: 0;">
                    <p>
                        <strong style="font-size: 1.1rem;">Create Catalog</strong>
                    </p>
                </div>

                <label for="catalogTitle" style="flex-shrink: 0;">
                    <input type="text" id="catalogTitle" name="title" placeholder="Enter catalog title..."
                           maxlength="255" minlength="1" required>
                </label>

                <div id="gameslist"
                     style="flex: 1; overflow-y: auto; padding-right: 1rem; margin-bottom: 1rem; min-height: 0;">
                </div>

                <div id="selectedGamesInputs"></div>

                <button type="submit" id="submitCatalog" disabled style="flex-shrink: 0;">
                    Create Catalog
                </button>
            </form>
        </article>
    </div>
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

    const selectedGames = new Map();

    function selectGame(appId, gameName) {
        if (selectedGames.has(appId)) {
            return;
        } else {
            selectedGames.set(appId, gameName);
        }

        const libraryGameDiv = document.querySelector(`.game-div[appid="${appId}"]`);
        if (libraryGameDiv) {
            libraryGameDiv.style.display = 'none';
        }

        const gamesList = document.getElementById('gameslist');
        const gameDiv = document.createElement('div');
        gameDiv.setAttribute('appid', appId);
        gameDiv.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; padding-right: 0.5rem; margin-bottom: 0.25rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px;';

        const paragraphGameName = document.createElement('p');
        paragraphGameName.style.cssText = "padding-left: 0.5rem;"
        paragraphGameName.textContent = gameName;

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.style.cssText = 'cursor: pointer; padding: 0.25rem 0.5rem; display: flex; align-items: center; justify-content: center;';

        const icon = document.createElement('i');
        icon.classList.add('fas', 'fa-trash');

        removeButton.appendChild(icon);
        removeButton.onclick = function () {
            selectedGames.delete(appId);
            gameDiv.remove();

            if (libraryGameDiv) {
                libraryGameDiv.style.display = 'flex';
            }

            computeForm();
        };

        gameDiv.appendChild(paragraphGameName);
        gameDiv.appendChild(removeButton);
        gamesList.appendChild(gameDiv);

        computeForm();
    }

    function computeForm() {
        const inputs = document.getElementById('selectedGamesInputs');
        inputs.innerHTML = '';

        selectedGames.forEach((name, appId) => {
            if (name.length < 1 || name.length > 255) {
                return;
            }
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'games[]';
            input.value = appId;
            inputs.appendChild(input);
        });

        document.getElementById('submitCatalog').disabled = selectedGames.size === 0;
    }
</script>