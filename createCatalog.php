<?php
session_start();
ini_set('display_errors', 1);
require_once('session.php');

$errorMessage = '';
$successMessage = '';

if (!isset($_SESSION[Identifiers::STEAM_ID]) || !isset($_SESSION[Identifiers::STEAM_PROFILE])) {
    header("Location: profile.php");
    exit();
}

require_once('steam/SteamUtils.php');

if (isset($_GET['error'])) {
    $errorMessage = $_GET['error'];
} else if (isset($_GET['success'])) {
    $successMessage = $_GET['success'];
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'search') {
        $name = $_GET['name'] ?? '';
        $client = RabbitClient::getConnection();
        $games = (array)$client->send_request(['type' => RequestType::SEARCH_GAMES, 'name' => $name, 'limit' => PHP_INT_MAX]);
        echo json_encode($games);
        exit();
    } elseif ($action === 'games') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $lastAppId = ($page - 1) * $limit;
        $client = RabbitClient::getConnection();
        $games = (array)$client->send_request(['type' => RequestType::GAMES, 'lastappid' => $lastAppId, 'limit' => $limit]);
        echo json_encode($games);
        exit();
    }
    echo json_encode([]);
    exit();
}
$profile = $_SESSION[Identifiers::STEAM_PROFILE][Identifiers::STEAM_PROFILE];
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

    <?php if ($successMessage): ?>
        <article class="success">
            <?= $successMessage ?>
        </article>
    <?php elseif ($errorMessage): ?>
        <article class="error">
            <?= $errorMessage ?>
        </article>
    <?php endif; ?>

    <div style="display: flex; gap: 10px; align-items: stretch; height: 100vh;">
        <article
                style="flex: 1; max-width: 80%; border: 1px var(--pico-form-element-border-color) solid; padding: 1rem; display: flex; flex-direction: column;">

            <div style="text-align: center; flex-shrink: 0;">
                <img src="<?= $profile["avatar"] ?? "N/A" ?>"
                     alt="Steam Avatar"
                     style="border-radius: 4px; margin-bottom: 1rem;"
                >

                <p>
                    <strong class="steam-username"><?= $profile["personaname"] ?? "N/A" ?></strong><br>
                    <small style="color: var(--pico-muted-color);">Steam ID: <?= $profile["steamid"] ?? "N/A" ?></small>
                </p>
            </div>

            <label for="gameSearch" style="flex-shrink: 0;">
                <input type="search" id="gameSearch" placeholder="Search Games.." style="margin-bottom: 1rem;">
            </label>

            <div id="loadingStatus" style="text-align: center; margin: 2rem 0; flex-shrink: 0;">
                <span aria-busy="true">Loading games...</span>
            </div>

            <div id="gamesContainer" style="flex: 1; overflow-y: auto; padding-right: 1rem; min-height: 0; display: none;">
                <div style="text-align: center; margin-bottom: 1rem;">
                    <button id="prevBtn" onclick="changePage(-1)" class="secondary">Previous</button>
                    <span id="pageInfo" style="margin: 0 1rem;"></span>
                    <button id="nextBtn" onclick="changePage(1)">Next</button>
                </div>
                <div id="gamesList"></div>
            </div>

            <p id="noResults" style="text-align: center; display: none;">No games found.</p>
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
    let currentPage = 1;
    let searchText = '';
    let searchResults = [];
    const gamesPerPage = 20;
    const selectedGames = new Map();

    loadGames();

    let searchTimeout;
    document.getElementById('gameSearch').addEventListener('input', function (e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchText = e.target.value;
            currentPage = 1;
            loadGames();
        }, 500);
    });

    function changePage(direction) {
        currentPage += direction;

        if (searchText.length > 0) {
            const start = (currentPage - 1) * gamesPerPage;
            const end = start + gamesPerPage;
            const pageGames = searchResults.slice(start, end);
            document.getElementById('gamesContainer').style.display = 'block';
            appendGames(pageGames, true);
        } else {
            loadGames();
        }
    }

    async function loadGames() {
        const loading = document.getElementById('loadingStatus');
        const container = document.getElementById('gamesContainer');
        const noResults = document.getElementById('noResults');

        loading.style.display = 'block';
        loading.innerHTML = '<span aria-busy="true">Loading games...</span>';
        container.style.display = 'none';
        noResults.style.display = 'none';

        try {
            if (searchText.length > 0) {
                const url = `?action=search&name=${encodeURIComponent(searchText)}`;
                const response = await fetch(url);
                searchResults = await response.json();

                loading.style.display = 'none';

                if (searchResults.length === 0) {
                    noResults.style.display = 'block';
                    return;
                }

                const start = (currentPage - 1) * gamesPerPage;
                const end = start + gamesPerPage;
                const pageGames = searchResults.slice(start, end);
                appendGames(pageGames, true);
            } else {
                const url = `?action=games&page=${currentPage}&limit=${gamesPerPage}`;
                const response = await fetch(url);
                const games = await response.json();

                loading.style.display = 'none';

                if (games.length === 0) {
                    noResults.style.display = 'block';
                    return;
                }

                appendGames(games, false);
            }

            container.style.display = 'block';
        } catch (error) {
            console.error('Error loading games:', error);
            loading.innerHTML = 'Error loading games';
        }
    }

    function appendGames(games, isSearch) {
        const gamesList = document.getElementById('gamesList');
        gamesList.innerHTML = '';

        games.forEach(game => {
            const appid = game.AppID;
            const name = game.Name;
            const tags = typeof game.Tags === 'string' ? JSON.parse(game.Tags) : game.Tags;

            const isSelected = selectedGames.has(appid);

            const gameDiv = document.createElement('div');
            gameDiv.className = 'game-div';
            gameDiv.setAttribute('appid', appid);
            gameDiv.style.display = isSelected ? 'none' : 'flex';
            gameDiv.onclick = function() { selectGame(appid, name); };

            gameDiv.innerHTML = `
                <img src="https://cdn.cloudflare.steamstatic.com/steam/apps/${appid}/header.jpg"
                     alt="${name}"
                     style="display: flex; max-width: 25vh; border-radius: 2px; margin-right: 1rem;">
                <div style="text-align: left;">
                    <strong style="font-size: 1.1rem;">${name}</strong><br>
                </div>
            `;

            gamesList.appendChild(gameDiv);

            if (tags && tags.length > 0) {
                const tagsDiv = document.createElement('div');
                tagsDiv.id = `tags-${appid}`;
                tagsDiv.className = 'game-tags';
                tagsDiv.style.cssText = 'padding-bottom: 0.5rem; gap: 0.2rem';
                tagsDiv.style.display = isSelected ? 'none' : 'block';

                tags.forEach(tag => {
                    const tagSpan = document.createElement('span');
                    tagSpan.className = 'game-tags-background';
                    tagSpan.textContent = tag;
                    tagsDiv.appendChild(tagSpan);
                });

                gamesList.appendChild(tagsDiv);
            }
        });

        const previousButton = document.getElementById('prevBtn');
        const nextButton = document.getElementById('nextBtn');
        const pageInfo = document.getElementById('pageInfo');

        if (isSearch) {
            const totalPages = Math.ceil(searchResults.length / gamesPerPage);
            previousButton.disabled = currentPage === 1;
            nextButton.disabled = currentPage >= totalPages;
            pageInfo.textContent = `Page ${currentPage} of ${totalPages} (${searchResults.length} results)`;
        } else {
            previousButton.disabled = currentPage === 1;
            nextButton.disabled = games.length < gamesPerPage;
            pageInfo.textContent = `Page ${currentPage}`;
        }
    }

    function selectGame(appId, gameName) {
        if (selectedGames.has(appId)) return;
        selectedGames.set(appId, gameName);

        const libraryGameDiv = document.querySelector(`.game-div[appid="${appId}"]`);
        const tagContainer = document.getElementById(`tags-${appId}`);

        if (libraryGameDiv) libraryGameDiv.style.display = 'none';
        if (tagContainer) tagContainer.style.display = 'none';

        const gamesList = document.getElementById('gameslist');
        const gameDiv = document.createElement('div');
        gameDiv.setAttribute('appid', appId);
        gameDiv.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; padding-right: 0.5rem; margin-bottom: 0.25rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px;';

        const paragraphGameName = document.createElement('p');
        paragraphGameName.style.cssText = "padding-left: 0.5rem;";
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

            if (libraryGameDiv) libraryGameDiv.style.display = 'flex';
            if (tagContainer) tagContainer.style.display = 'block';

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
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'games[]';
            input.value = appId;
            inputs.appendChild(input);
        });

        document.getElementById('submitCatalog').disabled = selectedGames.size === 0;
    }
</script>