# LinguaSchool: етап 2. Каталоги й блог - детальний план

Oct 9, 2026 · @Alex Hamenko

Після етапу 2 (приблизно 2-3 тижні) на сайті є каталог курсів, каталог викладачів із фільтрами й пагінацією, профіль викладача, блог і повноцінне SEO. Сторінки швидкі завдяки HTTP-кешу і кешу фрагментів у Redis.

Попередній: [етап 1](stage-1.md). Нові модулі: `Catalog` (курси, рівні, цілі) і `Teaching` (викладачі). Блог живе в `Content`. Усі три - пласкі модулі (`Entity/`, `Repository/`, `Controller/`, `Twig/`).

## Етап 2. Каталоги й блог (≈2-3 тижні)

### 2.1 Redis і кеш (≈0,5 дня)

1. Додайте сервіс `redis` (Valkey) у `compose.yaml` і `compose.override.yaml`, змінну `REDIS_URL` у `.env`. Перевірте, що в образі є розширення `redis` (у `Dockerfile` шаблону - `install-php-extensions`), і перезберіть через `make build`.
2. `config/packages/cache.yaml`: `app: cache.adapter.redis`, окремий tag-aware пул `cache.content` для фрагментів контенту.
3. Сесії поки лишаються у файлах. На Redis їх переводимо на етапі 4 разом із логіном.

**Що вивчаєте:** Cache component, адаптери й пули, `TagAwareCacheInterface`.

### 2.2 Каталог курсів (≈3 дні)

| Сутність | Поля | Переклад |
| --- | --- | --- |
| `Course` | id, slug-ключ, level (enum `CefrLevel`: A1...C2), goals (набір `Goal`), format (individual/group/self-study), lessonsCount, cover, position, isPublished | `CourseTranslation`: locale, slug, title, teaser, body, metaTitle, metaDescription |
| `Goal` | id, key (`business`, `travel`, `exam`, `it`...), position | `GoalTranslation`: locale, title |

1. `CefrLevel` - PHP backed enum у `Shared` (він знадобиться в Assessment, GroupClasses, Learning); Doctrine маппить його через `enumType`.
2. Сторінки: `/ua/course/` (список із фільтром за метою і рівнем) і `/ua/course/{slug}/` (сторінка курсу з FAQ і відгуками).
3. Фільтри приймайте як DTO через `#[MapQueryString]` з валідацією: невідомий рівень дає 400 або ігнорується (вирішіть самі і зафіксуйте тестом).
4. Замініть `YamlCourseTeaserProvider` з етапу 1 на Doctrine-реалізацію в `Catalog`. Інтерфейс лишається в `Content` (модуль-споживач), реалізація - у `Catalog`. Перемикач - один alias (`#[AsAlias]` на реалізації). Перевірте в Deptrac, що `Content` не залежить від `Catalog`.
5. CRUD у EasyAdmin, фабрики Foundry і оновлена story.

**Що вивчаєте:** enum у Doctrine, ManyToMany, `#[MapQueryString]`, інверсія залежностей між модулями.

### 2.3 Викладачі (≈3-4 дні)

| Сутність | Поля | Переклад |
| --- | --- | --- |
| `TeacherProfile` | id, slug, type (enum `native`/`local`), photo, experienceYears, specializations (масив ключів або зв'язок на `Goal` за id), rating, isPublished | `TeacherProfileTranslation`: locale, name, headline, bio, education |

1. На етапі 2 `TeacherProfile` ще не прив'язаний до `User`. Поле `userId` (nullable UUID) з'явиться на етапі 4. Посилання між модулями - лише за UUID, без Doctrine-асоціацій.
2. Список `/ua/repetitory/anhliiska-mova/` з фільтром "Всі / Носії / Локальні" і пагінацією. Пагінацію спершу зробіть на `Doctrine\ORM\Tools\Pagination\Paginator`, щоб побачити, як вона робить два запити (count і вибірку). Готовий бандл (Pagerfanta) - лише якщо свій варіант почне дублюватися.
3. Профіль `/ua/repetitory/anhliiska-mova/{slug}/`: біографія, освіта, спеціалізації, відгуки. Кнопка "Записатися" поки веде на форму пробного уроку (етап 3).
4. Фото викладачів: `ImageField` в EasyAdmin з завантаженням у `public/uploads/teachers/`. На Flysystem переходимо на етапі 7.
5. Секції головної, що показують викладачів, отримують дані через інтерфейс-провайдер, як курси в 2.2.

**Що вивчаєте:** пагінація, `QueryBuilder`, умовні фільтри, завантаження файлів у формах.

### 2.4 Блог (≈3 дні)

| Сутність | Поля | Переклад |
| --- | --- | --- |
| `BlogPost` | id, category, cover, publishedAt, isPublished, authorName, readingTime | `BlogPostTranslation`: locale, slug, title, excerpt, body, metaTitle, metaDescription |
| `BlogCategory` | id, position | `BlogCategoryTranslation`: locale, slug, title |

1. Сторінки: `/ua/blog/` (пагінація), `/ua/blog/{category}/`, `/ua/blog/{category}/{slug}/`. Стаття з майбутньою датою `publishedAt` не показується: перевірка через `ClockInterface`, не через `new \DateTime()`.
2. `readingTime` рахується в доменному сервісі при збереженні (Doctrine listener `prePersist`/`preUpdate` або явний виклик з адмінки - порівняйте підходи через `/ask`).
3. "Схожі статті" з тієї ж категорії одним запитом.
4. Тексти статей: HTML з `TextEditorField`. Markdown (`twig/markdown-extra` + `league/commonmark`) - лише якщо захочете, перевірте сумісність.

**Що вивчаєте:** Doctrine listeners, `ClockInterface` у запитах, ієрархічні URL.

### 2.5 SEO (≈2 дні)

1. `sitemap.xml`: контролер, який віддає URL усіх опублікованих сторінок, курсів, викладачів і статей для обох локалей з `xhtml:link rel="alternate" hreflang`. Відповідь кешується (2.6). Великий sitemap пізніше можна розбити на sitemap index.
2. `robots.txt` з посиланням на sitemap; у dev - `Disallow: /`.
3. schema.org у JSON-LD через компонент `SeoMeta` з етапу 1: `Organization` на головній, `Course`, `Person` (викладач), `Article` (стаття), `FAQPage` (FAQ), `BreadcrumbList` на вкладених сторінках. Дані збирає PHP, шаблон лише виводить `json_encode`.
4. Хлібні крихти - окремий компонент з даними від контролера.
5. Перевірте сторінки в Google Rich Results Test і валідаторі schema.org.

**Що вивчаєте:** Response з нестандартним Content-Type, `UrlGeneratorInterface` (абсолютні URL), структуровані дані.

### 2.6 Продуктивність: N+1 і HTTP-кеш (≈2 дні)

1. Пройдіться профайлером по кожній новій сторінці. Список викладачів із перекладами і спеціалізаціями - класичне N+1: виправте fetch join (`addSelect`) або окремим запитом за списком id.
2. HTTP-кеш: `#[Cache(public: true, maxage: ..., smaxage: ...)]` на публічних діях, `ETag`/`Last-Modified` для статей і `$response->isNotModified($request)`. Розберіться, чому сесія (cookie) робить відповідь `private`, і чому публічні сторінки не мають відкривати сесію.
3. Reverse proxy в dev: `framework.http_cache: true` (Symfony HttpCache) - подивіться заголовки `X-Symfony-Cache`. Для prod вирішимо на етапі 10.
4. Кеш фрагментів: важкі секції (лічильники, відгуки, меню) кешуються в пулі `cache.content` з тегами (`menu`, `review`, `course`). Інвалідація - Doctrine listener `postFlush`, який знімає теги змінених сутностей. Це закриває пункт 5 про EasyAdmin з етапу 1.

**Що вивчаєте:** HTTP caching (expiration vs validation), Cache tags, Doctrine events, профайлер.

### 2.7 Тести й готовність етапу 2 (≈2 дні, паралельно з рештою)

1. **Functional:** список курсів і викладачів з фільтрами (кількість карток), пагінація (друга сторінка, сторінка за межами - 404), стаття з майбутньою датою - 404, `sitemap.xml` валідний XML і містить обидві локалі, JSON-LD парситься.
2. **Integration:** репозиторії з фільтрами; перевірка кількості запитів (`DebugStack`-подібний middleware або профайлер у тесті) для списку викладачів.
3. **Unit:** розрахунок `readingTime`, побудова хлібних крихт, генерація JSON-LD.
4. Інвалідація кешу: змінили `MenuItem` - наступний запит бачить нове меню.

**Готовність етапу 2**

- [ ] Каталог курсів і сторінка курсу в `uk` і `en`, фільтри за метою й рівнем
- [ ] Каталог викладачів з фільтром native/local і пагінацією, профіль за slug
- [ ] Блог: список, категорія, стаття, схожі статті
- [ ] `sitemap.xml`, `robots.txt`, JSON-LD, хлібні крихти
- [ ] Головна бере курси з БД через провайдер, Deptrac без порушень
- [ ] Публічні сторінки віддають `Cache-Control: public`, кеш фрагментів інвалідується
- [ ] Жодна сторінка не робить більше ~5 SQL-запитів
- [ ] `make qa` і `make test` зелені

**Наступний крок:** [етап 3. Ліди й тест рівня](stage-3.md).
