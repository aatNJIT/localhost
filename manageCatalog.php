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

$catalogId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($catalogId <= 0) {
    header("Location: viewUserCatalogs.php?userid=" . $_SESSION[Identifiers::USER_ID] . "&error=Invalid catalog ID");
    exit();
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $client = RabbitClient::getConnection();
    
    if ($action === 'search') {
        $name = $_GET['name'] ?? '';
        $games = (array)$client->send_request(['type' => RequestType::SEARCH_GAMES, 'name' => $name, 'limit' => PHP_INT_MAX]);
        echo json_encode($games);
        exit();
    } elseif ($action === 'games') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        $games = (array)$client->send_request(['type' => RequestType::GAMES, 'offset' => $offset, 'limit' => $limit]);
        echo json_encode($games);
        exit();
    } elseif ($action === 'getCatalog') {
        $catalog = $client->send_request(['type' => RequestType::GET_CATALOG, 'catalog_id' => $catalogId]);
        echo json_encode($catalog);
        exit();
    }
    echo json_encode([]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client = RabbitClient::getConnection();
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'delete') {
        $result = $client->send_request([
            'type' => RequestType::DELETE_CATALOG,
            'catalog_id' => $catalogId,
            'user_id' => $_SESSION[Identifiers::USER_ID]
        ]);
        
        if (isset($result->success) && $result->success) {
            header("Location: viewUserCatalogs.php?userid=" . $_SESSION[Identifiers::USER_ID] . "&success=Catalog deleted successfully");
        } else {
            header("Location: manageCatalog.php?id=" . $catalogId . "&error=" . urlencode($result->message ?? 'Failed to delete catalog'));
        }
        exit();
    } elseif ($postAction === 'update') {
        $games = $_POST['games'] ?? [];
        $ratings = $_POST['ratings'] ?? [];
        $title = $_POST['title'] ?? '';
        
        $result = $client->send_request([
            'type' => RequestType::UPDATE_CATALOG,
            'catalog_id' => $catalogId,
            'user_id' => $_SESSION[Identifiers::USER_ID],
            'title' => $title,
            'games' => $games,
            'ratings' => $ratings
        ]);
        
        if (isset($result->success) && $result->success) {
            header("Location: manageCatalog.php?id=" . $catalogId . "&success=Catalog updated successfully");
        } else {
            header("Location: manageCatalog.php?id=" . $catalogId . "&error=" . urlencode($result->message ?? 'Failed to update catalog'));
        }
        exit();
    }
}

$profile = $_SESSION[Identifiers::STEAM_PROFILE][Identifiers::STEAM_PROFILE];
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Manage Catalog</title>
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
            <?= htmlspecialchars($successMessage) ?>
        </article>
    <?php elseif ($errorMessage): ?>
        <article class="error">
            <?= htmlspecialchars($errorMessage) ?>
        </article>
    <?php endif; ?>

    <div id="loadingCatalog" style="text-align: center; margin: 2rem 0;">
        <span aria-busy="true">Loading catalog...</span>
    </div>

    <div id="mainContent" style="display: none;">
        <div style="display: flex; gap: 10px; align-items: stretch; height: 100vh;">
            
            <article style="flex: 1; max-width: 50%; border: 1px var(--pico-form-element-border-color) solid; padding: 1rem; display: flex; flex-direction: column;">

                <div style="text-align: center; flex-shrink: 0;">
                    <p><strong style="font-size: 1.1rem;">Add Games to Catalog</strong></p>
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

            
            <article style="flex: 1; max-width: 50%; border: 1px var(--pico-form-element-border-color) solid; padding: 1rem;">
                <form method="POST" id="catalogForm" style="display: flex; flex-direction: column; height: 100%;">
                    <input type="hidden" name="action" value="update">
                    
                    <div style="text-align: center; margin-bottom: 1rem; flex-shrink: 0;">
                        <p><strong style="font-size: 1.1rem;">Edit Catalog</strong></p>
                    </div>

                    <label for="catalogTitle" style="flex-shrink: 0;">
                        <input type="text" id="catalogTitle" name="title" placeholder="Enter catalog title..."
                               maxlength="255" minlength="1" required>
                    </label>

                    <div id="catalogGames" style="flex: 1; overflow-y: auto; padding-right: 1rem; margin-bottom: 1rem; min-height: 0;">
                    </div>

                    <div id="selectedGamesInputs"></div>
                    <div id="ratingsInputs"></div>

                    <div style="display: flex; gap: 1rem; flex-shrink: 0;">
                        <button type="submit" id="updateCatalog" style="flex: 1;">
                            <i class="fa-solid fa-save"></i> Save Changes
                        </button>
                        <button type="button" id="deleteCatalog" class="secondary" style="flex: 1;" onclick="deleteCatalog()">
                            <i class="fa-solid fa-trash"></i> Delete Catalog
                        </button>
                    </div>
                </form>
            </article>
        </div>
    </div>
</main>


<dialog id="deleteModal">
    <article>
        <header>
            <h3>Confirm Delete</h3>
        </header>
        <p>Are you sure you want to delete this catalog? This action cannot be undone once confirmed.</p>
        <footer>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <button type="button" class="secondary" onclick="document.getElementById('deleteModal').close()">Cancel</button>
                <button type="submit" class="contrast">Delete</button>
            </form>
        </footer>
    </article>
</dialog>

</body>
</html>

<script>
    const catalogId = <?= $catalogId ?>;
    let currentPage = 1;
    let searchText = '';
    let searchResults = [];
    const gamesPerPage = 20;
    const selectedGames = new Map(); // appId -> {name, rating}

    
    loadCatalog();

    async function loadCatalog() {
        try {
            const response = await fetch(`?id=${catalogId}&action=getCatalog`);
            const catalog = await response.json();
            
            if (!catalog || catalog.error) {
                alert('Failed to load catalog');
                window.location.href = 'viewUserCatalogs.php?userid=<?= $_SESSION[Identifiers::USER_ID] ?>';
                return;
            }

            document.getElementById('catalogTitle').value = catalog.title || '';
            
            // Load existing games into selectedGames map
            if (catalog.games && Array.isArray(catalog.games)) {
                catalog.games.forEach(game => {
                    const appId = game.AppID || game.appid;
                    const name = game.Name || game.name;
                    const rating = game.rating || game.Rating || 0;
                    selectedGames.set(appId, { name: name, rating: rating });
                });
            }

            document.getElementById('loadingCatalog').style.display = 'none';
            document.getElementById('mainContent').style.display = 'block';
            
            renderCatalogGames();
            loadGames();
        } catch (error) {
            console.error('Error loading catalog:', error);
            alert('Error loading catalog');
        }
    }

    function renderCatalogGames() {
        const catalogGames = document.getElementById('catalogGames');
        catalogGames.innerHTML = '';

        if (selectedGames.size === 0) {
            catalogGames.innerHTML = '<p style="text-align: center; color: var(--pico-muted-color);">No games in catalog. Add games from the left panel.</p>';
        }

        selectedGames.forEach((gameData, appId) => {
            const gameDiv = document.createElement('div');
            gameDiv.setAttribute('data-appid', appId);
            gameDiv.style.cssText = 'display: flex; flex-direction: column; padding: 0.75rem; margin-bottom: 0.5rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px;';

            const topRow = document.createElement('div');
            topRow.style.cssText = 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;';

            const gameName = document.createElement('strong');
            gameName.textContent = gameData.name;
            gameName.style.cssText = 'flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;';

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'secondary';
            removeButton.style.cssText = 'cursor: pointer; padding: 0.25rem 0.5rem; margin-left: 0.5rem;';
            removeButton.innerHTML = '<i class="fas fa-trash"></i>';
            removeButton.onclick = function() { removeGame(appId); };

            topRow.appendChild(gameName);
            topRow.appendChild(removeButton);

            const ratingRow = document.createElement('div');
            ratingRow.style.cssText = 'display: flex; align-items: center; gap: 0.5rem;';

            const ratingLabel = document.createElement('label');
            ratingLabel.textContent = 'Rating:';
            ratingLabel.style.cssText = 'margin: 0; font-size: 0.9rem;';

            const ratingInput = document.createElement('input');
            ratingInput.type = 'number';
            ratingInput.min = '0';
            ratingInput.max = '10';
            ratingInput.step = '0.5';
            ratingInput.value = gameData.rating || 0;
            ratingInput.style.cssText = 'width: 80px; margin: 0; padding: 0.25rem 0.5rem;';
            ratingInput.onchange = function() { updateRating(appId, this.value); };

            const ratingMax = document.createElement('span');
            ratingMax.textContent = '/ 10';
            ratingMax.style.cssText = 'font-size: 0.9rem; color: var(--pico-muted-color);';

            ratingRow.appendChild(ratingLabel);
            ratingRow.appendChild(ratingInput);
            ratingRow.appendChild(ratingMax);

            gameDiv.appendChild(topRow);
            gameDiv.appendChild(ratingRow);
            catalogGames.appendChild(gameDiv);
        });

        computeForm();
    }

    function updateRating(appId, rating) {
        if (selectedGames.has(appId)) {
            const gameData = selectedGames.get(appId);
            gameData.rating = parseFloat(rating) || 0;
            selectedGames.set(appId, gameData);
            computeForm();
        }
    }

    function removeGame(appId) {
        selectedGames.delete(appId);
        renderCatalogGames();
        
        const libraryGameDiv = document.querySelector(`.game-div[appid="${appId}"]`);
        const tagContainer = document.getElementById(`tags-${appId}`);
        if (libraryGameDiv) libraryGameDiv.style.display = 'flex';
        if (tagContainer) tagContainer.style.display = 'block';
    }

    function selectGame(appId, gameName) {
        if (selectedGames.has(appId)) return;
        selectedGames.set(appId, { name: gameName, rating: 0 });

        const libraryGameDiv = document.querySelector(`.game-div[appid="${appId}"]`);
        const tagContainer = document.getElementById(`tags-${appId}`);
        if (libraryGameDiv) libraryGameDiv.style.display = 'none';
        if (tagContainer) tagContainer.style.display = 'none';

        renderCatalogGames();
    }

    function computeForm() {
        const gamesInputs = document.getElementById('selectedGamesInputs');
        const ratingsInputs = document.getElementById('ratingsInputs');
        gamesInputs.innerHTML = '';
        ratingsInputs.innerHTML = '';

        selectedGames.forEach((gameData, appId) => {
            const gameInput = document.createElement('input');
            gameInput.type = 'hidden';
            gameInput.name = 'games[]';
            gameInput.value = appId;
            gamesInputs.appendChild(gameInput);

            const ratingInput = document.createElement('input');
            ratingInput.type = 'hidden';
            ratingInput.name = 'ratings[' + appId + ']';
            ratingInput.value = gameData.rating || 0;
            ratingsInputs.appendChild(ratingInput);
        });
    }

    function deleteCatalog() {
        document.getElementById('deleteModal').showModal();
    }

    let searchTimeout;
    document.getElementById('gameSearch').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchText = e.target.value;
            currentPage = 1;
            searchResults = [];
            loadGames();
        }, 500);
    });

    function changePage(direction) {
        currentPage += direction;
        loadGames();
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
                if (searchResults.length === 0) {
                    const url = `?id=${catalogId}&action=search&name=${encodeURIComponent(searchText)}`;
                    const response = await fetch(url);
                    searchResults = await response.json();
                }

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
                searchResults = [];
                const url = `?id=${catalogId}&action=games&page=${currentPage}&limit=${gamesPerPage}`;
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
                     style="display: flex; max-width: 20vh; border-radius: 2px; margin-right: 1rem;">
                <div style="text-align: left;">
                    <strong style="font-size: 1rem;">${name}</strong>
                </div>
            `;

            gamesList.appendChild(gameDiv);

            if (tags && tags.length > 0) {
                const tagsDiv = document.createElement('div');
                tagsDiv.id = `tags-${appid}`;
                tagsDiv.className = 'game-tags';
                tagsDiv.style.cssText = 'padding-bottom: 0.5rem; gap: 0.25rem';
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
</script>
