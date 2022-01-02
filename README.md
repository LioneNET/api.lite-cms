# lite-cms

Версия laravel 8.x

## Развертывание
  Для развертывания, достаточно запустить в папке с проектом команду `composer install`
  Затем сгенерировать ключ через `php artisan key:generate` и применить миграции `php artisan:migrate`

## Методы api
Все запросы к api идут через префикс `admin`
Методы `/api/admin`:

### **menu**
Меню
| url                | method   | params                                                                              |
| ------------------ | -------- | ----------------------------------------------------------------------------------- |
| `/`                | **get**  | -                                                                                   |
| `/create`          | **post** | `name`, `alias`, `position_id`, `url`                                               |
| `/add`             | **post** | `name`, `alias`, `id_parent`, `url`                                                 |
| `/update`          | **post** | `name`, `alias`, `id_parent`, `url`, `id`, `position_id`(только для корневых узлов) |
| `/delete`          | **post** | `id`                                                                                |
| `/change-position` | **post** | `a`, `b`                                                                            |

### **category**
Категории
| url       | method   | params                                      |
| --------- | -------- | ------------------------------------------- |
| `/`       | **get**  | -                                           |
| `/create` | **post** | `name`, `alias`, `image`                    |
| `/add`    | **post** | `name`, `alias`, `id_parent`, `image`       |
| `/update` | **post** | `name`, `alias`, `id_parent`, `image`, `id` |
| `/delete` | **post** | `id`                                        |

### **topic**
Статьи
| url       | method   | params                                                                    |
| --------- | -------- | ------------------------------------------------------------------------- |
| `/`       | **get**  | -                                                                         |
| `/create` | **post** | `title`, `short_text`, `text`, `category_id`, `image`, `section_id`       |
| `/update` | **post** | `id`, `title`, `short_text`, `text`, `category_id`, `image`, `section_id` |
| `/delete` | **post** | `id`                                                                      |

### **section**
Секции сайта
| url       | method   | params                |
| --------- | -------- | --------------------- |
| `/`       | **get**  | -                     |
| `/create` | **post** | `title`, `name`       |
| `/update` | **post** | `id`, `title`, `name` |
| `/delete` | **post** | `id`                  |

### **file**
Файлы
| url           | method   | params                      |
| ------------- | -------- | --------------------------- |
| `/`           | **get**  | -                           |
| `/upload`     | **post** | `path`, `num`, `num_chunks` |
| `/delete`     | **post** | ~                           |
| `/dir-create` | **post** | ~                           |
| `/dir-rename` | **post** | ~                           |

### **banner**
Банеры
| url       | method   | params                                       |
| --------- | -------- | -------------------------------------------- |
| `/`       | **get**  | -                                            |
| `/create` | **post** | `name`, `class`, `inner_text`, `position_id` |
| `/update` | **post** | `name`, `class`, `inner_text`, `position_id` |
| `/delete` | **post** | `id`                                         |

### **position**
Позиции
| url              | method   | params                |
| ---------------- | -------- | --------------------- |
| `/`              | **get**  | -                     |
| `/get-pos-items` | **post** | `title`, `name`       |
| `/change`        | **post** | `id`, `title`, `name` |

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
