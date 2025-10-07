<?php
session_start();

$IDLE_TIMEOUT = 900; // 15 minutes

// If there's no username in the session, send the user back to the register/login page
if (empty($_SESSION['username'])) {
    header('Location: register.html');
    exit();
}

// If last activity exists, enforce idle timeout
if (!empty($_SESSION['last_active']) && (time() - (int)$_SESSION['last_active'] > $IDLE_TIMEOUT)) {
    // Expired: clear session and redirect to register/login
    session_unset();
    session_destroy();
    header('Location: register.html');
    exit();
}

// Update last activity timestamp
$_SESSION['last_active'] = time();

// Safely escape the username for HTML output
$username_safe = htmlspecialchars($_SESSION['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <title>IT-490 - Logged In</title>
  <link rel="stylesheet" href="css/pico.min.css">
  <link rel="stylesheet" href="css/custom.css">
  <style>
    /* small inline style guard in case custom.css missing */
    .container { padding: 1.5rem; }
    nav ul { display:flex; gap:1rem; list-style:none; align-items:center; padding:0; }
    nav img { height:40px; }
  </style>
</head>
<body>
<main>
  <nav>
    <ul>
      <img src="assets/albert.gif" alt="albert">
      <li><strong>IT-490</strong></li>
    </ul>
    <ul>
      <li><a href="profile.html">Profile</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>

  <div class="container">
    <h1>Welcome, <?php echo $username_safe; ?>!</h1>
    <p>You are now logged in.</p>
    <p><small>Last activity: <?php echo date('Y-m-d H:i:s', $_SESSION['last_active']); ?></small></p>
  </div>
</main>
</body>
</html>