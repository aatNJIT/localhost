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
<main style="padding-left: 10vh; padding-right: 10vh;">
    <article style="border: 1px var(--pico-form-element-border-color) solid">
        <nav style="justify-content: center">
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

    <article
            style="margin-top: 1rem; text-align: center; border: 1px solid var(--pico-form-element-border-color); padding: 1rem;">
        <?php
        $request = ['type' => RequestType::GET_ALL_USERS];
        $response = RabbitClient::getConnection()->send_request($request);
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

                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px; transition: background-color .1s;"
                         onmouseover="this.style.backgroundColor='var(--pico-background-color)'"
                         onmouseout="this.style.backgroundColor='transparent'">

                        <div style="text-align: left;">
                            <strong style="font-size: 1.1rem;">Username: <?= $username ?></strong>
                            <?php if ($isCurrentUser): ?>
                                <span style="color: var(--pico-primary); margin-left: 0.5rem;">(You)</span>
                            <?php endif; ?>
                            <?php if ($isFollowing): ?>
                                <span style="color: var(--pico-secondary); margin-left: 0.5rem;"><i
                                            class="fa-solid fa-check"></i> Following</span>
                                <span style="color: var(--pico-secondary); margin-left: 0.5rem;">
                                    Followed: <?= $followingDate ? date('Y-m-d', strtotime($followingDate)) : '' ?>
                                </span>
                            <?php endif; ?>
                            <br>
                            <small style="color: var(--pico-muted-color);"><?= $user['SteamID'] ?? "N/A" ?></small>
                        </div>

                        <div style="display: flex; gap: 0.5rem; flex-shrink: 0">
                            <form method="get" action="viewUserCatalogs.php">
                                <input type="hidden" name="userid" value="<?= $userID ?>">
                                <button type="submit" style="margin-top: 1rem">View Catalogs</button>
                            </form>

                            <?php if (isset($_SESSION[Identifiers::USER_ID]) && !$isCurrentUser): ?>
                                <?php if ($isFollowing): ?>
                                    <form method="post" action="unfollowUser.php">
                                        <input type="hidden" name="userid" value="<?= $userID ?>">
                                        <input type="hidden" name="username" value="<?= $username ?>">
                                        <button type="submit"
                                                style="margin-top: 1rem; background-color: var(--pico-mark-background-color);">
                                            Unfollow
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="followUser.php">
                                        <input type="hidden" name="userid" value="<?= $userID ?>">
                                        <input type="hidden" name="username" value="<?= $username ?>">
                                        <button type="submit" style="margin-top: 1rem">Follow</button>
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