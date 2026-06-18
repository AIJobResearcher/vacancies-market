## Пункт 2: Привязка репозиториев к Eloquent моделям

Реализовано: полная привязка доменных репозиториев к Laravel Eloquent ORM с автоматическим маппингом между доменными объектами и модельями.

### Что было добавлено

#### 1. Миграции базы данных
- `database/migrations/2026_06_18_000001_create_employers_table.php` — таблица для Employer
- `database/migrations/2026_06_18_000002_create_vacancies_table.php` — таблица для Vacancy
- `database/migrations/2026_06_18_000003_create_interviewers_table.php` — таблица для Interviewer
- `database/migrations/2026_06_18_000004_create_portals_table.php` — таблица для Portal

#### 2. Eloquent модели
- `app/Models/Employer.php` — модель с отношениями к Vacancy и Interviewer
- `app/Models/Vacancy.php` — модель с отношением к Employer
- `app/Models/Interviewer.php` — модель с отношением к Employer
- `app/Models/Portal.php` — модель для конфигурирования порталов

#### 3. Реализации репозиториев
- `app/Infrastructure/Persistence/EmployerEloquentRepository.php`
- `app/Infrastructure/Persistence/VacancyEloquentRepository.php`
- `app/Infrastructure/Persistence/InterviewerEloquentRepository.php`
- `app/Infrastructure/Persistence/PortalEloquentRepository.php`

Каждый репозиторий:
- Реализует соответствующий интерфейс из `app/Domain/Repositories/`
- Осуществляет двустороннее преобразование между доменными объектами и Eloquent моделями
- Использует методы `save()`, `findById()`, `findByEmployerId()` (где применимо), `all()` и `remove()`

#### 4. Регистрация в IoC контейнере
- `app/Providers/AppServiceProvider.php` — все интерфейсы репозиториев зарегистрированы как синглтоны
- Это позволяет инжектировать интерфейсы везде в приложении

### Маппинг данных

Репозитории выполняют автоматическое преобразование:
- **Employer**: контакты (email, phone) распределяются между `contacts` VO и полями модели
- **Vacancy**: Salary VO разбирается на min/max/currency, VacancyStatus enum конвертируется в строку
- **Interviewer**: аналогично Employer с контактами
- **Portal**: parsingConfig (JSON) сохраняется как есть

### Как использовать

#### Инжекция репозитория через конструктор
```php
use App\Domain\Repositories\EmployerRepositoryInterface;

class EmployerService
{
    public function __construct(
        private EmployerRepositoryInterface $repository
    ) {}
    
    public function saveEmployer(Employer $employer): void
    {
        $this->repository->save($employer);
    }
}
```

#### Получение из контейнера
```php
$repository = app(VacancyRepositoryInterface::class);
$vacancy = $repository->findById('v1');
```

### Тестирование

#### Unit-тесты (работают без БД)
```bash
vendor/bin/phpunit --testsuite Unit
```

Результат: **7 тестов пройдено, 15 assertions**
- Проверка инвариантов вакансии (title, employer required)
- Версионирование при изменениях
- Поведение интервьюера

#### Feature-тесты (требуют SQLite расширение PHP)
Для запуска интеграционных тестов репозиториев нужно установить `php-sqlite3` расширение:

```bash
# На Linux
sudo apt-get install php-sqlite3
# или
sudo apt-get install php8.5-sqlite3
```

Затем можно запустить все тесты:
```bash
vendor/bin/phpunit
```

### Окружение

Текущая конфигурация `.env` переключена на SQLite для локальной разработки. Для production используется PostgreSQL (смотри `config/database.php`).

### Следующие шаги

1. Написать Feature-тесты для репозиториев (требуют SQLite расширение).
2. Добавить миграции для создания индексов и ограничений (уникальность имён порталов и т.п.).
3. Добавить обработку ошибок при дублировании записей.
4. Реализовать фабрики (Factories) для быстрого создания тестовых данных.
5. Настроить seed для заполнения начальных данных (порталов и т.п.).

### Проверка ошибок

Все файлы проверены на синтаксис — ошибок не найдено.
