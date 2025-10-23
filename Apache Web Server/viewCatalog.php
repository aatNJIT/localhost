<?php
session_start();
require_once('identifiers.php');
require_once('steam/SteamUtils.php');
require_once('rabbitMQ/RabbitClient.php');

if (!isset($_GET['catalogid']) || !is_numeric($_GET['catalogid'])) {
    header("Location: index.php");
    exit();
} else {
    $catalogID = $_GET['catalogid'];
}

$errorMessage = '';
$successMessage = '';

if (isset($_GET['error'])) {
    $errorMessage = $_GET['error'];
} else if (isset($_GET['success'])) {
    $successMessage = $_GET['success'];
}

?>

<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html" data-theme="dark">

<head>
    <title>IT-490 - Catalog</title>
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
                        <li>
                            <a href="viewUserCatalogs.php?userid=<?= $_SESSION[Identifiers::USER_ID] ?>"> <i
                                        class="fa-solid fa-list"></i> My Catalogs
                            </a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a href="logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </li>
                <?php endif; ?>

            </ul>
        </nav>
    </article>

    <?php if ($successMessage): ?>
        <article class="success">
            <?= $successMessage ?>
        </article>
    <?php elseif ($errorMessage): ?>
        <article class="error">'
            <?= $errorMessage ?>
        </article>'
    <?php endif; ?>

    <article class="bordered-article" style="text-align: center">
        <?php
        $catalogRequest = ['type' => RequestType::GET_CATALOG, Identifiers::CATALOG_ID => $catalogID];
        $catalog = RabbitClient::getConnection()->send_request($catalogRequest);

        $commentsRequest = ['type' => RequestType::GET_CATALOG_COMMENTS, Identifiers::CATALOG_ID => $catalogID];
        $comments = RabbitClient::getConnection()->send_request($commentsRequest);
        ?>

        <?php if (is_array($catalog) && !empty($catalog)): ?>
            <div style="max-height: 45vh; overflow-y: auto; padding-right: 1rem;">

                <div style="margin-bottom: 0.5rem; font-weight: bold; font-size: 1.2rem;">
                    <?= $catalog['Title'] ?> (<?= count($catalog['games']) ?> games)
                </div>

                <label for="librarySearch">
                    <input type="search" id="librarySearch" placeholder="Search library..."
                           style="margin-bottom: 1rem;">
                </label>

                <div style="margin-bottom: 1rem; padding-left: 1rem;">
                    <?php if (!empty($catalog['games'])): ?>
                        <?php foreach ($catalog['games'] as $game): ?>
                            <?php
                            $appid = $game['AppID'];
                            $rating = $game['Rating'];
                            $name = $game['Name'];
                            $tags = json_decode($game['Tags'], true);
                            ?>
                            <div class="game-div">
                                <img src="<?= SteamUtils::getAppImage($appid) ?>" alt="<?= $name ?>"
                                     style="max-width:120px; border-radius:2px; margin-right:1rem;">
                                <div style="text-align:left;">
                                    <strong><?= $name ?></strong><br>
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
            </div>

        <?php else: ?>
            <p style="text-align: center; margin-top: 1rem">Catalog not found</p>
        <?php endif; ?>
    </article>

    <article class="bordered-article" style="text-align: center;">
        <div style="max-height: 50vh; overflow-y: auto; padding-right: 1rem;">
            <div style="margin-bottom: 0.5rem; font-weight: bold; font-size: 1.2rem;">
                Comments
            </div>
            <?php if (isset($_SESSION[Identifiers::USER_ID]) && isset($_SESSION[Identifiers::SESSION_ID])): ?>
                <form method="POST" action="addComment.php" style="display: flex;">
                    <input type="hidden" name="catalogid" value="<?= $catalogID ?>">
                    <input type="text" maxlength="255" minlength="1" style="width: 350%; height: 2.5rem;" name="comment"
                           placeholder="Add a comment..." required>
                    <button type="submit"
                            style="height: 2.5rem; padding: 0 1rem; margin-left: 0.5rem; white-space: nowrap">
                        Add Comment
                    </button>
                </form>
            <?php endif; ?>

            <div style="margin-top: 1rem; text-align: left;">
                <?php if (is_array($comments) && !empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
                        <?php
                        $userRequest = ['type' => RequestType::GET_USER, Identifiers::USER_ID => $comment['UserID']];
                        $user = RabbitClient::getConnection()->send_request($userRequest);
                        $username = is_array($user) && isset($user['Username']) ? $user['Username'] : 'Anonymous';
                        ?>
                        <div style="border-bottom: 1px solid var(--pico-muted-border-color); padding: 1rem 0;">
                            <div style="font-weight: bold; margin-bottom: 0.25rem;">
                                <?= htmlspecialchars($username) ?>
                            </div>
                            <div style="color: var(--pico-color);">
                                <?= htmlspecialchars($comment['Text']) ?>
                            </div>
                            <div style="color: var(--pico-muted-color); font-size: 0.875rem; margin-top: 0.25rem;">
                                <?= htmlspecialchars($comment['Created']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--pico-muted-color); margin-top: 1rem;">No comments
                        yet.</p>
                <?php endif; ?>
            </div>

        </div>
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