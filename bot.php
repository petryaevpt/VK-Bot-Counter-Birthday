<?php

require_once 'config.php';
require_once 'functions.php';

$mysqli = new mysqli($db_host, $db_name, $db_pass, $db_database);
if ($mysqli->connect_error) {
  die("Connection failed: " . $mysqli->connect_error);
}

$data = json_decode(file_get_contents('php://input'), true);

switch ($data['type']) {
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
  $percent = getPercent($mysqli, $peer_id);

  if (($validate_message != 0)) {
    messages_send($group_token, $peer_id,
    "Хорошо, понял. Теперь ты сможешь узнать процент.");
    insertOrUpdateBirthday($mysqli, $peer_id, $validate_message);
  } else {
      switch ($message) {
        case 'инфо':
          messages_send($group_token, $peer_id,
          "Привет, {$user_name}, меня зовут Хандра-Бот. Я могу посчитать прогресс года с Дня рождения. \nЕсли ещё не успел, напиши день своего рождения в формате ДД.ММ.ГГГГ. \nПосле этого можешь написать «Процент». \nТакже ты можешь попросить меня уведомлять тебя о повышении процента, написав «Прогресс». \nЕсли захочешь отключить эту функцию, можешь сказать мне «Хватит».");
        break;

        case 'процент':
          if (is_numeric($percent)) {
            messages_send($group_token, $peer_id,
            "Ого, с твоего дня рождения прошло уже {$percent}% года!");
          } else{
              messages_send($group_token, $peer_id, $percent);
            }
        break;

        case 'прогресс':
          if (is_numeric($percent) && !checkPermission($mysqli, $peer_id)) {
            messages_send($group_token, $peer_id,
            "Окей, буду напоминать тебе о каждом повышении процента, мне не сложно! Для отключения функции напиши «Хватит».");

            updatePermission($mysqli, $peer_id);
          } elseif (is_numeric($percent) && checkPermission($mysqli, $peer_id)) {
              messages_send($group_token, $peer_id,
              "Хей, мы же уже договорились, я буду напоминать тебе повышении процента. Для отключения функции напиши «Хватит».");
            } else {
                messages_send($group_token, $peer_id, "{$percent}");
              }
        break;

        case 'хватит':
          if (is_numeric($percent) && checkPermission($mysqli, $peer_id)) {
            messages_send($group_token, $peer_id,
            "Эх, ладно, хоть отдохну:)");

            updatePermission($mysqli, $peer_id);
          } else {
              messages_send($group_token, $peer_id, "Что, прости?");
            }
        break;

        default:
          messages_send($group_token, $peer_id,
          "{$user_name}, если не знаешь, что сказать, пиши «Инфо».");
        break;
      }
    }

    echo 'ok';
  break;

  default:
    echo 'ok';
  break;
}

$mysqli->close();
