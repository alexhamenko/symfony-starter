# <Назва проєкту> - контекст проєкту

## Про проєкт
_Заповнити: що це за застосунок, навіщо він і що в ньому вивчаємо._

## Стек
- Symfony 8.1, PHP 8.5+, FrankenPHP (шаблон dunglas/symfony-docker), PostgreSQL
- Doctrine ORM + Migrations, Foundry
- Twig; стилі: AssetMapper + symfonycasts/tailwind-bundle (standalone Tailwind 4, без Node) + daisyUI 5
  (standalone `.mjs` у `tailwind/plugins/`, версії зафіксовані); у dev CSS перезбирає `make css`
- Якість: PHPStan level max (без baseline), PHP-CS-Fixer (@Symfony, risky), Rector,
  PHPUnit (suites: unit/integration/functional, DAMA), lint:twig, lint:yaml, lint:container
- CI: GitHub Actions (Tests + super-linter + commitlint), Dependabot
- Команди через Makefile: build, up, down, logs, sh, composer, console, cc, css, db-reset, test, qa, fix;
  Composer і консоль - лише `make composer c='...'` / `make console c='...'`
- Dev-контейнер працює від uid/gid хоста (UID-мапінг у стадії `frankenphp_dev`), тож файли від рецептів
  і makers належать користувачу хоста; образ перезбирати через `make build` (передає UID/GID),
  root у контейнері - лише явно: `docker compose exec -u root php bash`

## Режим FrankenPHP (рішення)
- Caddyfile налаштований на worker-режим, але в dev стоїть `FRANKENPHP_LOOP_MAX: 1`
  (`compose.override.yaml`): worker перезапускається після кожного запиту, тобто семантика
  shared-nothing, як у класичному PHP. Прод-конфіг не змінено.
- Навіщо: на етапі вивчення Symfony не змішувати його з нюансами довгоживучого процесу.
- Код однаково пишемо ідіоматично (stateless-сервіси, `RequestStack` замість суперглобалів,
  без static-стану й `exit`), щоб перехід був дешевим.
- Коли вмикати worker: разом із Messenger (`messenger:consume` - теж довгоживучий процес).
  Кроки: прибрати `FRANKENPHP_LOOP_MAX` з override, розібрати `ResetInterface`/`kernel.reset`,
  додати anti-leak функціональні тести (`$client->disableReboot()`, два запити від різних
  користувачів), перевірити сервіси на стан у властивостях і сутності в полях.

## Архітектура
_Заповнити: структура `src/`, модулі чи шари, ключові доменні правила._

## Режим ментора
Режим ментора увімкнено (правила - у глобальному `~/.claude/CLAUDE.md`).

## Правила роботи
- Кожен крок - окремий невеликий коміт; після змін запускати `make qa` і `make test`
- Повідомлення комітів - Conventional Commits за правилами з CONTRIBUTING.md
- Перед встановленням пакета перевіряти сумісність із Symfony 8.1
- Код: declare(strict_types=1), final-класи (крім Doctrine-сутностей), readonly де можливо,
  конструкторна ін'єкція, атрибути замість YAML для роутів і mapping
