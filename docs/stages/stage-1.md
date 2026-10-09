# LinguaSchool: етап 1. Каркас публічного сайту - детальний план

Sep 28, 2026 · @Alex Hamenko

Після етапу 1 (приблизно 2-3 тижні) у вас є двомовна головна сторінка, статичні сторінки та адмінка для контенту.

Попередній: [етап 0](stage-0.md). Фокус - бекенд: PHP-класи компонентів, контролери, сутності й тести пишете ви, шаблони секцій, UI-kit і тему генерує AI (див. "Межа бекенд / UI" у `PROJECT_CONTEXT.md`).

## Етап 1. Каркас публічного сайту (≈2-3 тижні)

### 1.1 Модулі й локалізований роутинг (≈2 дні)

1. Видаліть дефолтні `src/Controller`, `src/Entity`, `src/Repository`. Створіть `src/Shared/` і `src/Content/` (пласкі підпапки `Entity/`, `Repository/`, `Controller/`, `Twig/`).
2. У `config/packages/doctrine.yaml` - mapping на кожен модуль (`dir: '%kernel.project_dir%/src/Content/Entity'`, `prefix: 'App\Content\Entity'`). У `config/routes.yaml` - імпорт атрибутних роутів із `src/*/Controller/`.
3. Локалі: `uk` (за замовчуванням) і `en`. Префікси в URL як в оригіналі - `/ua/` і `/en/` - через локалізований префікс у `routes.yaml`: `prefix: { uk: '/ua', en: '/en' }`. Корінь `/` → 301 на `/ua/` (окремий контролер або визначення мови з `Accept-Language`).
4. `framework.enabled_locales: [uk, en]`, `default_locale: uk`, переклади в `translations/messages+intl-icu.uk.yaml` і `.en.yaml`. Ключі за секціями: `home.hero.title`, `footer.contacts` тощо.
5. Перемикач мов у хедері генерує той самий роут з іншим `_locale` (`app.request.attributes.get('_route')` + `_route_params`); у `<head>` - `hreflang` для кожної локалі і `x-default`.
6. Двомовний контент у БД - власні таблиці перекладів (див. 1.2), а не сторонній бандл: так ви зрозумієте Doctrine-зв'язки й індекси.

**Що вивчаєте:** Routing (атрибути, localized routes, requirements), Translation, ICU MessageFormat, конфігурація бандлів.

### 1.2 Сутності Content, міграції, фікстури (≈3 дні)

Патерн для перекладного контенту: основна сутність тримає мовно-незалежні поля, а `…Translation` - тексти для однієї локалі, з унікальним індексом `(parent_id, locale)`.

| Сутність | Поля | Переклад |
| --- | --- | --- |
| `Page` | id (UUID v7), key (`about`, `contacts`, `terms-of-use`…), isPublished, updatedAt | `PageTranslation`: locale, slug, title, body (HTML), metaTitle, metaDescription |
| `Faq` | id, category (`general`, `prices`, `teachers`), position, isPublished | `FaqTranslation`: locale, question, answer |
| `Review` | id, authorName, avatar, source (`enguide`, `instagram`), videoUrl (nullable), position, isPublished | `ReviewTranslation`: locale, text |
| `Stat` | id, value (`70k+`, `1100`), position | `StatTranslation`: locale, label |
| `MenuItem` | id, location (`header`, `footer-1`…), position, routeName або url | `MenuItemTranslation`: locale, label |

1. Генеруйте через `make:entity`, потім приберіть зайве й додайте типи: `Uuid` як id з `UuidV7`, `DateTimeImmutable`, конструктор замість сетерів там, де поле обов'язкове.
2. Репозиторії з методами під конкретні сторінки: `PageRepository::findPublishedBySlug(string $slug, string $locale)` з одним JOIN на переклад - перевірте в профайлері, що це один запит.
3. Міграції: `make:migration`, уважно читайте згенерований SQL перед `migrate`. Одна міграція на логічну зміну.
4. Foundry-фабрики для кожної сутності + story `ContentStory` з реалістичними українськими й англійськими текстами (ваші власні, не з оригіналу). `make db-reset` дає наповнений сайт.
5. Невеликий Twig-хелпер `trans_field(entity, 'title')` або метод `getTranslation(locale)` з fallback на `uk`.

**Що вивчаєте:** Doctrine mapping атрибутами, зв'язки OneToMany, індекси, Migrations, Foundry, профайлер запитів.

### 1.3 Layout і головна сторінка (≈4-5 днів вашого часу)

`templates/base.html.twig` з блоками `title`, `meta`, `body`; компоненти `Header` (перемикач «Для дорослих / Для компаній / Для дітей», мова, «Увійти»), `Footer` (меню з `MenuItem`, контакти), `PromoBar`, `CookieBanner`. Кожна секція головної - окремий Twig Component.

**Межа між бекендом і згенерованим UI**

| Шар | Де лежить | Хто пише |
| --- | --- | --- |
| Секції-компоненти (дані) | `src/Content/Twig/Components/*.php` - отримують дані з репозиторіїв/провайдерів, віддають типізовані публічні властивості | Ви |
| Шаблони секцій | `templates/components/Home/*.html.twig` - лише розмітка з UI-kit, без запитів і логіки | AI |
| UI-kit | `templates/components/Ui/*.html.twig` - анонімні компоненти з `{% props %}`: `Button`, `Card`, `Badge`, `Container`, `Section`, `SectionHeading`, `Accordion`, `Carousel` | AI |
| Тема | `assets/styles/app.css` - Tailwind + daisyUI, дизайн-токени | AI |

Спочатку ви пишете PHP-класи й навмисно «голі» шаблони (заголовок + `dump` даних), потім віддаєте AI бриф на верстку. Згенерований код комітьте окремо з префіксом `ui:`.

**Секції головної**

| Секція (зверху вниз) | Компонент | Дані | На етапі 1 |
| --- | --- | --- | --- |
| Hero + фото-картки | `HomeHero` | Переклади | Статична сітка |
| Стрічка бейджів | `BadgeMarquee` | Статичний масив | CSS-анімація |
| «Твій прогрес в одному місці» | `InOnePlace` | Переклади | Bento-сітка |
| Групові заняття | `GroupClassesPromo` | Переклади | - |
| «Не знаєш свій рівень?» | `LevelTestCta` | Переклади | Посилання-заглушка |
| Айсберг «більше ніж здається» | `MoreThanSeems` | Переклади | - |
| Якості викладачів | `TeacherTraits` | Переклади | - |
| Банер пробного уроку | `TrialBanner` | - | Статична форма без відправки (етап 3) |
| Курси під будь-яку мету | `CoursesCarousel` | `CourseTeaserProviderInterface` | CSS scroll-snap + плейсхолдер `data-island` |
| Мобільні застосунки | `MobileApps` | Переклади | - |
| Відгуки студентів | `Testimonials` | `Review` з БД | CSS scroll-snap + плейсхолдер `data-island` |
| Лічильники | `Stats` | `Stat` з БД | Статичні числа |
| FAQ | `FaqList` | `Faq` (`general`, 6 шт.) | `<details>` |
| SEO-лонгрід | `SeoArticle` | `Page` з key `home-seo` | - |

**Плейсхолдер під майбутній Vue:** контейнер отримує `data-island="CoursesCarousel"` і `data-props="{{ props|json_encode|e('html_attr') }}"`, а всередині - робоча SSR-розмітка. Поки немає JS, який монтує острівці, атрибути просто ігноруються.

**Бриф для AI (шаблон):** скріншот референсу; список UI-компонентів з їхніми props; для кожної секції - які змінні приходять у шаблон; обмеження: лише Tailwind + daisyUI, семантичний HTML, без JavaScript, mobile-first від 360 px, доступність (alt, aria-label, контраст).

Навчальний момент - `CourseTeaserProviderInterface`. На етапі 1 його реалізує `YamlCourseTeaserProvider` (читає `config/content/courses.yaml`), а на етапі 2 ви заміните її на Doctrine-реалізацію з модуля Catalog, змінивши один alias у `services.yaml`.

### 1.4 Статичні сторінки й адмінка (≈3-4 дні)

**Сторінки етапу 1:** контакти, «Наша команда», відгуки (повний список `Review` з пагінацією), FAQ (усі категорії), три юридичні сторінки, 404/500 у стилі сайту. Одна дія `PageController::show(string $slug)` для сторінок з `Page` і окремі контролери там, де є своя логіка (відгуки, FAQ).

1. Сторінки з `Page` рендеряться через `#[MapEntity]` або явний виклик репозиторію; неопублікована або відсутня сторінка → `NotFoundHttpException`.
2. SEO-мінімум уже тут: `<title>`, `meta description`, canonical, OpenGraph з полів перекладу; окремий Twig-компонент `SeoMeta`.
3. Кастомні сторінки помилок: `templates/bundles/TwigBundle/Exception/error404.html.twig` і `error.html.twig`; перевірка через `/_error/404` у dev.

**EasyAdmin:**

1. `DashboardController` на `/admin` + CRUD-контролери для `Page`, `Faq`, `Review`, `Stat`, `MenuItem`.
2. Переклади редагуються як `CollectionField` з вкладеною формою `…TranslationType` (поле locale + тексти) - хороша вправа на Symfony Forms.
3. Поле `body` - `TextEditorField`; сортування за `position`; фільтр `isPublished`.
4. Тимчасовий захист: у `security.yaml` in-memory користувач `admin` з хешованим паролем з `.env.local` і `form_login` для firewall `admin`; `access_control` на `^/admin` → `ROLE_ADMIN`. Повноцінна автентифікація з'явиться на етапі 4.
5. Після кожної зміни контенту - інвалідація кешу фрагментів (якщо вже додали кешування).

**Що вивчаєте:** контролери й argument resolvers, Twig-наслідування і компоненти, Forms (CollectionType), Security basics, EasyAdmin.

### 1.5 Тести й готовність етапу 1 (≈2 дні, паралельно з рештою)

1. **Functional:** data provider з усіма публічними роутами × обидві локалі → статус 200, є `<title>`, є `hreflang` для `uk` і `en`; `/` → 301 на `/ua/`; неіснуючий slug → 404; `/admin` без логіну → редирект на логін.
2. **Integration:** `PageRepository::findPublishedBySlug` повертає лише опубліковане і в правильній локалі; fallback перекладу на `uk`.
3. **Unit:** `YamlCourseTeaserProvider`, Twig-хелпер перекладів.
4. **Компоненти:** `InteractsWithTwigComponents` - рендер секцій-компонентів із фікстурними даними, перевірка ключових елементів через DomCrawler. Це захищає бекенд-контракт, коли AI переписує шаблони.
5. Lighthouse вручну на головній: ціль ≥ 90 для Performance і SEO на мобільному.

**Готовність етапу 1**

- [ ] Головна зі всіма 14 секціями в `uk` і `en`, адаптивна до 360 px
- [ ] Контакти, команда, відгуки, FAQ, юридичні сторінки, 404/500
- [ ] Перемикач мов зберігає поточну сторінку, `hreflang` і canonical коректні
- [ ] Увесь контент редагується в EasyAdmin, включно з перекладами
- [ ] `make db-reset` наповнює сайт фікстурами
- [ ] Сторінки працюють без JavaScript; плейсхолдери `data-island` на місці
- [ ] Профайлер: жодна сторінка не робить більше \~5 SQL-запитів
- [ ] `make qa` і `make test` зелені, Deptrac не має порушень

**Наступний крок:** [етап 2. Каталоги й блог](stage-2.md).
