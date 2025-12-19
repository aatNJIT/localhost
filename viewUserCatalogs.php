<?php
session_start();
require_once('identifiers.php');
require_once('steam/SteamUtils.php');
require_once('rabbitMQ/RabbitClient.php');

if (!isset($_GET[Identifiers::USER_ID]) || !is_numeric($_GET[Identifiers::USER_ID])) {
    if (isset($_SESSION[Identifiers::USER_ID])) {
        header("Location: viewUserCatalogs.php?userid=" . $_SESSION[Identifiers::USER_ID]);
    } else {
        header("Location: index.php");
    }
    exit();
} else {
    $userID = $_GET[Identifiers::USER_ID];
}
?>

<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html" data-theme="dark">

<head>
    <title>IT-490 - My Catalogs</title>
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
                    <?php endif; ?>
                    <li>
                        <a href="logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </li>
                <?php endif; ?>

            </ul>
        </nav>
    </article>

    <?php
    $catalogs = RabbitClient::getConnection()->send_request(['type' => RequestType::GET_USER_CATALOGS, Identifiers::USER_ID => $userID]);
    if (isset($_SESSION[Identifiers::USER_ID])) {
        $votes = RabbitClient::getConnection()->send_request(['type' => RequestType::GET_USER_VOTES, Identifiers::USER_ID => $_SESSION[Identifiers::USER_ID]]);
    } else {
        $votes = [];
    }
    ?>

    <?php if (is_array($catalogs) && !empty($catalogs)): ?>
        <?php foreach ($catalogs as $catalog): ?>
            <article class="bordered-article">
                <div style="display: inline-block;">
                    <strong><?= $catalog['Votes'] ?></strong>
                    <?php if (isset($_SESSION[Identifiers::USER_ID])): ?>
                        <?php $vote = $votes[$catalog['CatalogID']] ?? null; ?>
                        <a href="voteCatalog.php?catalogid=<?= urlencode($catalog['CatalogID']) ?>&action=up"
                           style="text-decoration: none; color: <?= $vote === 'up' ? '#FF4500' : 'inherit' ?>;">
                            <i class="fa-solid fa-arrow-up"></i>
                        </a>
                        <a href="voteCatalog.php?catalogid=<?= urlencode($catalog['CatalogID']) ?>&action=down"
                           style="text-decoration: none; color: <?= $vote === 'down' ? '#7193FF' : 'inherit' ?>;">
                            <i class="fa-solid fa-arrow-down"></i>
                        </a>
                    <?php endif; ?>
                    <a href="viewCatalog.php?catalogid=<?= urlencode($catalog['CatalogID']) ?>"
                       style="cursor: pointer;">
                        <strong><?= $catalog['Title'] ?></strong>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align: center; margin-top: 1rem">No catalogs found</p>
    <?php endif; ?>

</main>
</body>
</html>