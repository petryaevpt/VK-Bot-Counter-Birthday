<?php
  ini_set("display_errors",1);
  error_reporting(E_ALL);

  /*
  Не активирован!

  Cron-запрос: crontab -e 0 9 * * * /var/www/html/petryaevpt/BotBDCounter/cron.php
  (Включать каждый день в 9 утра)
  Отключени: crontab -r

  TODO: Спрашивать у пользователя: нужны ли ему пуши об увеличении прогресса, если да, запускать крон каждый день в 9 утра (при условии, если процент повысился)
  */

  require_once 'config.php';
  require_once 'functions.php';

  $peer_id = $data['object']['peer_id'] ?: $data['object']['user_id'];

  $user_info = users_get($access_token, $peer_id)[0];
  $user_name = $user_info['first_name'];

  $percent = getPercent($mysqli, $peer_id);

  $message = "{$user_name}, смотри-ка! Ты стал ещё на процент года ближе к Дню Рождения! С последнего прошло уже {$percent}!";

  $result = messages_send($group_token, $peer_id, $message);

  print_r($result);
	echo PHP_EOL;
