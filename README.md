# symfony-starter

[![CI](https://github.com/alexhamenko/symfony-starter/actions/workflows/ci.yaml/badge.svg)](https://github.com/alexhamenko/symfony-starter/actions/workflows/ci.yaml)

Шаблон для навчальних проєктів на Symfony 8.1: Docker-оточення, QA-інструменти й CI вже налаштовані, специфічного для застосунку коду немає.

Стек: PHP 8.5, FrankenPHP, PostgreSQL, Doctrine ORM + Migrations, Foundry, Twig, AssetMapper + Tailwind 4 + daisyUI (без Node). Деталі - у [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md).

## Новий проєкт із шаблону

1. На GitHub: **Use this template** (або `gh repo create <name> --template alexhamenko/symfony-starter --clone`).
2. Перейменувати проєкт:
   - `composer.json`: `name` і `description`, потім `make composer c='update --lock'`;
   - `README.md`: заголовок, опис і посилання CI-бейджа;
   - `PROJECT_CONTEXT.md`: заповнити розділи "Про проєкт" і "Архітектура".
3. Перегенерувати `APP_SECRET` у `.env.dev`:

   ```bash
   sed -i "s/^APP_SECRET=.*/APP_SECRET=$(openssl rand -hex 16)/" .env.dev
   ```

4. GitHub: Settings -> Rules -> Rulesets, ruleset для `main`: заборона прямого push, обов'язковий PR, обов'язкові перевірки `Tests` і `Lint`.
5. `make build && make up && make test` - усе має бути зеленим.

Makefile додає до назви образу префікс з імені директорії (`IMAGES_PREFIX`, наприклад `my-blog-app-php-dev`), тож проєкти з шаблону не перезаписують образи одне одного. Тому Docker запускайте через `make`: прямий `docker compose up` шукатиме образ без префікса.

## Запуск

Потрібні Docker з Compose v2.10+ і `make`.

```bash
make build   # build images (passes host UID/GID to the dev image)
make up      # start containers and wait until they are healthy
make css     # in a separate terminal: rebuild Tailwind CSS on template changes
```

Відкрийте `https://localhost` і прийміть локальний TLS-сертифікат (або додайте кореневий сертифікат Caddy в довірені, див. [docs/symfony-docker/tls.md](docs/symfony-docker/tls.md)). Сторінка `https://localhost/_dev/styleguide` (лише dev) показує тему.

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
| `make qa` | PHPStan (max), PHP-CS-Fixer, Rector, lint-перевірки, `doctrine:schema:validate` |
| `make fix` | Rector, потім PHP-CS-Fixer |

## Як працювати

- Зміни - через гілку і PR: `main` захищений, злиття лише із зеленим CI (`Tests`, `Lint`).
- Перед комітом: `make qa` і `make test`. Повідомлення комітів - Conventional Commits, шпаргалка в [CONTRIBUTING.md](CONTRIBUTING.md).

## Основа

Шаблон побудовано на [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker). Його документація лишилась у `docs/symfony-docker/`: [Xdebug](docs/symfony-docker/xdebug.md), [TLS](docs/symfony-docker/tls.md), [деплой](docs/symfony-docker/production.md), [troubleshooting](docs/symfony-docker/troubleshooting.md), [оновлення шаблону](docs/symfony-docker/updating.md).
