<?php
session_start();
require_once('rabbitMQ/RabbitClient.php');
require_once('steam/SteamUtils.php');
require_once('identifiers.php');

$errorMessage = '';
$successMessage = '';

if (isset($_GET['error'])) {
    $errorMessage = $_GET['error'];
} else if (isset($_GET['success'])) {
    $successMessage = $_GET['success'];
}

$followingUserIDs = [];
if (isset($_SESSION[Identifiers::USER_ID])) {
    $followRequest = ['type' => RequestType::GET_USER_FOLLOWING, Identifiers::USER_ID => $_SESSION[Identifiers::USER_ID]];
    $followingResponse = RabbitClient::getConnection()->send_request($followRequest);
    if (is_array($followingResponse)) {
        $followingUserIDs = $followingResponse;
    }
}
?>

<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html" data-theme="dark">

<head>
    <title>IT-490 - All Users</title>
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
                        <a href="profile.php"> <i class="fa-solid fa-user"></i> Profile</a>
                    </li>
                <?php endif; ?>

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

                <?php if (isset($_SESSION[Identifiers::SESSION_ID]) && isset($_SESSION[Identifiers::USER_ID])): ?>
                    <li>
                        <a href="viewUserCatalogs.php?userid=<?= $_SESSION[Identifiers::USER_ID] ?>"> <i
                                    class="fa-solid fa-list"></i> My Catalogs
                        </a>
                    </li>

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

    <article class="bordered-article" style="margin-top: 1rem; text-align: center; padding: 1rem;">
        <?php
        $response = RabbitClient::getConnection()->send_request(['type' => RequestType::GET_ALL_USERS]);
        ?>

        <?php if (is_array($response) && !empty($response)): ?>
            <div style="max-height: 80vh; overflow-y: auto; padding-right: 1rem;">
                <?php foreach ($response as $user): ?>
                    <?php
                    $username = htmlspecialchars($user['Username']);
                    $userID = htmlspecialchars($user['ID']);
                    $isFollowing = !empty($followingUserIDs) && in_array($userID, $followingUserIDs[0]);

                    $followingDate = null;
                    if (!empty($followingUserIDs)) {
                        foreach ($followingUserIDs as $follower) {
                            if ($follower['FollowedID'] == $userID) {
                                $followingDate = $follower['Created'] ?? null;
                                break;
                            }
                        }
                    }

                    $isCurrentUser = isset($_SESSION[Identifiers::USER_ID]) && $userID == $_SESSION[Identifiers::USER_ID];
                    ?>

                    <div class="game-div" style="justify-content: space-between">
                        <div style="text-align: left;">
                            <strong class="steam-username">Username: <?= $username ?></strong>
                            <?php if ($isCurrentUser): ?>
                                <span style="color: var(--pico-primary); margin-left: 0.5rem;">(You)</span>
                            <?php endif; ?>
                            <?php if ($isFollowing): ?>
                                <span style="color: var(--pico-secondary); margin-left: 0.5rem;"><i
                                            class="fa-solid fa-check"></i>
                                    Following
                                </span>
                                <span style="color: var(--pico-secondary); margin-left: 0.5rem;">
                                    Followed: <?= $followingDate ? date('Y-m-d', strtotime($followingDate)) : '' ?>
                                </span>
                            <?php endif; ?>
                            <br>
                            <small style="color: var(--pico-muted-color);"><?= $user['SteamID'] ?? "N/A" ?></small>
                        </div>

                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem;">
                            <form method="get" action="viewUserCatalogs.php">
                                <input type="hidden" name="userid" value="<?= $userID ?>">
                                <button type="submit" style="margin-top: 1rem">
                                    <i class="fa-solid fa-magnifying-glass"></i>View Catalogs
                                </button>
                            </form>

                            <a href="message.php?userid=<?= $userID ?>" style="margin-top: 1rem; display: inline-block">
                                <button type="button">
                                    <i class="fa-regular fa-message"></i> Message
                                </button>
                            </a>

                            <?php if (isset($_SESSION[Identifiers::USER_ID]) && !$isCurrentUser): ?>
                                <?php if ($isFollowing): ?>
                                    <form method="post" action="unfollowUser.php">
                                        <input type="hidden" name="userid" value="<?= $userID ?>">
                                        <input type="hidden" name="username" value="<?= $username ?>">
                                        <button type="submit" style="margin-top: 1rem; background-color: var(--pico-mark-background-color);">
                                            <i class="fa-solid fa-minus"></i> Unfollow
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="followUser.php">
                                        <input type="hidden" name="userid" value="<?= $userID ?>">
                                        <input type="hidden" name="username" value="<?= $username ?>">
                                        <button type="submit" style="margin-top: 1rem">
                                            <i class="fa-solid fa-plus"></i> Follow
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center;">No Users Found.</p>
        <?php endif; ?>

    </article>
</main>
</body>
</html>