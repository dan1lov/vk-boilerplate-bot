# vk-boilerplate-bot

В данном репозитории собраны различные примеры реализаций ботов для ВКонтакте.

Во всех примерах, используется версия PHP `7.2`.

---
### very-simple
Очень простой бот, состоящий всего из четырех файлов. Подойдет, чтобы просто понять принцип работы и способы взаимодействия с ботом.

**Не рекомендуется** использовать данный пример бота как реальный для вашего проекта.

### simple-vkhp
Стандартный бот, созданный с использованием библиотеки [dan1lov/php-vkhp](https://github.com/dan1lov/php-vkhp). Имеет более продвинутую структуру файлов, нежели реализация `very-simple`.

Уже может использоваться в качестве основы для любого реального проекта.

### scenarios-vkhp
Практически тоже самое, что и `simple-vkhp`, но имеет одну отличительную черту: наличие системы `сценариев`.

Система `сценариев` позволяет создавать такие команды, в которых пользователь может, к примеру, задавать себе свой собственный ник. Более подробнее о реализации можете посмотреть в файле [README.md](scenarios-vkhp/README.md) в папке данного примера.