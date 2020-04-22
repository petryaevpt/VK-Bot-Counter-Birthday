<?php

//Удобно печает массив
function dd($value)
{
  echo '<pre>';
  print_r($value);
  echo '</pre>';
}

//Отправляет сообщение
function messages_send($group_token, $peer_id, $message)
{
  api($group_token, 'messages.send', array(
  'peer_id' => $peer_id,
  'message' => $message,
  'random_id' => rand(999999,9999999),
   ));
}

//Возвращает расширенную информацию о пользователях
function users_get($group_token, $user_id)
{
  return api($group_token, 'users.get', array(
    'user_ids' => $user_id,
    ));
}

//Callback API
function api($access_token, $method, $params)
{
  $params['access_token'] = $access_token;
  $params['v'] = '5.100';
  $query = http_build_query($params);
  $url = 'https://api.vk.com/method/' . $method . '?' . $query;

  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $json = curl_exec($curl);
  $error = curl_error($curl);
  if ($error) {
    error_log($error);
    throw new Exception("Неудачный запрос: {$method}.");
  }
  curl_close($curl);

  $response_api = json_decode($json, true);
  if (!$response_api || !isset($response_api['response'])) {
    error_log($json);
    throw new Exception("Неверный ответ на запрос: {$method}.");
  }
  return $response_api['response'];
}

//Получаем процент с момента ДР
function getPercent($mysqli, $user_id)
{
  $sql = "SELECT birthday_date FROM users_bday WHERE users_id = '{$user_id}';";
  $result = $mysqli->query($sql);

  if ($result->num_rows != 0) {
    while ($row = $result->fetch_assoc()) {
      $days_in_year = date('L') ? 366 : 365;

      $now_date = new DateTime();
      $birthday_date = $row['birthday_date'];
      $birthday = new DateTime($birthday_date);

      $diff = $birthday->diff($now_date);
      $diff_days = $diff->days;

      $percent_raw = $diff_days/$days_in_year;
      $percent = round($percent_raw * 100);

      if ($percent >= 100) {
        $percent -= 100;
      }

      return $percent;
    }
  } else {
      return 'Сначала пришли мне день своего рождения в формате ДД.ММ.ГГГГ';
    }
}

/*Cron проверяет процент и обновляет, его, если фактический привысил тот,
что находится в базе данных.
Таким образом обеспечивается отправка напоминаний раз в процент
*/
function updatePercent($mysqli, $user_id, $percent)
{
  $sql = "SELECT progress FROM users_bday WHERE users_id = '{$user_id}';";
  $result = $mysqli->query($sql);

  while ($row = $result->fetch_assoc()) {
    if ($row['progress'] != $percent) {
      $update = "UPDATE users_bday SET progress='{$percent}' WHERE users_id = {$user_id};";
      $mysqli->query($update);
      return 1;
    }
  }
}

//Возвращает либо возраст пользователя, либо 0, если ДР отличается на год от актуальной даты
function checkBDate($bdate)
{
  $days_in_year = date('L') ? 366 : 365;

  $datetime = new DateTime();
  $birthday = new DateTime($bdate);
  $diff_first = $birthday->diff($datetime);

  $diff_all_days = $diff_first->days;
  $age = floor($diff_all_days / 365);

  $age_for_modify = '+' . $age . 'years';
  $last_birthday = $birthday->modify($age_for_modify); //Последний день рождения пользователя

  $diff_second = $last_birthday->diff($datetime);
  $diff_days = $diff_second->days;

  if (($diff_days < $days_in_year)) {
    return $age;
  }
    return 0;
}

//Заполнение базы данных
function insertOrUpdateBirthday($mysqli, $user_id, $message)
{
  $select = "SELECT birthday_date, progress FROM users_bday WHERE users_id = '{$user_id}';";
  $result = $mysqli->query($select);

  //Если есть поле с таким $user_id, перезаписываем дату, иначе создаём поле
  if($result->num_rows != 0) {
    $update = "UPDATE users_bday SET birthday_date='{$message}' WHERE users_id = {$user_id};";
    $mysqli->query($update);
  } else {
      $insert = "INSERT INTO users_bday (users_id, birthday_date) VALUES ('{$user_id}', '{$message}');";
      $mysqli->query($insert);
    }
}

//Проверка введённой пользователем даты и приведение её к необходимому для БД формату
function ConversionDate($message)
{
  if (strlen($message) != 10) {
    return 0;
  }

  $day = mb_substr($message, 0, 2, 'utf-8');
  $month = mb_substr($message, 3, 2, 'utf-8');
  $year = mb_substr($message, 6, 4, 'utf-8');
  $checkdate = $year . $month . $day;

  if(!ctype_digit($checkdate)) {
    return 0;
  }

  if (($day == 29) && ($month == 02)) {
    $day = 28;
  }

  $date = $year . '-' . $month . '-' . $day;

  $format = 'Y-m-d';
  $datetime = DateTime::createFromFormat($format, $date);

  if ($datetime->format($format) != $date) {
    return 0;
  }

  $check_date = checkBDate($date);

  if ($check_date == 0 || $check_date > 100) {
    return 0;
  }

  $year += $check_date;
  $date = $year . '-' . $month . '-' . $day;

  return $date;
}

//Проверка разрешения на рассылку
function checkPermission($mysqli, $user_id)
{
  $select = "SELECT permission FROM users_bday WHERE users_id = '{$user_id}';";
  $result = $mysqli->query($select);

  if($result->num_rows != 0) {
    while ($row = $result->fetch_assoc()) {
      if($row['permission'] == 0) {
        return 0;
      }

      return 1;
    }
  }
}

//Соглашение на рассылку или отказ от неё
function updatePermission($mysqli, $user_id)
{
  $select = "SELECT permission FROM users_bday WHERE users_id = '{$user_id}';";
  $result = $mysqli->query($select);

  while ($row = $result->fetch_assoc()) {
    if($row['permission'] == 0) {
      $update = "UPDATE users_bday SET permission='1' WHERE users_id = {$user_id};";
      $mysqli->query($update);
    } else {
        $update = "UPDATE users_bday SET permission='0' WHERE users_id = {$user_id};";
        $mysqli->query($update);
      }
  }
}

//Возвращает массив id всех пользователей, разрешивших рассылку
function getUsersIdForCron($mysqli)
{
  $select = "SELECT permission, users_id FROM users_bday;";
  $result = $mysqli->query($select);

  while ($row = $result->fetch_assoc()) {
    $array [] = array_values($row); //возвращает массив со всеми элементами массива $row
  }

  $peer_id = array_column($array, 1); // возвращает массив из значений массива $array с ключом 1

  return $peer_id;
}
