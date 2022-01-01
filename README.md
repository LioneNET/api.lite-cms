# lite-cms

Версия laravel 8.x

## Развертывание

Для развертывания, достаточно запустить в папке с проектом команду `composer install`
Затем сгенерировать ключ через `php artisan key:generate` и применить миграции `php artisan:migrate`

## Методы api

Все запросы к api идут через префикс `admin`
Методы `/api/admin`:

### /menu

Параметры: 
`id_parent` - ID родительского узла
`name` - Название меню
`alias` - Алиас
`position_id` - ID позиции корневого меню
`url` - урл меню
`a` - ID меню позицию которого нужно изменить
`b` - ID меню после которого будет итди меню `a`

`/` **get** возвращает дерево меню
`/create` **post**  создает корневое меню. 
`/add` **post** Добавляет к пункт меню в корневое меню. 
`/update` **post** Обновить
`/delete` **post** Удалить
`/change-position` **post** Изменить позицию следования

### \category

Параметры: 
`id_parent` - ID родительского узла
`name` - Название категории
`alias` - Алиас
`image` - url картинки
`moveNodeToID` - переместить

`/` **get** возвращает дерево категорий
`/create` **post**  создает категорию.
`/add` **post** Добавляет подкатегорию.  
`/update` **post** Обновить
`/delete` **post** Удалить

### \topic

Параметры: 
`title` - Заголовок
`short_text` - Интро текст
`text` - Полный текст
`category_id` - ID категории
`image` - url картинки
`section_id` - ID секции, к которой будет присвоина статья
...

`/` **get** возвращает статьи
`/create` **post**  создает статью
`/update` **post** Обновить
`/delete` **post** Удалить

### \section

Параметры: 
`title` - Заголовок
`name` - Название секции

`/` **get** возвращает секции
`/create` **post**  создает секцию
`/update` **post** Обновить
`/delete` **post** Удалить

### \file

Параметры: 
`path` - Путь к директории
`num` - Текущий кусок файла
`num_chunks` - Всего кусков файлов
`file_size` - Размер файла
`file_name` - Имя файла

`/` **get** возвращает статьи
`/upload` **post**  загрузить файл
`/delete` **post** удалить файл директорию
`/dir-create` **post** создание директории
`/dir-rename` **post** переименование директории

### \banner

Параметры: 
`name` - Имя
`class` - Название css класса
`inner_text` - Текст или HTML
`position_id` - ID позиции

`/` **get** возвращает дерево меню
`/create` **post**  создает корневое меню. 
`/update` **post** Обновить
`/delete` **post** Удалить

### \position

Параметры: 
`name` - Имя
`title` - Заголовок
`a` - ID 
`b` - ID 

`/` **get** возвращает все позиции
`/get-pos-items` **post**  возвращает объекты связаные с поцициями
`/change` **post** Меняет позицию местами

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
