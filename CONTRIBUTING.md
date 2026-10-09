# Правила роботи з репозиторієм

## Повідомлення комітів

Формат - [Conventional Commits 1.0.0](https://www.conventionalcommits.org/uk/v1.0.0/).

```text
<type>(<scope>)!: <description>

[body]

[footer(s)]
```

| Частина | Обов'язкова | Правило |
| --- | --- | --- |
| `type` | так | один із типів нижче |
| `(scope)` | ні | область зміни; пропускаємо, якщо зміна наскрізна |
| `!` | ні | breaking change (зміна контракту API, несумісна міграція) |
| `description` | так | англійською, імператив (`add`, не `added`/`adds`), з малої літери, без крапки, до ~72 символів |
| body | ні | **чому** і **що саме**, якщо не очевидно з заголовка; відділяється порожнім рядком |
| footer | ні | `BREAKING CHANGE: ...`, `Refs: #123` |

`Co-Authored-By` не додаємо.

### Типи

| Тип | Коли | Приклад |
| --- | --- | --- |
| `feat` | нова функціональність, помітна користувачу або клієнту API | `feat(blog): add archive page` |
| `fix` | виправлення помилки або невірної конфігурації | `fix(routing): return 404 for unknown slug` |
| `refactor` | зміна коду без зміни поведінки | `refactor(blog): extract slug generation to service` |
| `perf` | оптимізація без зміни поведінки | `perf(blog): fetch comments in one query` |
| `test` | лише тести | `test(blog): cover ArticleRepository::findPublishedBySlug` |
| `docs` | лише документація (`*.md`, docblocks) | `docs: add commit conventions` |
| `style` | форматування без зміни логіки (результат `make fix`) | `style: apply php-cs-fixer @Symfony ruleset` |
| `build` | Dockerfile, compose, Makefile, збірка статики | `build(docker): pin PostgreSQL 16` |
| `ci` | GitHub Actions, Dependabot | `ci: run make qa on pull requests` |
| `chore` | рутина: залежності, конфіги інструментів, `.gitignore` | `chore(deps): install symfony/orm-pack` |
| `revert` | відкат коміту | `revert: feat(blog): add archive page` |
| `ui` | **власний тип шаблону**: шаблони, UI-kit і стилі, згенеровані AI | `ui(home): add hero section markup` |

### Scope

- **Модулі / області застосунку:** визначаються в кожному проєкті (наприклад, `blog`, `user`)
- **Інфраструктура:** `deps`, `deps-dev`, `docker`, `ci`, `qa`, `db`, `routing`
- **UI:** `home`, `layout`, `ui-kit`, `theme`

Список не закритий: нова область додається сюди разом із першим комітом, що її використовує.

### Як обрати тип (швидкий алгоритм)

1. Змінилась поведінка для користувача? Нове - `feat`, виправлене - `fix`.
2. Змінився лише код, поведінка та сама? `refactor` (або `perf`, якщо мета - швидкість).
3. Змінились лише тести / документація / форматування? `test` / `docs` / `style`.
4. Шаблони й стилі від AI? `ui`.
5. Інфраструктура: збірка й контейнери - `build`, CI - `ci`, решта - `chore`.
6. Важко обрати один тип - коміт змішує кілька змін, його варто розбити.

### Типові сценарії

**Встановлення пакета через Flex.** Один `composer require` - один коміт.

- Заголовок описує **нашу дію**, а не роботу рецепта: `install <package>`. Жодних "configure", "enable",
  "set up": якщо ми лише виконали `composer require`, ми нічого не налаштовували.
- Назва пакета - та, яку передавали в `composer require` (для pack-а - сам pack, навіть якщо Flex його розпакував).
- Кілька пакетів однією командою - перелічуємо коротко: `install symfony/translation and symfony/intl`.
- Dev-пакет (`--dev`) - тип `chore(deps-dev)`.

```text
chore(deps): install symfony/validator
chore(deps-dev): install symfony/maker-bundle
```

**Тіло - лише коли рецепт зачепив щось поза `config/packages/` і `config/bundles.php`**: `compose*.yaml`,
`Dockerfile`, `.env*`, шаблони, `phpunit.dist.xml`, `src/`, або коли поведінка Flex неочевидна.
Одне-два речення, що саме змінилось:

```text
chore(deps): install symfony/mailer

Recipe adds Mailpit service to compose.override.yaml and MAILER_DSN to .env.
```

```text
chore(deps-dev): install symfony/debug-pack

Flex unpacked the pack: monolog-bundle goes to require,
debug-bundle, stopwatch and web-profiler-bundle to require-dev.
```

`composer.json`, `composer.lock`, `symfony.lock` і `config/reference.php` змінюються в кожному такому коміті - їх не згадуємо.

**Правки після рецепта** - окремим комітом, щоб в історії було видно, що зробив Flex, а що ми:

```text
fix(docker): align PostgreSQL serverVersion with database image
```

**Нова сутність і міграція** - разом, бо міграція без сутності не має сенсу:

```text
feat(blog): add Article entity

Slug is unique and generated from the title on creation.
```

**Налаштування інструменту якості:**

```text
chore(qa): configure PHPStan at level max
```

**Згенерована верстка** - завжди окремо від PHP-коду компонента:

```text
feat(home): add featured articles to homepage
ui(home): add featured articles section markup
```

**Breaking change** (зміна публічного API):

```text
feat(api)!: rename /posts endpoint to /articles

BREAKING CHANGE: clients must switch to /api/articles.
```

### Перед комітом

- `make qa` і `make test` зелені; якщо `make qa` скаржиться на стиль - `make fix`
- `git diff --staged` - у коміті лише те, що описано в заголовку
- жодних секретів: реальні значення - у `.env.local` або `secrets:set`
