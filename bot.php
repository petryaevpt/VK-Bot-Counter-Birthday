<?php
ini_set("display_errors",1);
error_reporting(E_ALL);

/*
Ссылка на группу во ВК, в которой можно проверить бота: https://vk.com/public188328735
*/

require_once 'config.php';
require_once 'functions.php';

$data = json_decode(file_get_contents('php://input'), true);

$mysqli = new mysqli("localhost", "root", "root", "petryaev_bd_counter_bot");
if ($mysqli->connect_error)
{
  die("Connection failed: " . $mysqli->connect_error);
}

switch ($data['type'])
{
  case 'confirmation':
    echo $confirmation_token;
  break;

  case 'message_new':
  $message = $data['object']['text'];
  $message = mb_strtolower($message);
  $peer_id = $data['object']['peer_id'] ?: $data['object']['user_id'];

  $user_info = users_get($access_token, $peer_id)[0];
  $user_name = $user_info['first_name'];

  $validate_message = conversionDate($message);

  if (($validate_message != 0) && (chekDate($validate_message)))
  {
    messages_send($group_token, $peer_id,
    "Хорошо, понял. Теперь ты сможешь узнать процент.");

    insertOrUpdate($mysqli, $peer_id, $validate_message);

  } elseif ($message == 'процент')
    {
      $percent = getPercent($mysqli, $peer_id);
      messages_send($group_token, $peer_id, "{$percent}");
      
    } else
      {
         messages_send($group_token, $peer_id,
         "{$user_name}, напиши, когда у тебя был ПОСЛЕДИЙ день рождения в формате ДД.ММ.ГГГГ. Если уже написал дату, пиши «Процент».");
      }

    echo('ok');
  break;

  default:
    echo('ok');
  break;
}

$mysqli->close();
