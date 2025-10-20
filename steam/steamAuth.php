<?php
ob_start();
session_start();
function loginButton($buttonstyle = "square")
{
    $button['rectangle'] = "01";
    $button['square'] = "02";
    $button = "<a href='?login'> <img src='https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_" . $button[$buttonstyle] . ".png'></a>";
    echo $button;
}

if (isset($_GET['login'])) {
    require 'openId.php';
    try {
        require 'steamConfig.php';
        $openid = new LightOpenID($steamAuth['domainname']);

        if (!$openid->mode) {
            $openid->identity = 'https://steamcommunity.com/openid';
            header('Location: ' . $openid->authUrl());
        } elseif ($openid->mode == 'cancel') {
            echo 'User has canceled authentication!';
        } else {
            if ($openid->validate()) {
                $id = $openid->identity;
                $ptn = "/^https?:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
                preg_match($ptn, $id, $matches);

                $_SESSION['steamid'] = $matches[1];
                if (!headers_sent()) {
                    header('Location: ' . $steamAuth['loginpage']);
                } else {
                    ?>
                    <script type="text/javascript">
                        window.location.href = "<?=$steamAuth['loginpage']?>";
                    </script>
                    <noscript>
                        <meta http-equiv="refresh" content="0;url=<?= $steamAuth['loginpage'] ?>"/>
                    </noscript>
                    <?php
                }
                exit();
            } else {
                echo "User is not logged in.\n";
            }
        }
    } catch (ErrorException $e) {
        echo $e->getMessage();
    }
}

if (isset($_GET['logout'])) {
    require 'steamConfig.php';
    session_unset();
    session_destroy();
    header('Location: ' . $steamAuth['logoutpage']);
    exit();
}

if (isset($_GET['update'])) {
    unset($_SESSION['steam_uptodate']);
    require 'suppliers/userProfile.php';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}