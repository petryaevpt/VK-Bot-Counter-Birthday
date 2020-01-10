<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);

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
    'fields' => 'bdate',
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
  if ($error)
  {
    error_log($error);
    throw new Exception("Неудачный запрос: {$method}.");
  }
  curl_close($curl);

  $response_api = json_decode($json, true);
  if (!$response_api || !isset($response_api['response']))
  {
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

  if ($result->num_rows != 0)
  {
    while ($row = $result->fetch_assoc())
    {
      $days_in_year = date('L') ? 366 : 365;

      $now_date = new DateTime();
      $birthday_date = $row['birthday_date'];
      $birthday = new DateTime($birthday_date);

      $diff = $birthday->diff($now_date);
      $diff_days = $diff->days;

      $percent_raw = $diff_days/$days_in_year;
      $percent = round($percent_raw * 100);

      return $percent;
    }
  } else
    {
      return 'Сначала пришли мне дату последнего дня рождения в формате ДД.ММ.ГГГГ';
    }
}

//Проверка на дату: должна быть не раньше, чем год назад + не позже, чем сегодня
function chekDate($bdate)
{
  $days_in_year = date('L') ? 366 : 365;
  $datetime = new DateTime();
  $d = new DateTime($bdate);
  $diff = $d->diff($datetime);

  $date_unix = strtotime(date('Y-m-d'));
  $bdate_unix = strtotime($bdate);

  $diff_days = $diff->days;
  if (($diff_days < $days_in_year) && ($bdate_unix < $date_unix))
  {
    return 1;
  }
    return 0;
}

//Заполнение базы данных
function insertOrUpdateBirthday($mysqli, $user_id, $message)
{
  $select = "SELECT birthday_date FROM users_bday WHERE users_id = '{$user_id}';";
  $result = $mysqli->query($select);

  //Если есть поле с таким $user_id, перезаписываем дату, иначе создаём поле
  if($result->num_rows != 0)
  {
  $update = "UPDATE users_bday SET birthday_date='{$message}' WHERE users_id = {$user_id};";
  $mysqli->query($update);
  } else
    {
    $insert = "INSERT INTO users_bday (users_id, birthday_date) VALUES ('{$user_id}', '{$message}');";
    $mysqli->query($insert);
    }
}

//Проверка введённой пользователем даты и приведение её к необходимому для БД формату
function conversionDate($message)
{
  if (strlen($message) != 10)
  {
    return 0;
  }

  $day = mb_substr($message, 0, 2, 'utf-8');
  $month = mb_substr($message, 3, 2, 'utf-8');
  $year = mb_substr($message, 6, 4, 'utf-8');
  $checkdate = $year . $month . $day;

  if(!ctype_digit($checkdate))
  {
    return 0;
  }

  if (($day == 29) && ($month == 02))
  {
    $day = 28;
  }

  $date = $year . '-' . $month . '-' . $day;

  $format = 'Y-m-d';
  $datetime = DateTime::createFromFormat($format, $date);

  if ($datetime->format($format) != $date)
  {
    return 0;
  }

  return $date;
}

//Проверка разрешения на рассылку 
function checkPermission($mysqli, $user_id)
{
  $select = "SELECT permission FROM users_bday WHERE users_id = '{$user_id}';";
  $result = $mysqli->query($select);

  while ($row = $result->fetch_assoc())
  {
    if($row['permission'] == 0)
    {
      return 0;
    }

  return 1;
  }
}

//Получение разрешения на рассылку
function upPermission($mysqli, $user_id)
{
  $select = "SELECT permission FROM users_bday WHERE users_id = '{$user_id}';";
  $result = $mysqli->query($select);

  while ($row = $result->fetch_assoc())
  {
    if($row['permission'] == 0)
    {
      $update = "UPDATE users_bday SET permission='1' WHERE users_id = {$user_id};";
      $mysqli->query($update);
    }
  }
}

//Отказ от рассылки
function downPermission($mysqli, $user_id)
{
  $select = "SELECT permission FROM users_bday WHERE users_id = '{$user_id}';";
  $result = $mysqli->query($select);

  while ($row = $result->fetch_assoc())
  {
    if($row['permission'] == 1)
    {
      $update = "UPDATE users_bday SET permission='0' WHERE users_id = {$user_id};";
      $mysqli->query($update);
    }
  }
}
