# LinguaSchool: етап 0. Фундамент - детальний план

Sep 28, 2026 · @Alex Hamenko

Після етапу 0 (приблизно 1 тиждень) у вас є робочий Docker-проєкт на Symfony 8.1 з інструментами якості, CI, стилями без Node і Makefile як єдиною точкою входу.

Фокус - бекенд. Стилі та базовий UI-kit генерує AI, Vue відкладено: на його місці лишаються плейсхолдери `data-island`, а сторінки повністю працюють без JavaScript.

Етап 0 завершено. Наступний: [етап 1](stage-1.md).

## Етап 0. Фундамент (≈1 тиждень)

### 0.1 Середовище (≈1 день)

Базою беремо шаблон [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker): FrankenPHP, Caddy з HTTPS на localhost, вбудований Mercure і готовий GitHub Actions workflow.

1. Встановіть Docker Desktop / Docker Engine з Compose v2, Git, PhpStorm з плагіном Symfony Support.
2. Створіть репозиторій через «Use this template» на сторінці шаблону і клонуйте його.
3. Зберіть і запустіть із потрібною версією: `docker compose build --pull --no-cache`, потім `SYMFONY_VERSION=8.1.* docker compose up --wait`. Перевірте актуальний синтаксис у `docs/symfony-docker/options.md`.
4. Відкрийте `https://localhost`, прийміть self-signed сертифікат, побачте welcome-сторінку Symfony.
5. Переконайтеся, що в `composer.json` є `"extra": {"symfony": {"require": "8.1.*"}}` і `"php": ">=8.5"`.
6. Перший коміт одразу після генерації - щоб бачити, що змінюють Flex-рецепти далі.

**Сервіси Docker після етапу 0**

| Сервіс | Звідки | Навіщо |
| --- | --- | --- |
| `php` (FrankenPHP) | Шаблон | Застосунок, HTTP, Mercure hub |
| `database` (PostgreSQL) | Рецепт `symfony/orm-pack` | Основна БД |
| `mailer` (Mailpit) | Рецепт `symfony/mailer` | Перегляд листів у dev |
| `redis` (Valkey або Redis) | Додаєте вручну в `compose.yaml` | Кеш, сесії, lock, rate limit (потрібні з етапу 2-3) |

Порада: окремо від `compose.override.yaml` (dev) тримайте `compose.prod.yaml` з шаблону без змін - він знадобиться на етапі деплою.

### 0.2 Пакети Composer (≈0,5 дня)

Встановлюйте по одному пакету з окремим комітом і читайте, що додав Flex-рецепт у `config/`, `.env` і `compose.yaml`. Команди виконуються всередині контейнера: `make composer c='require …'` (dev-контейнер працює від uid хоста, тож файли від рецептів належать вам). Якщо пакет ще не підтримує 8.1 - Composer одразу скаже, і це теж корисний досвід.

| Пакет | Тип | Навіщо на етапах 0-1 |
| --- | --- | --- |
| `symfony/orm-pack` | prod | Doctrine ORM, DBAL, Migrations |
| `symfony/twig-bundle` | prod | Шаблони |
| `twig/extra-bundle`, `twig/intl-extra`, `twig/string-extra`, `twig/html-extra` | prod | Автореєстрація Twig-розширень (сам бандл фільтрів не дає); `format_currency`/`format_datetime`, `u.truncate`/`slug`, `html_classes()`. Markdown, cssinliner, inky, cache - коли знадобляться |
| `symfony/ux-twig-component` | prod | Секції головної та UI-kit як компоненти |
| `symfony/asset-mapper` | prod | Статика без Node і бандлера |
| `symfonycasts/tailwind-bundle` | prod | Tailwind через standalone-бінарник |
| `symfony/translation`, `symfony/intl` | prod | Мультимовність |
| `symfony/validator`, `symfony/form` | prod | Валідація, форми (заявки - етап 3) |
| `symfony/uid`, `symfony/clock` | prod | UUID v7, тестований час |
| `symfony/mailer` | prod | Підтягне Mailpit у compose |
| `symfony/security-bundle` | prod | Поки лише для захисту `/admin` |
| `easycorp/easyadmin-bundle` | prod | Адмінка контенту |
| `symfony/maker-bundle` | dev | Генерація сутностей, контролерів |
| `symfony/debug-pack` | dev | Профайлер і toolbar (`profiler-pack` уже містить `web-profiler-bundle`), `dump()`/`dd()`, stopwatch. Flex розпаковує pack: `monolog-bundle` потрапляє в `require` (логування потрібне й у prod), решта - в `require-dev` |
| `symfony/test-pack` | dev | PHPUnit, BrowserKit, DomCrawler |
| `zenstruck/foundry` | dev | Фабрики й фікстури |
| `dama/doctrine-test-bundle` | dev | Відкат транзакцій між тестами |
| `phpstan/phpstan`, `phpstan/phpstan-symfony`, `phpstan/phpstan-doctrine`, `phpstan/extension-installer` | dev | Статичний аналіз; installer автоматично підключає розширення, без `includes` у `phpstan.dist.neon` |
| `friendsofphp/php-cs-fixer` | dev | Code style |
| `rector/rector` | dev | Автоматичні рефакторинги й апгрейди |
| `deptrac/deptrac` | dev | Контроль меж між модулями |

### 0.3 Якість коду (≈1 день)

Налаштовуємо все на порожньому проєкті, поки немає технічного боргу: потім підняти рівень PHPStan набагато важче.

1. **PHPStan** - `phpstan.dist.neon`: `level: max`, `paths: [src, tests]`, розширення symfony і doctrine; вкажіть `symfony.containerXmlPath` на `var/cache/dev/App_KernelDevDebugContainer.xml` і `doctrine.objectManagerLoader` на `tests/object-manager.php`. Baseline не створюйте.
2. **PHP-CS-Fixer** - `.php-cs-fixer.dist.php` з наборами `@Symfony`, `@Symfony:risky`, `@PHP8x5Migration`, `@PHP8x5Migration:risky`; `declare_strict_types`; `final_internal_class` (усі класи final, крім сутностей Doctrine через виключення атрибутів `ORM\Entity`, `ORM\Embeddable`, `ORM\MappedSuperclass`; опції - `php-cs-fixer describe final_internal_class`). `config/reference.php` виключити з Finder, кеш `.php-cs-fixer.cache` - у `.gitignore`.
3. **Rector** - `rector.php` з `->withPhpSets()`, `->withAttributesSets()`, `->withComposerBased(symfony: true, doctrine: true, phpunit: true, twig: true)` (набори під встановлені версії пакетів, оновлюються самі після апгрейду), `->withSymfonyContainerXml()` на той самий `var/cache/dev/App_KernelDevDebugContainer.xml` і `->withPreparedSets(deadCode: true, codeQuality: true, typeDeclarations: true)`; `config/reference.php` - у `->withSkip()`. У CI запускаємо лише `--dry-run`.
4. **Deptrac** - `deptrac.yaml`: шар на кожен модуль (`Shared`, `Content`, …) через collector `directory`. Правило: будь-який модуль може залежати від `Shared`; `Shared` - ні від кого. Нові модулі додаєте в конфіг разом із папкою. На кроці 0.3 модулів ще немає - конфіг мінімальний, реальні шари з'являються в 1.1. Dashboard EasyAdmin - окремий шар `Admin` (залежить від модулів), не `Shared`. Публічний API модуля (фасади, DTO, події) - окремий шар через collector `bool` (`must` модуль, `must_not` його публічна частина); інші модулі залежать лише від нього. Шари Domain/Application/Infrastructure/UI складних модулів - окремий конфіг `deptrac.layers.yaml`, другий виклик у `make qa`.
5. **PHPUnit** - `phpunit.dist.xml` з трьома suites: `unit` (`tests/Unit`), `integration` (`tests/Integration`, KernelTestCase + БД), `functional` (`tests/Functional`, WebTestCase). Увімкніть розширення DAMA для відкату транзакцій.
6. **Тестова БД** - `.env.test` з окремою базою; команди `doctrine:database:create --env=test` і `doctrine:migrations:migrate --env=test` винесіть у Makefile.

Порада з досвіду WP: у PhpStorm підключіть PHPStan і CS-Fixer як inspections - помилки видно одразу в редакторі, а не лише в CI.

### 0.4 Стилі без Node: AssetMapper + Tailwind + daisyUI (≈0,5 дня)

Фронтенд-збірки на старті немає: Vue відкладено, стилі й UI-kit генерує AI. Потрібен лише мінімум, щоб Twig-шаблони мали CSS.

1. `composer require symfony/asset-mapper symfonycasts/tailwind-bundle`, далі `make console c='tailwind:init'` (команда лише інтерактивна). Вона фіксує версію бінарника в `config/packages/symfonycasts_tailwind.yaml` (`binary_version: v4.3.3`) і додає `@import "tailwindcss"` в `app.css`. Бінарник завантажується у `var/tailwind/`, Node не потрібен; `tailwind.config.js` для v4 не створюється - конфігурація живе в CSS.
2. `assets/styles/app.css`: `@import "tailwindcss" source(none)` + `@source "../../templates"` (класи шукаються лише в шаблонах). daisyUI 5.7.47 - standalone-файли `daisyui.mjs` і `daisyui-theme.mjs` у `tailwind/plugins/`, підключені через `@plugin`: поза `assets/`, щоб AssetMapper їх не публікував; закомічені з фіксованою версією (оновлення - завантажити файли нового релізу з GitHub і змінити версію в коментарі `app.css`). Тема - вбудована `light` з `--color-primary: #4fc87a` і темним `--color-primary-content` (білий текст на `#4fc87a` не проходить контраст WCAG).
3. У `base.html.twig`: `<link rel="stylesheet" href="{{ asset('styles/app.css') }}">` (бандл підміняє вміст скомпільованим CSS), `{{ importmap('app') }}`, `<meta name="viewport">` (без нього mobile-first не працює на телефонах) і `<html lang="{{ app.request.locale }}">`.
4. Dev: `make css` (`tailwind:build --watch`) окремим процесом, інакше зміни класів у шаблонах не потраплять у CSS. Prod: у `Dockerfile` (стадія `frankenphp_prod_builder`) `tailwind:build --minify` перед `asset-map:compile`. CI: те саме як smoke-перевірка статики (0.5).
5. `assets/app.js` лишається майже порожнім: без `import './styles/app.css'` (стилі підключені через `<link>`, без залежності від JS); JavaScript на етапах 0-1 не пишемо.

Коли дійде до Vue: `pentatrion/vite-bundle` + `vite-plugin-symfony`, Node-сервіс у compose, заміна `importmap()` на `vite_entry_*_tags()` у `base.html.twig`. Оцінка - близько пів дня.

### 0.5 Makefile, CI, готовність (≈1 день)

**Makefile** - одна точка входу для вас і для Claude Code:

| Ціль | Що робить |
| --- | --- |
| `make build` | Збірка образів з `UID`/`GID` хоста (UID-мапінг у стадії `frankenphp_dev`) |
| `make up` / `make down` / `make logs` | `docker compose up --wait` / `down` / логи |
| `make sh` | Shell у контейнері `php` |
| `make composer c='...'` / `make console c='...'` / `make cc` | Composer, `bin/console`, `cache:clear` |
| `make css` | `tailwind:build --watch` |
| `make db-reset` | drop → create → migrate → fixtures (dev) |
| `make test` | Тестова БД (create + migrate) + `phpunit`, suite через `c='--testsuite unit'` |
| `make qa` | `cache:warmup`, `phpstan`, `php-cs-fixer --dry-run`, `rector --dry-run`, `deptrac`, `lint:container`, `lint:twig`, `lint:yaml`, `doctrine:schema:validate` |
| `make fix` | `rector process`, потім `php-cs-fixer fix` (Rector не дотримується code style, CS-Fixer форматує його результат) |

**CI (GitHub Actions)** - розширте workflow із шаблону до одного job: збірка образу, `make qa`, `make test` з PostgreSQL, `tailwind:build --minify` + `asset-map:compile` як smoke-перевірка статики. Увімкніть branch protection: merge у `main` лише із зеленим CI. Працюйте короткими гілками й PR навіть наодинці - так видно історію рішень.

**Готовність етапу 0**

- [x] `make up` піднімає проєкт з нуля на чистій машині
- [x] `https://localhost` відкривається, профайлер працює
- [x] `make qa` і `make test` зелені (є хоча б один smoke-тест)
- [x] Tailwind + daisyUI збираються, `base.html.twig` підхоплює стилі
- [x] CI зелений на PR, `main` захищений
- [x] README: як запустити, які команди є
- [x] `CLAUDE.md` у корені з контекстом проєкту

**Наступний крок:** [етап 1. Каркас публічного сайту](stage-1.md).
