<?php
session_start();
require_once('session.php');
require_once('identifiers.php');

if (!isset($_SESSION[Identifiers::STEAM_ID])) {
    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Game Recommendations</title>
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
                    <a href="users.php">
                        <i class="fa-solid fa-users"></i> Users
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <i class="fa-solid fa-user"></i> Profile
                    </a>
                </li>
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
                    <a href="viewUserCatalogs.php?userid=<?= $_SESSION[Identifiers::USER_ID] ?>">
                        <i class="fa-solid fa-list"></i> My Catalogs
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
        <h3><i class="fa-solid fa-fire"></i> Your Most Played Games</h3>
        <div id="topGamesContent">
            <div aria-busy="true"
                 style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                Loading your most played games...
            </div>
        </div>
    </article>
    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">
        <h3><i class="fa-solid fa-tags"></i> Your Favorite Genres</h3>
        <div id="genresContent">
            <div aria-busy="true"
                 style="min-height: 100px; display: flex; align-items: center; justify-content: center;">
                Analyzing your games...
            </div>
        </div>
    </article>
    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">
        <h3><i class="fa-solid fa-lightbulb"></i> Recommended For You</h3>
        <div id="recommendationsContent">
            <div aria-busy="true"
                 style="min-height: 300px; display: flex; align-items: center; justify-content: center;">
                Loading your game recommendations...
            </div>
        </div>
    </article>
</main>

<script>
    async function loadRecommendations() {
        try {
            const response = await fetch('getRecommendations.php');
            const data = await response.json();

            const topGames = document.getElementById('topGamesContent');
            if (data.topGames && data.topGames.length > 0) {
                topGames.innerHTML = data.topGames.map(game => `
                    <div style="display: flex; align-items: center; padding: 0.5rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px;">
                        <img src="${game.image}"
                             alt="${escapeHtml(game.name)}"
                             style="max-width: 120px; border-radius: 2px; margin-right: 1rem;">
                        <div style="text-align: left;">
                            <strong>${escapeHtml(game.name)}</strong><br>
                            <small style="color: var(--pico-muted-color);">
                                <i class="fa-solid fa-clock"></i>
                                ${game.hours} hours played
                            </small>
                            ${game.tags ? `<br><small style="color: var(--pico-primary);">
                                <i class="fa-solid fa-tags"></i> ${escapeHtml(game.tags)}
                            </small>` : ''}
                        </div>
                    </div>
                `).join('');
            } else {
                topGames.innerHTML = `
                    <p style="color: var(--pico-muted-color);">
                        No games played yet. Start playing to get recommendations!
                    </p>
                `;
            }

            const genres = document.getElementById('genresContent');
            if (data.topTags && data.topTags.length > 0) {
                genres.innerHTML = `
                    <p style="color: var(--pico-muted-color); font-size: 0.9rem;">
                        Based on your most played games
                    </p>
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 0.5rem;">
                        ${data.topTags.map(tag => `
                            <span style="background: var(--pico-primary-background); padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem;">
                                ${escapeHtml(tag)}
                            </span>
                        `).join('')}
                    </div>
                `;
            } else {
                genres.innerHTML = `
                    <p style="color: var(--pico-muted-color);">
                        Play more games to discover your favorite genres!
                    </p>
                `;
            }

            const recommendations = document.getElementById('recommendationsContent');
            if (data.suggestions && data.suggestions.length > 0) {
                recommendations.innerHTML = `
                    <p style="color: var(--pico-muted-color); font-size: 0.9rem;">
                        Games similar to what you play most
                        ${data.topTags && data.topTags.length > 0 ? `(${escapeHtml(data.topTags[0])})` : ''}
                    </p>
                    ${data.suggestions.map(game => `
                        <div style="display: flex; align-items: center; padding: 0.5rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px;">
                            <img src="${game.image}"
                                 alt="${escapeHtml(game.name)}"
                                 style="max-width: 120px; border-radius: 2px; margin-right: 1rem;">
                            <div style="text-align: left;">
                                <strong>${escapeHtml(game.name)}</strong><br>
                                <small style="color: var(--pico-muted-color);">
                                    ${game.rating > 0 ? `
                                        <i class="fa-solid fa-thumbs-up"></i> ${game.rating}% positive (${game.reviews.toLocaleString()} reviews)
                                    ` : ''}
                                </small>
                            </div>
                        </div>
                    `).join('')}
                `;
            } else {
                recommendations.innerHTML = `
                    <p style="color: var(--pico-muted-color);">
                        Play more games to get personalized recommendations based on your favorite genres!
                    </p>
                `;
            }
        } catch (error) {
            document.getElementById('topGamesContent').innerHTML = `
                <p style="color: red">
                    <i class="fa-solid fa-exclamation-triangle"></i> Error loading recommendations.
                </p>
            `;
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    loadRecommendations();
</script>
</body>
</html>