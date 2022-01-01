# lite-cms

Версия laravel 8.x

## Развертывание
  Для развертывания, достаточно запустить в папке с проектом команду `composer install`
  Затем сгенерировать ключ через `php artisan key:generate` и применить миграции `php artisan:migrate`

## Методы api
Все запросы к api идут через префикс `admin`
Методы `/api/admin`:

### menu
Работа с меню
| url                | method   | params                                                                              |
| ------------------ | -------- | ----------------------------------------------------------------------------------- |
| `/`                | **get**  | -                                                                                   |
| `/create`          | **post** | `name`, `alias`, `position_id`, `url`                                               |
| `/add`             | **post** | `name`, `alias`, `id_parent`, `url`                                                 |
| `/update`          | **post** | `name`, `alias`, `id_parent`, `url`, `id`, `position_id`(только для корневых узлов) |
| `/delete`          | **post** | `id`                                                                                |
| `/change-position` | **post** | `a`, `b`                                                                            |


## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
