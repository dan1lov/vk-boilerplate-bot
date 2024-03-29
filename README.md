# vk-boilerplate-bot

Простой шаблон для быстрой разработки для платформы ботов ВКонтакте.
Используйте весь потенциал инструментов, чтобы добиться лучшего опыта использования для пользователей.

Данный шаблон был разработан лично мной, и улучшался каждый год, подстраиваясь под собственные нужды.
Со временем всё пришло в более-менее абстрактную структуру, которая может стать отправной точкой для бота любой сложности.

В данной реализации используются библиотеки собственной разработки:
* [dan1lov/php-vkhp](https://github.com/dan1lov/php-vkhp): хелпер, для работы с функциями VK API
* [dan1lov/database](https://gist.github.com/dan1lov/8340c2ea8fa48d11f5c00238d2d83d10): упрощенная работа с PDO

Минимальная версия PHP — `7.2`

По всем вопросам писать в Telegram: [@ffwturtle](https://t.me/ffwturtle) 

---
### Стуктура файлов:
* `callback.php` — главный файл, к которому обращается ВКонтакте при получении любого нового события в вашем сообществе
* `libs/`
    * `VKHP_onefile.php` — файл библиотеки `dan1lov/php-vkhp`
    * `Database.php` — файл микро-библиотеки `dan1lov/database`
* `files/`
    * `config/`
        * `constants.php` — константы бота
        * `database.php` — параметры для подключения базы
        * `main.php` — массив основных параметров
        * `settings.php` — массив настроек
    * `temp/` — папка, для различных временных файлов
        * `scenarios/` — папка, для хранения временных файлов системы `сценариев`
    * `commands.php` — доступные команды
    * `functions.php` — функции бота
    * `setup.php` — подлючение необходимых зависимостей, установка связей
    * `database-dump.sql` — дамп базы, для работоспособности данного примера

---
### Система сценариев
Данная система была разработана для улучшения опыта пользователя с ботом. 
Отказ от команд, в которых указываются параметры в одном сообщении.

Пользователь двигается по сторого определенному сценарию, описанному разработчиком.
Отсюда и родилось название для этой системы

Пример команды, в которой используются параметры:
```
/ник myNewNickname
```

Как это выглядит с системой `сценариев`:
```
Польз.: /ник
Бот:    Укажите ваш новый ник
Польз.: myNewNickname
Бот:    Теперь ваш новый ник: myNewNickname
```

---
### Callback-кнопки
Читайте подробнее на странице для разработчиков: [callback-кнопки](https://dev.vk.com/api/bots/development/keyboard#Callback-%D0%BA%D0%BD%D0%BE%D0%BF%D0%BA%D0%B8)
