# LinguaSchool: етап 10. Продакшн-якість - детальний план

Oct 9, 2026 · @Alex Hamenko

Після етапу 10 (постійно + приблизно 2 тижні окремої роботи) застосунок задеплоєний на VPS, має моніторинг помилок, логи, бекапи з перевіреним відновленням, профільовані вузькі місця і план апгрейду Symfony.

Попередній: [етап 9](stage-9.md). Частину пунктів (тести, безпека, оновлення залежностей) варто робити постійно з етапу 1, а не відкладати сюди.

## Етап 10. Продакшн-якість (постійно + ≈2 тижні)

### 10.1 Тести і покриття (постійно, ≈2 дні аудиту)

1. Звіт покриття (`pcov` у dev-образі або Xdebug): знайдіть непокриту доменну логіку і критичні шляхи (білінг, бронювання).
2. Опційно - mutation testing (`infection/infection`) для `Domain` складних модулів: показує тести, які нічого не перевіряють.
3. Smoke-тести prod-збірки в CI: образ `frankenphp_prod` стартує, `/health` відповідає 200.
4. Anti-leak тести worker-режиму (етап 3) - для всіх сценаріїв з користувачем і локаллю.

**Що вивчаєте:** якість тестів, а не лише їх кількість.

### 10.2 Спостережуваність (≈2 дні)

1. Monolog у prod: JSON-формат у stdout, канали на модуль, `fingers_crossed` для помилок, processors з request id і user id.
2. `sentry/sentry-symfony` (перевірте сумісність): помилки, performance tracing, release із git SHA.
3. `/health`: перевірка БД, Redis, Mercure; окремо - стан черг Messenger (`messenger:stats`) і кількість `failed`.
4. Алерти: невдалі вебхуки, переповнена `failed`-черга, помилки списання підписок.

**Що вивчаєте:** Monolog (handlers, channels, processors), Sentry, health checks.

### 10.3 Продуктивність (≈2-3 дні)

1. Профілювання: Blackfire (якщо доступний) або Xdebug profiler на найважчих сторінках і ендпоінтах.
2. PHP: OPcache з preloading (`config/preload.php`), `realpath_cache`; worker-режим FrankenPHP уже ввімкнений.
3. Doctrine: query cache і metadata cache у Redis, `EXPLAIN` на повільних запитах з логів, індекси міграціями.
4. HTTP: стиснення в Caddy, 103 Early Hints для CSS, версіоновані асети (`asset-map:compile`), рішення про reverse proxy кеш у prod (Symfony HttpCache vs Caddy/CDN).
5. Lighthouse на мобільному для ключових сторінок: Performance і SEO >= 90.

**Що вивчаєте:** профілювання, кеші Doctrine, preloading, HTTP-оптимізації.

### 10.4 Безпека (≈2 дні)

1. `composer audit` і `symfony check:security` у CI; Dependabot (або Renovate) для Composer, Docker і GitHub Actions.
2. Заголовки: CSP, HSTS, `X-Content-Type-Options`, `Referrer-Policy` (`nelmio/security-bundle` або Caddy - перевірте сумісність бандла).
3. Секрети prod - Symfony secrets vault (`secrets:set --env=prod`), ключ розшифрування лише на сервері.
4. Ревізія: rate limits на логіні, формах і API; права на завантажені файли; доступ до `/admin` і Swagger UI в prod.

**Що вивчаєте:** secrets vault, security headers, аудит залежностей.

### 10.5 Деплой (≈3 дні)

1. Базово - `compose.prod.yaml` з шаблону і `docs/symfony-docker/production.md` (VPS, наприклад DigitalOcean, DNS, автоматичний HTTPS у Caddy).
2. Сервіси prod: `php`, `worker` (`messenger:consume async scheduler_default` з `--time-limit` і перезапуском), `database`, `redis`.
3. Деплой із CI: збірка і push образу в registry (GHCR), на сервері `docker compose pull && up -d`, міграції окремим кроком перед перемиканням. Подумайте, як зробити міграції сумісними зі старою версією коду (expand/contract).
4. `messenger:stop-workers` після деплою, щоб воркери підхопили новий код.
5. Staging (опційно): те саме оточення з тестовими ключами Stripe.

**Що вивчаєте:** prod-збірка Symfony, контейнерний деплой, безпечні міграції.

### 10.6 Бекапи (≈1 день)

1. `pg_dump` за розкладом у S3-сумісне сховище з ротацією; бекап завантажених файлів (Flysystem-сховище).
2. Обов'язкова перевірка відновлення: розгорнути бекап на чистій машині і пройти smoke-тест. Бекап, який не відновлювали, не рахується.
3. Задокументуйте процедуру відновлення в `docs/`.

### 10.7 Апгрейд Symfony (≈1-2 дні)

1. Підтримка 8.1 закінчується в січні 2027 - плануйте перехід на 8.2.
2. Перед апгрейдом: нуль deprecations у логах і тестах (`SYMFONY_DEPRECATIONS_HELPER`), оновлений Rector через `withComposerBased`.
3. Апгрейд окремим PR: `extra.symfony.require`, `composer update "symfony/*"`, перегляд нових рецептів (`composer recipes:update`), `make qa` і `make test`.

**Що вивчаєте:** політика релізів Symfony, deprecations, оновлення рецептів.

**Готовність етапу 10**

- [ ] Застосунок працює на VPS з HTTPS, деплой - з CI
- [ ] Воркери Messenger і Scheduler працюють і перезапускаються після деплою
- [ ] Помилки потрапляють у Sentry, логи структуровані, `/health` моніториться
- [ ] Бекапи щоденні, відновлення перевірене і задокументоване
- [ ] Security headers, аудит залежностей у CI, секрети у vault
- [ ] Lighthouse >= 90 (Performance, SEO) на ключових сторінках
- [ ] Немає deprecations, план апгрейду на 8.2 готовий або виконаний
- [ ] `make qa` і `make test` зелені

**Наступний крок:** [опційний етап 11. Vue-фронтенд](stage-11.md).
