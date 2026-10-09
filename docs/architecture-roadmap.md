# LinguaSchool: архітектура та roadmap (Symfony 8.1 + Vue 3)

Sep 28, 2026 · @Alex Hamenko

## Мета і референс

Pet-проєкт для переходу з WordPress на Symfony: функціональна копія онлайн-школи англійської за зразком [englishdom.com](https://www.englishdom.com/ua/). Робоча назва — LinguaSchool; бренд, логотипи, фото й тексти оригіналу не використовуємо, копіюємо лише структуру та функціональність.

Стек: Symfony 8.1 на PHP 8.5+ (реліз травень 2026, підтримка до січня 2027, далі апгрейд на 8.2), PostgreSQL, Redis/Valkey, FrankenPHP, Twig + Tailwind + daisyUI через AssetMapper (без Node); Vue 3 — пізніше. Фокус проєкту — бекенд: стилі й UI-kit генерує AI. [Джерело: symfony.com/releases/8.1](https://symfony.com/releases/8.1)

### Публічні сторінки (Twig SSR, SEO)

| Розділ | URL оригіналу | Примітка |
| --- | --- | --- |
| Головна | `/ua/` | Секції зі скріншоту, форма пробного уроку |
| Для компаній / Для дітей | `/ua/corp/`, `/ua/kids/` | Окремі лендінги |
| Курси | `/ua/course/` | Список + сторінка курсу |
| Викладачі | `/ua/repetitory/anhliiska-mova/` | Фільтр Всі / Носії / Локали, профіль за slug |
| Групові заняття | `/ua/group-lessons/` | Лендінг |
| Ціни | `/ua/prices/?type=subscription\|packages` | Підписки та пакети |
| Блог | `/ua/blog/` | Список, стаття, категорії |
| Тест рівня | `/ua/test-your-english-level/` | Vue-острівець |
| Відгуки, команда, прес-кіт, контакти | `/ua/otzyvy/`, `/ua/school/`, `/ua/school/press/`, `/ua/contacts/` | Контентні сторінки |
| Промокод, сертифікати | `/ua/promo/`, `/ua/gift-certificates/` | Потребують білінгу |
| Партнерка, реферали, стипендія | `/ua/cpa-partners/`, `/ua/referral-program/`, `/ua/scholarship/` | Лендінги + форми |
| Вакансії | `/ua/job/`, `/ua/school/jobs/` | Список + форма |
| Розсилка, застосунок слів | `/ua/weekly-subscription/`, `/ua/englishdom-words-app/` | Лендінги |
| Юридичні | `/ua/info/terms/*` | Оферта, приватність, cookies |

### Кабінет (за логіном): спочатку JSON API, Vue SPA пізніше

Моє навчання (індивідуальні уроки), тренування і словник, self-study курси, розмовні клуби, вебінари/активності, AI-словник, профіль і баланс. Кабінет оригіналу закритий, тому його екрани проєктуємо самостійно.

## Бізнес-правила домену

Ці правила взяті з FAQ і сторінки цін оригіналу. Вони стають інваріантами доменної моделі й першими кандидатами на unit-тести.

| Область | Правило |
| --- | --- |
| Урок | Триває 50 хвилин; пробний урок безкоштовний, на ньому визначають рівень і ціль |
| Перенесення / скасування | Не пізніше ніж за 6 год до початку; 2 безкоштовні перенесення і 2 скасування на місяць |
| Пакети підписки | 8 уроків / 1 міс, 16 / 2 міс, 24 / 3 міс, 48 / 6 міс |
| Поновлення | Автоматичне; нагадування за 3 дні до списання |
| Скасування підписки | Клуби, self-study і тренажер слів доступні до кінця оплаченого періоду; невикористані уроки лишаються на балансі |
| Зміна плану | Купівля нового пакета оновлює дату підписки й нараховує нові уроки |
| Повернення | 28-денна гарантія повернення коштів |
| Бонуси | +3 уроки на старті, «приведи друга», сімейна знижка 10% |
| Пауза | Зберігає розклад, викладача і кошти на балансі |
| Групові заняття | До 7 студентів у групі, один рівень, регулярний графік |
| Розмовні клуби | До 12 людей, рівні A1–C1, формат Coffee Talk на 15 хв |
| Програма рівня | За CEFR 100–200 навчальних годин; рівень розбитий на 54–59 уроків |
| Викладачі | Типи: носій (native) і локальний; профіль з освітою та стажем |

## Архітектура: модульний моноліт

Один Symfony-застосунок, розбитий на модулі (bounded contexts); межі між ними перевіряє Deptrac у CI. Мікросервіси не потрібні.

| Модуль | Відповідальність | Стиль |
| --- | --- | --- |
| Shared | Money, Clock, UUID, базові події, Doctrine-типи | Бібліотека |
| Identity | User, ролі, реєстрація, OAuth, magic link | Середній |
| Content | Сторінки, блог, FAQ, відгуки, SEO, меню | Плаский CRUD |
| Catalog | Курси, програми, рівні CEFR, цілі | Плаский CRUD |
| Teaching | Викладачі, профілі, спеціалізації, доступність | Середній |
| Scheduling | Індивідуальні уроки, слоти, перенесення, workflow | Шари DDD |
| GroupClasses | Групи, розмовні клуби, записи, лист очікування | Середній |
| Billing | Продукти, замовлення, платежі, підписки, промо, сертифікати | Шари DDD |
| Learning | Self-study курси, домашки, вправи, прогрес | Шари DDD |
| Vocabulary | Слова, набори, інтервальні повторення | Середній |
| Assessment | Тест рівня | Середній |
| Leads | Заявки на пробний урок, передзвін, B2B-запити | Плаский CRUD |
| Notification | Email, Telegram, in-app через Mercure | Інфраструктура |

Модулі з шарами мають структуру `src/<Module>/{Domain,Application,Infrastructure,UI}`; пласкі модулі — `Entity/`, `Repository/`, `Controller/` усередині `src/<Module>/`. Модулі спілкуються через доменні події (Messenger) і публічні фасади, а не через чужі Doctrine-сутності.

## Системний дизайн і фронтенд

&#91;embedded content: системний дизайн · 1 застосунок, 3 сховища/процеси, зовнішні інтеграції\]

Вебхуки платіжної системи приходять прямо в застосунок, а вихідні виклики до зовнішніх сервісів виконує worker асинхронно через Messenger.

- **Публічна частина:** Twig + Symfony UX Twig Components для SSR; сторінки повністю працюють без JavaScript. Стилі — Tailwind + daisyUI через AssetMapper і `symfonycasts/tailwind-bundle`. Інтерактивні місця (каруселі, тест рівня, форма заявки) мають плейсхолдери `data-island` + `data-props` під майбутні Vue-острівці.
- **Межа бекенд / UI:** PHP-класи компонентів (дані) пишете ви; шаблони секцій, UI-kit (`templates/components/Ui/`) і тему генерує AI за брифом.
- **Кабінет студента й викладача:** API-first. Етапи 4–8 будуються як JSON API й перевіряються функціональними тестами, Swagger UI від API Platform і `.http`-файлами PhpStorm. `/app/*` поки віддає Twig-заглушку.
- **Адмінка:** EasyAdmin (перевірити сумісність із 8.1 на момент старту).
- **API:** перший модуль (Leads) пишемо руками — `#[MapRequestPayload]`, Serializer, Validator, DTO; для ресурсів кабінету далі API Platform 4 зі State Providers/Processors.
- **Автентифікація:** сесійні cookie (HttpOnly, SameSite) + CSRF, бо майбутній SPA буде на тому ж домені; вбудований login link, Google OAuth (`knpuniversity/oauth2-client-bundle`), верифікація email. JWT — лише якщо з'явиться мобільний клієнт.

**Коли дійде до Vue:** SPA на `/app/*` (TypeScript, Vite через `pentatrion/vite-bundle`, Vue Router, Pinia, TanStack Vue Query, vue-i18n) споживає готовий API; TS-типи генеруються з OpenAPI (`openapi-typescript`). Перехід з AssetMapper на Vite — близько пів дня. Альтернатива для форм — Symfony UX Live Components (інтерактивність на PHP), але тоді Vue може й не знадобитися; вирішувати свідомо.

## Ключові технічні рішення

Саме ці рішення дають найбільше знань про Symfony, тому кожне прив'язане до компонента.

| Задача | Рішення | Компонент |
| --- | --- | --- |
| Баланс уроків | Append-only ledger `LessonBalanceEntry` (+8 пакет, −1 урок, +1 повернення, +3 бонус); баланс = сума записів | Doctrine, доменна модель |
| Правило «6 год, 2 на місяць» | Voter + доменна політика; час лише через `ClockInterface` | Security Voters, Clock |
| Стани уроку | `requested → scheduled → in_progress → completed / cancelled_by_student / cancelled_by_teacher / no_show` | Workflow |
| Подвійне бронювання | Унікальний індекс `(teacher_id, starts_at)` + блокування | Lock, транзакції |
| Час і таймзони | Усе в UTC, IANA-таймзона у профілі, `DateTimeImmutable` | Clock, Intl |
| Нагадування, автосписання | Періодичні задачі, idempotent-хендлери | Scheduler, Messenger |
| Платежі | Stripe test mode, вебхуки з ідемпотентністю по event id | HttpClient, Webhook, RemoteEvent |
| Гроші | Цілі мінорні одиниці, `brick/money` | Doctrine custom type |
| Форми заявок | Ліміт запитів, капча, асинхронний лист + Telegram | Form, RateLimiter, Notifier |
| Мультимовність | `/{_locale}` у роутах, таблиці перекладів контенту, `hreflang` | Routing, Translation |
| Realtime | Лічильник місць у клубі, сповіщення | Mercure |
| Інтервальні повторення | SM-2 або FSRS у модулі Vocabulary | Чиста доменна логіка |
| Типи вправ | JSON-payload + типізовані DTO з discriminator | Serializer, Doctrine JSON |

## Модель даних (ядро)

Орієнтовний перелік сутностей по модулях; ідентифікатори — UUID v7.

| Модуль | Сутності |
| --- | --- |
| Identity | `User` (ролі STUDENT, TEACHER, ADMIN), `StudentProfile` (рівень CEFR, цілі, таймзона) |
| Teaching | `TeacherProfile` (native/local, освіта, стаж, спеціалізації, slug, фото), `AvailabilityRule`, `AvailabilityException` |
| Scheduling | `Lesson` (student, teacher, startsAt, status, videoRoomUrl, isTrial), `LessonBalanceEntry` |
| Billing | `Product` (subscription/package, уроки, період, ціна), `Order`, `Payment`, `Subscription` (status, currentPeriodEnd, nextChargeAt), `PromoCode`, `GiftCertificate`, `Referral` |
| Learning | `Course` → `Module` → `CourseLesson` → `Exercise`; `Enrollment`, `ExerciseAttempt`, `Homework` |
| GroupClasses | `GroupClass`, `SpeakingClub` (level, capacity, startsAt), `Booking` |
| Vocabulary | `Word`, `WordSet`, `UserWordProgress` (easeFactor, interval, dueAt) |
| Assessment | `PlacementTest` → `Question` → `TestResult` |
| Leads | `Lead` (type, status через Workflow) |
| Content | `Page`, `BlogPost`, `Category`, `Faq`, `Review` + таблиці перекладів |

## Roadmap

Разом 6–8 місяців при 10–15 год на тиждень; етапи 0–6 уже дають завершений проєкт для портфоліо. Детальний план кожного етапу - у `docs/stages/stage-<N>.md` (опційний Vue-фронтенд - етап 11).

1. **Етап 0. Фундамент (1 тиждень).** Docker (FrankenPHP, PostgreSQL, Redis, Mailpit), інструменти якості, CI, AssetMapper + Tailwind + daisyUI.
2. **Етап 1. Каркас публічного сайту (2–3 тижні).** Layout, локалізовані роути, головна з Twig Components (UI генерує AI), статичні сторінки, FAQ, відгуки, EasyAdmin. *Вивчаєте:* Routing, Twig Components, Doctrine, Migrations, Foundry, Translation.
3. **Етап 2. Каталоги й блог (2–3 тижні).** Курси, викладачі з фільтрами й пагінацією, профіль викладача, блог, SEO (sitemap, hreflang, OpenGraph, schema.org), HTTP-кеш. *Вивчаєте:* QueryBuilder, N+1, Cache.
4. **Етап 3. Ліди й тест рівня (2 тижні).** Форма пробного уроку, передзвін, розсилка, тест рівня на серверних формах, листи й Telegram, workflow ліда. *Вивчаєте:* Form, Validator, RateLimiter, Messenger, Mailer, Notifier.
5. **Етап 4. Ідентифікація + API кабінету (2–3 тижні).** Реєстрація, логін, magic link, Google OAuth, ролі, voters; JSON API з OpenAPI/Swagger UI, `.http`-файли, Twig-заглушка `/app/*`. *Вивчаєте:* Security, Serializer, API Platform.
6. **Етап 5. Розклад та індивідуальні уроки (3–4 тижні).** Доступність викладача, бронювання, перенесення й скасування, workflow уроку, нагадування, Jitsi — усе через API. *Вивчаєте:* Workflow, Lock, Clock, Scheduler.
7. **Етап 6. Білінг (3–4 тижні).** Ціни, checkout, вебхуки, ledger, автопоновлення, пауза, промокоди, сертифікати, реферали, повернення. *Вивчаєте:* Webhook, RemoteEvent, HttpClient, ідемпотентність.
8. **Етап 7. Навчальний контент (3–4 тижні).** Self-study курси, домашки, прогрес CEFR, тренажер слів. *Вивчаєте:* складні Doctrine-моделі, Flysystem.
9. **Етап 8. Групи й розмовні клуби (2 тижні).** Ліміт місць, лист очікування, realtime-лічильник. *Вивчаєте:* Mercure.
10. **Етап 9. B2B, діти, аналітика (2 тижні).** Лендінги `/corp/` і `/kids/`, дашборд адміна.
11. **Етап 10. Продакшн-якість (постійно + 2 тижні).** WebTestCase, Sentry, профілювання, деплой, бекапи.
12. **Опційно: Vue-фронтенд.** Острівці на місці плейсхолдерів, SPA кабінету поверх готового API; Vite, Vitest, Playwright.

## Мапа WordPress → Symfony

Головний зсув — від глобального стану до DI-контейнера і явних залежностей.

| WordPress | Symfony |
| --- | --- |
| `add_action` / `add_filter` | EventDispatcher, event subscribers, декоратори сервісів |
| `WP_Query`, `$wpdb` | Doctrine repositories, QueryBuilder, DQL |
| `wp_cron` | Scheduler + Messenger worker |
| Шорткоди, блоки | Twig Components / Live Components |
| `get_option`, константи | `.env`, parameters, `#[Autowire]` |
| Nonces | CSRF tokens |
| Capabilities, `current_user_can` | Roles + Voters, `#[IsGranted]` |
| `register_rest_route` | Контролери з атрибутами / API Platform |
| Transients | Cache component (tag-aware pools) |
| Глобальні функції плагіна | Сервіси в DI-контейнері, autowiring |
| `dbDelta` | Doctrine Migrations |

## Джерела

- [englishdom.com/ua](https://www.englishdom.com/ua/), [ціни](https://www.englishdom.com/ua/prices/?type=subscription), [викладачі](https://www.englishdom.com/ua/repetitory/anhliiska-mova/)
- [Symfony 8.1 release](https://symfony.com/releases/8.1)
