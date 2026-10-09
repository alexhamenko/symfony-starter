# LinguaSchool: етап 3. Ліди й тест рівня - детальний план

Oct 9, 2026 · @Alex Hamenko

Після етапу 3 (приблизно 2 тижні) сайт збирає заявки: пробний урок, передзвін, B2B-запит і підписку на розсилку. Заявки захищені від спаму, менеджер отримує лист і повідомлення в Telegram асинхронно, а статус ліда веде Workflow. Тест рівня працює на серверних формах.

Попередній: [етап 2](stage-2.md). Нові модулі: `Leads` (плаский), `Assessment` (середній), `Notification` (інфраструктура). З цього етапу з'являється Messenger, а з ним - worker-режим FrankenPHP (див. "Режим FrankenPHP" у `PROJECT_CONTEXT.md`).

## Етап 3. Ліди й тест рівня (≈2 тижні)

### 3.1 Messenger і worker-режим (≈1,5 дня)

1. `make composer c='require symfony/messenger'`. Транспорти: `async` на Redis (або Doctrine - порівняйте), `failed` для повідомлень, що вичерпали спроби, `sync` для команд, які мають виконуватися одразу.
2. Окремий compose-сервіс `worker` на тому ж образі з командою `messenger:consume async`. У dev його зручно перезапускати після змін коду (`--limit`, `--time-limit`).
3. Повідомлення - `readonly` DTO в `Application`/`Message` модуля, хендлери з `#[AsMessageHandler]`. Retry strategy і `failed` транспорт налаштуйте одразу; перегляд - `messenger:failed:show`.
4. Worker-режим FrankenPHP: приберіть `FRANKENPHP_LOOP_MAX` з `compose.override.yaml`, розберіться з `ResetInterface` і тегом `kernel.reset`, перевірте сервіси на стан у властивостях. Додайте anti-leak функціональні тести: `$client->disableReboot()`, два запити з різними локалями (а з етапу 4 - різними користувачами).
5. У тестах транспорт `async` замініть на `in-memory://` (`config/packages/test/messenger.yaml`) і перевіряйте, що повідомлення відправлено.

**Що вивчаєте:** Messenger (bus, transports, handlers, retry, failure transport), довгоживучі процеси, `ResetInterface`.

### 3.2 Лід і форма пробного уроку (≈2 дні)

| Сутність | Поля |
| --- | --- |
| `Lead` | id, type (enum: `trial`, `callback`, `corp`, `kids`), name, phone, email, level (`CefrLevel`, nullable), goal, comment, locale, source (UTM), status (marking), createdAt |

1. Форма пробного уроку - Symfony Form (`LeadTrialType`) на DTO, а не на сутності: сутність створюється в сервісі після валідації. Порівняйте з формою на сутності через `/ask`.
2. Валідація: `#[Assert\...]` на DTO, телефон у форматі E.164 (власний constraint або `odolbeau/phone-number-bundle` - перевірте сумісність).
3. Банер `TrialBanner` з етапу 1 стає робочою формою; після відправки - PRG (redirect) і flash-повідомлення. UTM-мітки зберігайте з query string першого візиту (cookie без сесії).
4. Workflow ліда (`symfony/workflow`, тип `state_machine`): `new -> contacted -> trial_scheduled -> converted` і `-> lost` з будь-якого стану. Marking store - властивість `status`. Граф - `workflow:dump lead | dot -Tpng`.

**Що вивчаєте:** Form на DTO, Validator, custom constraints, Workflow (state machine, guards, events).

### 3.3 Захист від спаму (≈1 день)

1. `symfony/rate-limiter`: ліміт за IP і за телефоном (sliding window, сховище - Redis). Перевищення - помилка у формі, а не 500.
2. Honeypot-поле і мінімальний час заповнення форми (timestamp у підписаному полі). Капча (наприклад, Cloudflare Turnstile через HttpClient) - лише якщо honeypot не впорається.
3. CSRF у формах увімкнений за замовчуванням: перевірте, як він працює на сторінці з HTTP-кешем (2.6), і розберіть stateless CSRF.

**Що вивчаєте:** RateLimiter, CSRF, взаємодія кешу і форм.

### 3.4 JSON API для лідів (≈1 день)

Перший API пишемо руками, без API Platform: так видно, що саме автоматизує API Platform на етапі 4.

1. `POST /api/leads`: дія з `#[MapRequestPayload]` на той самий DTO, що й форма (групи валідації за потреби). Відповідь 201 з `Location`, помилки валідації - 422 у форматі Problem Details.
2. Спільна бізнес-логіка (створення ліда, подія) - в одному сервісі, який викликають і форма, і API.
3. `.http`-файл `http/leads.http` для ручної перевірки в PhpStorm.

**Що вивчаєте:** `#[MapRequestPayload]`, Serializer, обробка помилок валідації в API.

### 3.5 Сповіщення (≈2 дні)

1. Після створення ліда модуль `Leads` відправляє доменну подію `LeadSubmitted` (Messenger, async). Модуль `Notification` на неї підписаний і не знає про сутність `Lead`: подія несе лише потрібні дані.
2. Листи: `TemplatedEmail` менеджеру і підтвердження клієнту (локаль клієнта), шаблони в `templates/email/`. Перевірка в Mailpit.
3. Telegram: `symfony/notifier` + `symfony/telegram-notifier`, `ChatMessage` у чат менеджерів. Токен - у `.env.local` або secrets vault.
4. Розсилка: форма підписки з double opt-in - лист із підписаним посиланням (`UriSigner` з терміном дії), підтвердження змінює статус підписника.

**Що вивчаєте:** доменні події між модулями, Mailer, Notifier, `UriSigner`, secrets.

### 3.6 Тест рівня (≈3 дні)

| Сутність | Поля | Переклад |
| --- | --- | --- |
| `PlacementTest` | id, key, isActive | `PlacementTestTranslation`: locale, title, intro |
| `Question` | id, test, level (`CefrLevel`), type (single choice, gap fill), position, correctAnswer | `QuestionTranslation`: locale, text, options |
| `TestResult` | id, testId, answers (JSON), score, level, email (nullable), createdAt | - |

1. Сторінка `/ua/test-your-english-level/` - багатокрокова серверна форма: по одному блоку питань на крок, проміжні відповіді в сесії. Перевірте, чи є в 8.1 вбудований механізм багатокрокових форм (FormFlow з'явився в 7.4) - і порівняйте з власною реалізацією.
2. Оцінювання - чиста доменна логіка (`LevelEvaluator`): бали за рівнями, визначення CEFR. Покрийте unit-тестами граничні випадки.
3. Результат: сторінка з рівнем і пропозицією пробного уроку (форма з 3.2 з заповненим рівнем). Можна залишити email, щоб отримати результат листом.
4. Контейнер тесту має `data-island="LevelTest"` під майбутній Vue.

**Що вивчаєте:** багатокрокові форми, сесія, доменна логіка без фреймворку.

### 3.7 Адмінка лідів (≈1 день)

1. CRUD `Lead` в EasyAdmin: фільтри за типом і статусом, кастомні дії для переходів Workflow (кнопка показується, лише якщо `workflow.can()`).
2. Питання тесту рівня - CRUD з перекладами (як на етапі 1).

### 3.8 Тести й готовність етапу 3 (≈2 дні, паралельно з рештою)

1. **Functional:** відправка форми (валідна, невалідна, спам-honeypot), rate limit (N+1-й запит), `POST /api/leads` (201, 422), проходження тесту рівня до результату, підтвердження розсилки за підписаним посиланням і з простроченим підписом.
2. **Асинхронність:** повідомлення потрапило в `in-memory` транспорт; хендлер окремо - `assertEmailCount`, `assertNotificationCount`.
3. **Unit:** `LevelEvaluator`, guards Workflow, генерація підписаних URL.
4. **Anti-leak:** два послідовні запити без перезавантаження ядра з різними локалями.

**Готовність етапу 3**

- [ ] Форми пробного уроку, передзвону, B2B і розсилки працюють без JavaScript
- [ ] Лід проходить статуси через Workflow, переходи доступні в EasyAdmin
- [ ] Лист менеджеру й клієнту та повідомлення в Telegram відправляються асинхронно, `failed` транспорт налаштований
- [ ] Rate limit і honeypot відсікають спам
- [ ] `POST /api/leads` з коректними 201/422
- [ ] Тест рівня визначає CEFR і веде на пробний урок
- [ ] Worker-режим FrankenPHP увімкнено, anti-leak тести зелені
- [ ] `make qa` і `make test` зелені

**Наступний крок:** [етап 4. Ідентифікація + API кабінету](stage-4.md).
