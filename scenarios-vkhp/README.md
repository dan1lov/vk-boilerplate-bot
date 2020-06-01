# scenarios-vkhp

Стандартный бот, созданный с использованием библиотеки [dan1lov/php-vkhp](https://github.com/dan1lov/php-vkhp). Имеет одну отличительную особенность от `simple-vkhp`: наличие системы `сценариев`, позволяющая избавиться от команд, в которых нужно указывать большое количество аргументов.

### Стуктура файлов:
* `callback.php` — главный файл, к которому обращается ВКонтакте при получении любого нового события в вашем сообществе
* `libs/`
    * `VKHP_onefile.php` — файл библиотеки `VKHP`
* `files/`
    * `config/`
        * `main.php` — файл, возвращающий массив параметров для работы бота
    * `data/` — папка, в которой хранятся данные пользователей
    * `temp/` — папка, для различных временных файлов
        * `scenarios/` — папка, для хранения временных файлов системы `сценариев`
    * `functions.php` — файл, содержащий все функции данного бота, необходимые для его работы
    * `commands.php` — файл, возвращающий массив всех доступных команд
    * `setup.php` — файл, в котором подключаются все необходимые зависимости и другие важные для работы бота вещи
---

### Система сценариев
Данная система позволяет забыть о командах, в которых нужно указывать параметры.

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