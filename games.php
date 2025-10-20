<?php
require_once('session.php');
require_once('steam/SteamUtils.php');
require 'steam/steamAuth.php';
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Login</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/pico.min.css">
    <link rel="stylesheet" href="css/custom.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
</head>

<body>
<main class="container" style="padding-left: 1rem; padding-right: 1rem">
    <article style="border: 1px var(--pico-form-element-border-color) solid">
        <nav>
            <ul>
                <li>
                    <strong>IT-490 <?php echo '[' . $_SESSION['username'] . ']' ?></strong>
                </li>
            </ul>
            <ul>
                <li>
                    <a href="index.php">
                        <i class="fa-solid fa-house"></i> Index
                    </a>
                </li>
                <li>
                    <a href="profile.php"> <i class="fa-solid fa-user"></i> Profile</a>
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
        include('steam/suppliers/userProfile.php');
        include('steam/suppliers/userGames.php');

        echo '<img src="' . $steamProfile["avatar"] . '" 
             alt="Steam Avatar" 
             style="border-radius: 4px; margin-bottom: 1rem;">
        <p>
            <strong>' . $steamProfile["personaname"] . '</strong><br>
            <small>Steam ID: ' . $steamProfile["steamid"] . '</small>
        </p>';

        if (!empty($steamgames['games'])) {
            $games = $steamgames['games'];

            usort($games, function ($a, $b) {
                return $b['playtime_forever'] - $a['playtime_forever'];
            });

            echo '<input type="search" id="librarySearch" placeholder="Search library..." style="margin-bottom: 1rem;">';
            echo '<div style="max-height: 80vh; overflow-y: auto; padding-right: 1rem;">';

            foreach ($games as $game) {
                $appid = $game['appid'];
                $name = $game['name'];

                echo '<div class="game-div" 
                            style="display: flex; 
                            align-items: center; 
                            padding: 1rem; 
                            margin-bottom: 0.4rem; 
                            border: 1px solid var(--pico-form-element-border-color); 
                            border-radius: 4px; 
                            cursor: pointer; 
                            transition: background-color .1s;" 
                            onmouseover="this.style.backgroundColor=\'var(--pico-background-color)\'" 
                            onmouseout="this.style.backgroundColor=\'transparent\'">';

                echo '<img src="' . SteamUtils::getAppImage($appid) . '" alt="' . $name . '" style="display: flex; max-width: 350px; border-radius: 2px; margin-right: 1rem;">';
                echo '<div style="text-align: left;">';
                echo '<strong style="font-size: 1.1rem;">' . $name . '</strong><br>';
                echo '<small style="color: var(--pico-muted-color);">' . SteamUtils::getGameTime($game) . ' hours played</small>';
                echo '</div>';
                echo '</div>';
            }

            echo '</div>';
        } else {
            echo '<p style="text-align: center;">No games found or profile is private.</p>';
        }
        ?>
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