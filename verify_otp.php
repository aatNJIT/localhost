<?php

session_start();
$errorMessage = '';

if (!isset($_SESSION['temp_userid'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['otp'])) {
    require_once('rabbitMQ/RabbitClient.php');
    require_once('identifiers.php');
    require_once('logger.php');

    $userid = $_SESSION['temp_userid'];
    $otp = trim($_POST['otp']);

    if (empty($otp) || strlen($otp) != 6 || !ctype_digit($otp)) {
        $errorMessage = 'Please enter a valid 6-digit code';
    } else {
        $request = [
            'type' => RequestType::TWO_FA_VERIFY,
            Identifiers::USER_ID => $userid,
            Identifiers::OTP => $otp
        ];
        $response = RabbitClient::getConnection()->send_request($request);
        
        if (is_array($response)) {
            session_regenerate_id();
            $_SESSION[Identifiers::USER_ID] = $response[Identifiers::USER_ID];
            $_SESSION[Identifiers::SESSION_ID] = $response[Identifiers::SESSION_ID];
            $_SESSION[Identifiers::USERNAME] = $response[Identifiers::USERNAME];
            $_SESSION[Identifiers::STEAM_ID] = $response[Identifiers::STEAM_ID];
            
            if (isset($response[Identifiers::STEAM_ID])) {
                $request = ['type' => RequestType::PROFILE, Identifiers::STEAM_ID => $_SESSION[Identifiers::STEAM_ID]];
                $response = RabbitClient::getConnection("SteamAPI")->send_request($request);
                $_SESSION[Identifiers::STEAM_PROFILE] = $response;
            }

            unset($_SESSION['temp_userid']);
            unset($_SESSION['temp_username']);
            header('Location: profile.php');
            exit();
        } else {
            log_message("User failed to verify OTP: $userid");
            $errorMessage = 'Invalid or expired OTP code';
        }
    }
}

$displayUsername = isset($_SESSION['temp_username']) ? htmlspecialchars($_SESSION['temp_username']) : '';
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Verify OTP</title>
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
                    <a href="login.php"> <i class="fa-solid fa-arrow-left">
                        </i> Back to Login
                    </a>
                </li>
            </ul>
        </nav>
    </article>

    <article>
        <h2 style="text-align: center">
            <i class="fa-solid fa-shield-halved"></i> Two-Factor Authentication
        </h2>
        <p style="text-align: center">
            <?php if ($displayUsername): ?>
                Welcome, <strong><?= $displayUsername ?></strong>!<br>
            <?php endif; ?>
            A 6-digit verification code has been sent to your email.<br>
            Please enter it below to complete login.
        </p>
    </article>

    <?php if ($errorMessage): ?>
        <article class="error">
            <?= $errorMessage ?>
        </article>
    <?php endif; ?>

    <form method="post">
        <div class="container" style="margin-top: 1rem; padding-left: 12px; padding-right: 12px">
            <label><b>Verification Code</b></label>
            <label>
                <input 
                    type="text" 
                    placeholder="Enter 6-digit code" 
                    name="otp" 
                    required 
                    maxlength="6" 
                    pattern="[0-9]{6}"
                    autocomplete="off"
                    autofocus
                    style="text-align: center; font-size: 1.5em; letter-spacing: 0.3em;">
            </label>

            <button type="submit" style="border: 2px var(--pico-form-element-border-color) solid">
                <i class="fa-solid fa-check"></i> Verify Code
            </button>
        </div>
    </form>

    <article style="background-color: var(--pico-card-background-color); border: none;">
        <small style="text-align: center; display: block;">
            <i class="fa-solid fa-clock"></i> Code expires in 5 minutes<br>
            <i class="fa-solid fa-envelope"></i> Didn't receive the code? Check your spam folder
        </small>
    </article>
</main>
</body>
</html>
