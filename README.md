#### Небольшое описание функционала бота:

После того, как пользователь напишет любое слово в личные сообщения группы, бот предложит написать слово **"Инфо"**, чтобы пользователя ознакомился с функционалом.

Бот сразу запросит дату рождения пользователя в определённом формате.

Далее бот проверит, есть ли в базе данных информация о пользователе: если да – просто обновит её, в противном случае создаст поле с необходимы ему данными.

Если дата рождения пользователя уже есть в базе данных, он сможет запросить у бота **"Процент"**, после чего получит информацию о том, сколько % прошло с момента дня его рождения, в противном случае получит запрос о получении даты рождения.

Также пользователю доступна рассылка сообщений, которую он может включить (а после выключить) ключами **"Прогресс"** и **"Хватит"** соответственно.

Рассылка реализована с помощью Cron, он напоминает пользователю о том, что с его дня рождения прошло всё больше времени, каждый раз, когда повышается процент.

#### Полное описание цепочки диалога:

Любое сообщение, написанное пользователем &rarr;
{Имя}, если не знаешь, что сказать, пиши "Инфо".

**"Инфо"** &rarr;
Привет, {Имя}, меня зовут Хандра-бот. Я могу посчитать прогресс года с Дня рождения.
Если ещё не успел, напиши день своего рождения в формате ДД.ММ.ГГГГ.
После этого можешь написать «Процент».
Также ты можешь попросить меня уведомлять тебя о повышении процента, написав «Прогресс».
Если захочешь отключить эту функцию, можешь сказать мне «Хватит».

**"Процент"**&rarr;
Если данные есть &rarr; Ого, с твоего дня рождения прошло уже {N}%!
Если данных ещё нет &rarr; Сначала пришли мне день своего рождения в формате ДД.ММ.ГГГГ

**"Прогресс"**&rarr;
Если данные есть &rarr; Окей, буду напоминать тебе о каждом повышении процента, мне не сложно! Для отключения функции напиши "Хватит"
Если данных ещё нет &rarr; Сначала пришли мне день своего рождения в формате ДД.ММ.ГГГГ

**"Хватит"**&rarr;
Если данные есть &rarr; Эх, ладно, хоть отдохну:)
Если данных ещё нет &rarr; Что, прости?

Сообщение от **Cron** &rarr;
{Имя}, смотри-ка! Ты стал ещё на процент года ближе к Дню Рождения!
С последнего прошло уже {N}%!
