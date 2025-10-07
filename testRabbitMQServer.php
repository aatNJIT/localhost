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
    // lookup username in database
    // check password
    return true;
    //return false if not valid
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
