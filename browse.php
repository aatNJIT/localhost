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

    <article class="bordered-article">
        <div style="text-align: center; margin-bottom: 1rem;">
            <label for="gameSearch">
                <input type="search" id="gameSearch" placeholder="Search Games..." style="margin-bottom: 1rem;">
            </label>
        </div>

        <div id="loadingStatus" style="text-align: center; margin: 2rem 0;">
            <span aria-busy="true">Loading games...</span>
        </div>

        <div id="tableContainer" style="display: none; overflow-x: auto;">
            <table role="grid" class="games-table">
                <thead>
                <tr>
                    <th style="width: 150px;">Image</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th style="width: 250px;">Tags</th>
                </tr>
                </thead>
                <tbody id="gamesTableBody">
                </tbody>
            </table>

            <div style="text-align: center; margin-top: 1rem;">
                <button id="prevBtn" onclick="changePage(-1)" class="secondary">Previous</button>
                <span id="pageInfo" style="margin: 0 1rem;"></span>
                <button id="nextBtn" onclick="changePage(1)">Next</button>
            </div>
        </div>

        <p id="noResults" style="text-align: center; display: none;">No games found.</p>
    </article>

    <script>
        let currentPage = 1;
        let searchText = '';
        let searchResults = [];
        const gamesPerPage = 20;

        loadGames();

        let searchTimeout;
        document.getElementById('gameSearch').addEventListener('input', function (e) {
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
            const table = document.getElementById('tableContainer');
            const noResults = document.getElementById('noResults');

            loading.style.display = 'block';
            loading.innerHTML = '<span aria-busy="true">Loading games...</span>';
            table.style.display = 'none';
            noResults.style.display = 'none';

            try {
                if (searchText.length > 0) {
                    if (searchResults.length === 0) {
                        const url = `getGames.php?action=search&name=${encodeURIComponent(searchText)}`;
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
                    appendGamesToTable(pageGames, true);
                } else {
                    searchResults = [];
                    const url = `getGames.php?action=games&page=${currentPage}&limit=${gamesPerPage}`;
                    const response = await fetch(url);
                    const games = await response.json();

                    loading.style.display = 'none';

                    if (games.length === 0) {
                        noResults.style.display = 'block';
                        return;
                    }

                    appendGamesToTable(games, false);
                }

                table.style.display = 'block';
            } catch (error) {
                console.error('Error loading games:', error);
                loading.innerHTML = 'Error loading games';
            }
        }

        function appendGamesToTable(games, isSearch) {
            const tbody = document.getElementById('gamesTableBody');
            tbody.innerHTML = '';

            games.forEach(game => {
                const appid = game.AppID;
                const name = game.Name;
                const tags = typeof game.Tags === 'string' ? JSON.parse(game.Tags) : game.Tags;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                     <img src="https://cdn.cloudflare.steamstatic.com/steam/apps/${appid}/header.jpg"
                    alt="${name}"
                    class="game-thumbnail">
                    </td>
                    <td><strong>${game.Name}</strong></td>
                    <td>${game.Description || 'N/A'}</td>
                    <td>${mapTags(tags) || 'N/A'}</td>
                `;
                tbody.appendChild(row);
            });

            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const pageInfo = document.getElementById('pageInfo');

            if (isSearch) {
                const totalPages = Math.ceil(searchResults.length / gamesPerPage);
                prevBtn.disabled = currentPage === 1;
                nextBtn.disabled = currentPage >= totalPages;
                pageInfo.textContent = `Page ${currentPage} of ${totalPages} (${searchResults.length} results)`;
            } else {
                prevBtn.disabled = currentPage === 1;
                nextBtn.disabled = games.length < gamesPerPage;
                pageInfo.textContent = `Page ${currentPage}`;
            }
        }

        function mapTags(tags) {
            if (!tags || tags.length === 0) return '';
            const tagsDiv = document.createElement('div');

            tagsDiv.className = 'game-tags';
            tagsDiv.style.cssText = 'display: flex; flex-wrap: wrap; gap: 0.1rem;';

            tags.forEach(tag => {
                const tagSpan = document.createElement('span');
                tagSpan.className = 'game-tags-background';
                tagSpan.textContent = tag;
                tagsDiv.appendChild(tagSpan);
            });

            return tagsDiv.outerHTML;
        }
    </script>
</main>
</body>
</html>