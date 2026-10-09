# LinguaSchool: етап 6. Білінг - детальний план

Oct 9, 2026 · @Alex Hamenko

Після етапу 6 (приблизно 3-4 тижні) студент бачить ціни, оплачує пакет або підписку через Stripe (test mode), уроки нараховуються в ledger після вебхука, підписка автоматично поновлюється, а промокоди, сертифікати, реферали й повернення працюють за правилами школи.

Попередній: [етап 5](stage-5.md). Модуль `Billing` - з шарами DDD. Етапи 0-6 разом дають завершений проєкт для портфоліо.

## Етап 6. Білінг (≈3-4 тижні)

### 6.1 Гроші й продукти (≈2-3 дні)

| Сутність | Поля |
| --- | --- |
| `Product` | id, type (`subscription`, `package`), lessons (8/16/24/48), periodMonths (1/2/3/6), price (Money), currency, isActive |
| `ProductTranslation` | locale, title, description |

1. Гроші - цілі мінорні одиниці. `brick/money` у `Shared` (перевірте сумісність), Doctrine custom type або embeddable (amount + currency). Жодних `float`.
2. Сторінка `/ua/prices/?type=subscription|packages`: `#[MapQueryString]` з enum; ціна за урок рахується в доменному сервісі і виводиться через `format_currency`.
3. `PriceCalculator` - чиста доменна логіка: база, знижки (промокод, сімейна 10%), сертифікат. Порядок застосування знижок зафіксуйте тестами.

**Що вивчаєте:** Doctrine custom types/embeddables, value objects для грошей, Intl-форматування.

### 6.2 Замовлення і checkout (≈3 дні)

| Сутність | Поля |
| --- | --- |
| `Order` | id, userId, productId, amount, discount, status (`pending`, `paid`, `failed`, `refunded`), promoCodeId, createdAt |
| `Payment` | id, orderId, provider, providerPaymentId, amount, status, rawPayload, createdAt |

1. Stripe Checkout у test mode. Клієнт - `HttpClient` зі scoped client (`stripe.client`, base URI, токен із secrets), а не SDK: так видно HTTP-рівень. SDK (`stripe/stripe-php`) - свідома альтернатива, обговоріть через `/ask`.
2. `POST /api/checkout` створює `Order` (`pending`) і Stripe Session з idempotency key, повертає URL оплати. Сторінки успіху/скасування лише показують статус: оплату підтверджує вебхук, а не redirect.
3. Мапінг помилок Stripe на доменні винятки; таймаути й retry HttpClient.

**Що вивчаєте:** HttpClient (scoped clients, retry, `MockHttpClient`), idempotency keys.

### 6.3 Вебхуки (≈3 дні)

1. `symfony/webhook` + `symfony/remote-event`: маршрут `/webhook/stripe`, власний `RequestParser` з перевіркою підпису Stripe і перетворенням на `RemoteEvent`.
2. Consumer (`#[AsRemoteEventConsumer]`) обробляє подію асинхронно через Messenger.
3. Ідемпотентність: таблиця оброблених event id з унікальним індексом; повторний вебхук - 200 без повторної обробки.
4. Оплата підтверджена -> `Order` `paid` -> доменна подія `OrderPaid` -> модуль `Scheduling` додає запис у ledger (+N уроків). `Billing` не пише в ledger напряму.
5. Локально - Stripe CLI (`stripe listen --forward-to https://localhost/webhook/stripe`).

**Що вивчаєте:** Webhook, RemoteEvent, ідемпотентність, eventual consistency між модулями.

### 6.4 Підписки (≈4 дні)

| Сутність | Поля |
| --- | --- |
| `Subscription` | id, userId, productId, status (`active`, `paused`, `past_due`, `cancelled`, `expired`), currentPeriodStart, currentPeriodEnd, nextChargeAt, pausedAt |

1. Workflow підписки зі станами вище; переходи - через доменні методи і слухачі Workflow.
2. Автопоновлення: Scheduler щодня знаходить підписки з `nextChargeAt`, нагадування за 3 дні до списання, списання збереженим способом оплати; невдача -> `past_due` і повторні спроби.
3. Пауза: зберігає розклад, викладача і баланс. Скасування: доступ до клубів, self-study і тренажера слів до кінця оплаченого періоду, невикористані уроки лишаються.
4. Зміна плану: купівля нового пакета оновлює дату підписки і нараховує нові уроки.
5. Фасад `SubscriptionStatusProviderInterface` для інших модулів (інтерфейс належить споживачу, наприклад `GroupClasses` на етапі 8).

**Що вивчаєте:** Scheduler для білінгу, Workflow, фасади між модулями.

### 6.5 Промокоди, сертифікати, реферали, бонуси (≈3 дні)

1. `PromoCode`: код, тип знижки (відсоток/сума/уроки), ліміт використань, термін дії; сторінка `/ua/promo/`. Конкурентне використання останнього промокоду - під lock або атомарним `UPDATE ... WHERE used < limit`.
2. `GiftCertificate`: купівля (як замовлення), унікальний код, активація іншим користувачем; сторінка `/ua/gift-certificates/`.
3. `Referral`: реферальне посилання, бонус обом після першої оплати запрошеного; сторінка `/ua/referral-program/`.
4. +3 уроки на старті - запис ledger після першої оплати; сімейна знижка 10% - через `PriceCalculator`.

**Що вивчаєте:** конкурентні оновлення в БД, доменні сервіси, тестування правил знижок.

### 6.6 Повернення і адмінка (≈2 дні)

1. 28-денна гарантія: `POST /api/orders/{id}/refund` (або дія адміна), Stripe Refund API, `Order` -> `refunded`, коригувальні записи ledger (невикористані уроки знімаються).
2. EasyAdmin: замовлення й платежі лише для читання, підписки з діями Workflow, промокоди і сертифікати.

### 6.7 Тести й готовність етапу 6 (≈3 дні, паралельно з рештою)

1. **Unit:** `PriceCalculator` (комбінації знижок), Money-округлення, політика повернення (день 28 і 29).
2. **Integration:** вебхук двічі - одне нарахування; `OrderPaid` -> ledger +N; промокод з лімітом 1 під конкурентним використанням.
3. **Functional:** checkout з `MockHttpClient`, вебхук з невалідним підписом - 400, підписка: поновлення, `past_due`, пауза, скасування (з `MockClock`).
4. Ручна перевірка зі Stripe CLI на тестових картках (успіх, відмова, 3DS).

**Готовність етапу 6**

- [ ] Сторінка цін для підписок і пакетів у `uk` і `en`
- [ ] Оплата через Stripe test mode, уроки нараховуються лише після вебхука
- [ ] Повторні вебхуки не дублюють нарахування
- [ ] Автопоновлення з нагадуванням за 3 дні, пауза, скасування, зміна плану
- [ ] Промокоди, сертифікати, реферали, стартовий бонус, сімейна знижка
- [ ] Повернення в межах 28 днів з коригуванням ledger
- [ ] Гроші ніде не зберігаються у `float`
- [ ] `make qa` і `make test` зелені

**Наступний крок:** [етап 7. Навчальний контент](stage-7.md).
