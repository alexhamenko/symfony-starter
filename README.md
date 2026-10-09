# LinguaSchool

[![CI](https://github.com/alexhamenko/lingua-school/actions/workflows/ci.yaml/badge.svg)](https://github.com/alexhamenko/lingua-school/actions/workflows/ci.yaml)

Навчальний pet-проєкт на Symfony 8.1: функціональна копія онлайн-школи англійської (структура й функції, без бренду й контенту оригіналу). Фокус - бекенд.

Стек: PHP 8.5, FrankenPHP, PostgreSQL, Doctrine, Twig + Symfony UX, AssetMapper + Tailwind 4 + daisyUI (без Node), EasyAdmin. Деталі - у [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md).

## Запуск

Потрібні Docker з Compose v2.10+ і `make`.

```bash
make build   # build images (passes host UID/GID to the dev image)
make up      # start containers and wait until they are healthy
make css     # in a separate terminal: rebuild Tailwind CSS on template changes
```

Відкрийте `https://localhost` і прийміть локальний TLS-сертифікат (або додайте кореневий сертифікат Caddy в довірені, див. [docs/symfony-docker/tls.md](docs/symfony-docker/tls.md)). Сторінка `https://localhost/_dev/styleguide` (лише dev) показує тему й компоненти.

Зупинка: `make down`.

## Команди

Повний список - `make help`.

| Команда | Що робить |
| --- | --- |
| `make build` / `make up` / `make down` / `make logs` | Docker: збірка, запуск, зупинка, логи |
| `make sh` | Shell у контейнері `php` |
| `make composer c='require ...'` | Composer (лише так, щоб файли від рецептів належали вам) |
| `make console c='debug:router'` / `make cc` | `bin/console`, очищення кешу |
| `make css` | `tailwind:build --watch` |
| `make db-reset` | Dev-БД з нуля: drop, create, migrate, фікстури |
| `make test` | Тестова БД + PHPUnit; один suite: `make test c='--testsuite unit'` |
| `make qa` | PHPStan (max), PHP-CS-Fixer, Rector, Deptrac, lint-перевірки, `doctrine:schema:validate` |
| `make fix` | Rector, потім PHP-CS-Fixer |

## Як працювати

- Зміни - через гілку і PR: `main` захищений, злиття лише із зеленим CI (`Tests`, `Lint`).
- Перед комітом: `make qa` і `make test`. Повідомлення комітів - Conventional Commits, шпаргалка в [CONTRIBUTING.md](CONTRIBUTING.md).
- Плани етапів: `docs/stages/stage-N.md` (від [етапу 0](docs/stages/stage-0.md) до опційного [етапу 11](docs/stages/stage-11.md)); архітектура й roadmap: [docs/architecture-roadmap.md](docs/architecture-roadmap.md).

## Основа

Проєкт створено з шаблону [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker). Його документація лишилась у `docs/symfony-docker/`: [Xdebug](docs/symfony-docker/xdebug.md), [TLS](docs/symfony-docker/tls.md), [деплой](docs/symfony-docker/production.md), [troubleshooting](docs/symfony-docker/troubleshooting.md), [оновлення шаблону](docs/symfony-docker/updating.md).
