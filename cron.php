<?php
  require_once 'config.php';
  require_once 'functions.php';

  $mysqli = new mysqli($db_host, $db_name, $db_pass, $db_database);
  if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
  }

  $users_id = getUsersIdForCron($mysqli);

  foreach ($users_id as $peer_id) {
    if (checkPermission($mysqli, $peer_id)) {
      $user_info = users_get($access_token, $peer_id)[0];
      $user_name = $user_info['first_name'];

      $percent = getPercent($mysqli, $peer_id);

      if (is_numeric($percent) && updatePercent($mysqli, $peer_id, $percent)) {
        $message = "{$user_name}, смотри-ка! \nС последнего дня рождения прошло уже {$percent}% года!";
        $result = messages_send($group_token, $peer_id, $message);
      }
    }
  }

  $mysqli->close();
