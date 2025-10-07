#!/usr/bin/php
<?php
error_reporting(E_ALL & ~E_DEPRECATED);  // show everything except deprecated
ini_set('display_errors', 1);


require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('mysqlconnect.php'); // automatically creates database connection

function doLogin($username,$password)
{
  global $mydb;

  if (empty($username) || empty($password)) {
    return array("returnCode" => '1', 
    'message'=>"Username or Password cannot be empty. Please try again.");
  }

  $stmt = $mydb->prepare("SELECT password FROM users WHERE username = ?");
  if (!$stmt) {
    return array("returnCode" => '1', 
    'message'=>"Database Error: " . $mydb->error);
  }

  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    $stmt->close();
    return array("returnCode" => '1', 
    'message'=>"Invalid username or password. Please try again.");
  }

  $row = $result->fetch_assoc();

  if (!password_verify($password, $row['password'])) {
    $stmt->close();
    return array("returnCode" => '1', 
    'message'=>"Invalid username or password. Please try again.");
  }

  $stmt->close();
  return array("returnCode" => '0', 'message'=>"Login successful");

  $sessionId = bin2hex(random_bytes(32));
  $userId = $user['id'];
  $currentTimestamp = date('Y-m-d H:i:s');

  $stmt = $mydb->prepare("INSERT INTO sessions (session_id, user_id, last_active) VALUES (?, ?, ?)");
  if (!$stmt) {
    return array("returnCode" => '1', 
    'message'=>"Database Error: " . $mydb->error);
  }

  $stmt->bind_param("sis", $sessionId, $userId, $currentTimestamp);
  $stmt->execute();
  $stmt->close();

  return array("returnCode" => '0', 'message'=>"Login successful");

  if (!$stmt->execute()) {
    return array("returnCode" => '1', 
    'message'=>"Database Error: " . $stmt->error);
  }

  $stmt->close();

  return array("returnCode" => '0', 'message'=>"Login successful", "sessionId" => $sessionId, "userId" => $userId, "username" => $username);

}

function doLogout($sessionId)
{
  global $mydb;
  
  if (empty($sessionId)) {
    return array("returnCode" => '1', 
    'message'=>"Session ID is required and cannot be empty.");
  }

  $stmt = $mydb->prepare("DELETE FROM sessions WHERE session_id = ?");
  if (!$stmt) {
    return array("returnCode" => '1', 
    'message'=>"Database Error: " . $mydb->error);
  }

  $stmt->bind_param("s", $sessionId);
  $stmt->execute();
  $result = $stmt->get_result();
  $stmt->close();

  if ($result->num_rows === 0) {
    return array("returnCode" => '1', 
    'message'=>"Invalid or expired session ID.");
  }

  $sessionData = $result->fetch_assoc();

  $stmt->close();
  return array("returnCode" => '0', 'message'=>"Logout successful");

  $stmt = $mydb->prepare("DELETE FROM sessions WHERE session_id = ?");
  if (!$stmt) {
    return array("returnCode" => '1', 
    'message'=>"Database Error: " . $mydb->error);
  }

  $stmt->bind_param("s", $sessionId);
  if (!$stmt->execute()) {
    return array("returnCode" => '1',
    'message'=>"Failed to invalidate session: " . $stmt->error);
  }

  $stmt->store_result();
  $stmt->bind_result($sessionId, $userId, $lastActive);
  $stmt->fetch();

  $stmt->close();

  return array("returnCode" => '0', 'message'=>"Logout successful", "sessionId" => $sessionId, "userId" => $userId, "lastActive" => $lastActive);

}

function doRegister($username, $password)
{
  global $mydb;

  if ($username === '' || $password === '') {
    echo "Error: Missing username or password." . PHP_EOL;
    return ['insertSuccess' => false, 'error' => 'missing_fields'];
  }

  // Prepare insert for registration
  $stmt = $mydb->prepare("INSERT INTO Users (Username, Password) VALUES (?, ?)");
  
  if (!$stmt) {
    // Prepare registartion failed 
    echo "Prepare failed." . PHP_EOL;
    return ['insertSuccess' => false, 'error' => 'db_prepare_failed'];
  }
  
  $stmt->bind_param("ss", $username, $password);
  $insertSuccess = $stmt->execute();

  if (!$insertSuccess) {
      if ($stmt->errno == 1062) {
        // Duplicate username
        echo "Username already exists: Try again." . PHP_EOL;
        $stmt->close();
        return ['insertSuccess' => false, 'error' => 'username_exists'];
      }
      
      $err = $stmt->error;
      echo "Insert failed." . PHP_EOL;
      $stmt->close();
      return ['insertSuccess' => false, 'error' => 'db_error'];
  }

  $stmt->close();
  return ['insertSuccess' => true];
}


function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  var_dump($request);
  if(!isset($request['type']))
  {
    return "ERROR: unsupported message type";
  }
  switch ($request['type'])
  {
    case "login":
      return doLogin($request['username'],$request['password']);

    case "register":
      return doRegister($request['username'],$request['password']);

    case "validate_session":
      return doValidate($request['sessionId']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>
