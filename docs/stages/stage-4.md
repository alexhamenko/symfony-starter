# LinguaSchool: етап 4. Ідентифікація + API кабінету - детальний план

Oct 9, 2026 · @Alex Hamenko

Після етапу 4 (приблизно 2-3 тижні) користувачі можуть зареєструватися, увійти паролем, magic link або через Google. Ролі й voters закривають доступ, а кабінет має перший JSON API на API Platform з документацією Swagger UI.

Попередній: [етап 3](stage-3.md). Новий модуль: `Identity` (середній). Кабінет - API-first: `/app/*` віддає Twig-заглушку, перевірка - функціональні тести, Swagger UI і `.http`-файли.

## Етап 4. Ідентифікація + API кабінету (≈2-3 тижні)

### 4.1 Користувач і реєстрація (≈3 дні)

| Сутність | Поля |
| --- | --- |
| `User` | id, email (унікальний, нормалізований), password (nullable для OAuth/magic link), roles, isVerified, locale, timezone (IANA), createdAt |
| `StudentProfile` | id, userId, name, level (`CefrLevel`), goals, phone |

1. `User` реалізує `UserInterface` і `PasswordAuthenticatedUserInterface`; провайдер - `entity` за email. `make:user` можна використати як чернетку, потім прибрати зайве.
2. Реєстрація: форма на DTO, `UserPasswordHasherInterface`, унікальність email (`UniqueEntity` або перевірка в сервісі - обговоріть через `/ask`).
3. Верифікація email: лист із підписаним посиланням (`UriSigner` з етапу 3 або `symfonycasts/verify-email-bundle` - перевірте сумісність). Неверифікований користувач входить, але частина дій заборонена.
4. Відновлення пароля: токен з терміном дії, одноразовий, без розкриття, чи існує email.
5. Консольна команда `app:user:create-admin` (`#[AsCommand]`) замінює in-memory користувача з етапу 1; `security.yaml` переходить на entity provider.
6. Лід із тим самим email після реєстрації прив'язується до користувача за `userId` (подія `UserRegistered`, модуль `Leads` її слухає).

**Що вивчаєте:** Security (users, providers, password hashers), події реєстрації, консольні команди.

### 4.2 Логін (≈2-3 дні)

1. Firewall `main`: `form_login` для сайту, `json_login` для API (`POST /api/login`), `logout`, `remember_me`, `login_throttling` (на RateLimiter).
2. Login link (вбудований у Security): "Увійти без пароля" - лист із посиланням, обмеження за часом і кількістю використань.
3. Сесії переносимо в Redis (`RedisSessionHandler`), cookie `HttpOnly`, `SameSite=Lax`, `Secure`.
4. CSRF для API на сесіях: майбутній SPA на тому ж домені, тому JWT не потрібен. Розберіть stateless CSRF (перевірка Origin/заголовка) і чому `SameSite` не замінює CSRF повністю.
5. Події Security (`LoginSuccessEvent`, `LoginFailureEvent`): оновлення `lastLoginAt`, аудит-лог невдалих входів.

**Що вивчаєте:** authenticators, firewalls, login link, remember me, сесії, CSRF для API.

### 4.3 Google OAuth (≈1-2 дні)

1. `knpuniversity/oauth2-client-bundle` + `league/oauth2-google` (перевірте сумісність з 8.1).
2. Власний authenticator на базі `OAuth2Authenticator`: знайти користувача за Google id, далі за email (зв'язати акаунти, якщо email верифікований у Google), інакше створити.
3. Ключі - у secrets vault, redirect URI для `https://localhost`.

**Що вивчаєте:** custom authenticator, Passport і badges, зовнішні провайдери ідентичності.

### 4.4 Авторизація (≈1-2 дні)

1. Ролі `ROLE_STUDENT`, `ROLE_TEACHER`, `ROLE_ADMIN`, `role_hierarchy`. `TeacherProfile` (етап 2) отримує `userId`.
2. Voters для правил, що залежать від об'єкта: студент бачить лише свій профіль, викладач - лише своїх студентів (з етапу 5). `#[IsGranted]` на діях і в API Platform (`security` на операціях).
3. `access_control` лише для грубих правил (`^/admin`, `^/app`, `^/api`); тонкі правила - voters.
4. EasyAdmin: керування користувачами і ролями, impersonation (`switch_user`) для адміна.

**Що вивчаєте:** Voters, `#[IsGranted]`, role hierarchy, `switch_user`.

### 4.5 API Platform і перші ресурси (≈3-4 дні)

1. `make composer c='require api-platform/symfony'` (перевірте версію з підтримкою Symfony 8.1). Swagger UI на `/api/docs`.
2. Ресурси - не сутності, а DTO у `Identity/UI/Api` (або `Api/` у модулі) з State Providers/Processors: `GET /api/me`, `PATCH /api/me` (профіль, таймзона, локаль), `POST /api/me/password`.
3. Групи серіалізації і валідації, формат помилок Problem Details, пагінація колекцій.
4. Порівняйте з ручним API з 3.4: що API Platform робить за вас і чого коштує ця магія.
5. `.http`-файли в `http/` з оточеннями (`http-client.env.json`): логін, отримання CSRF, запити кабінету.
6. `/app/*` - один контролер з Twig-заглушкою "Кабінет у розробці" і посиланням на Swagger UI для адміна.

**Що вивчаєте:** API Platform 4 (resources, operations, State Providers/Processors), OpenAPI, Serializer groups.

### 4.6 Тести й готовність етапу 4 (≈2 дні, паралельно з рештою)

1. **Functional:** реєстрація, верифікація (валідне, прострочене посилання), логін паролем, login link, logout, throttling, доступ до `/admin` і `/api/me` з різними ролями, `PATCH /api/me` з невалідними даними (422).
2. **API:** `ApiTestCase` від API Platform, перевірка схеми відповіді (`assertMatchesResourceItemJsonSchema`).
3. **Unit:** voters (матриця роль x власник x дія), нормалізація email.
4. **Anti-leak:** два запити від різних користувачів без перезавантаження ядра не бачать даних один одного.
5. OAuth-authenticator з підміненим клієнтом (`MockHttpClient` або фейковий провайдер).

**Готовність етапу 4**

- [ ] Реєстрація, верифікація email, відновлення пароля
- [ ] Логін паролем, magic link і Google; logout, remember me, throttling
- [ ] Ролі й voters; адмін створюється консольною командою
- [ ] Сесії в Redis, CSRF для API
- [ ] API Platform: `/api/me`, Swagger UI, `.http`-файли
- [ ] `/app/*` віддає заглушку лише залогіненим
- [ ] `make qa` і `make test` зелені, Deptrac без порушень

**Наступний крок:** [етап 5. Розклад та індивідуальні уроки](stage-5.md).
