<?php
  ini_set("display_errors",1);
  error_reporting(E_ALL);
  /*
  Выключен!
  */
  require_once 'config.php';
  require_once 'functions.php';

  $mysqli = new mysqli("localhost", "root", "root", "petryaev_bd_counter_bot");
  if ($mysqli->connect_error)
  {
    die("Connection failed: " . $mysqli->connect_error);
  }

  $users_id = getUsersIdForCron($mysqli);

  foreach ($users_id as $peer_id)
  {
    if (checkPermission($mysqli, $peer_id))
    {
      $user_info = users_get($access_token, $peer_id)[0];

      $user_name = $user_info['first_name'];
      $percent = getPercent($mysqli, $peer_id);

      if (is_numeric($percent) && updatePercent($mysqli, $peer_id, $percent))
      {
        $message = "{$user_name}, смотри-ка! Ты стал ещё на процент года ближе к Дню Рождения!
        С последнего прошло уже {$percent}%!";
        $result = messages_send($group_token, $peer_id, $message);
      }
    }
  }

  $mysqli->close();
