# E-learning platform – terv

## 1. Áttekintés

Hierarchikus tartalomstruktúra:

```
Kurzus (Course)
 └─ Lecke (Lesson)
     ├─ Oldal (Page)
     │   └─ Tartalmi elem (ContentBlock)
     └─ Teszt kérdés (Question)
         └─ Válaszlehetőség (AnswerOption)
```

- Egy **kurzus** több **leckéből** áll.
- Egy **lecke** több **oldalból** áll, és tartalmazhat **teszt kérdéseket**.
- Egy **oldal** több **tartalmi elemből** (blokkból) áll, sorrendben.
- A tartalmi elemek típusa bővíthető: Címsor, Szövegblokk, Kép, YouTube videó.
- A kurzusokat regisztrált felhasználók végezhetik, de csak akkor fér hozzá egy felhasználó egy kurzushoz, ha az admin hozzárendelte.
- A felhasználó megjelölheti, ha elvégzett egy oldalt vagy egy leckét.
- Lecke elvégzése után a felhasználó válaszolhat a leckéhez tartozó teszt kérdésekre; a válaszokat a rendszer elmenti.

## 2. Felhasználók és szerepkörök

- `users` tábla kiegészítve egy `is_admin` (boolean, default `false`) oszloppal — ez különbözteti meg az adminisztrátort a sima regisztrált felhasználótól.
- Bejelentkezés/regisztráció: a projektben jelenleg nincs auth scaffolding telepítve, ehhez szükséges egy auth starter (pl. Laravel Breeze) hozzáadása — **ez új függőség, felhasználói jóváhagyást igényel**.
- Admin bejelentkezés ugyanazon a `users` táblán, ugyanazon login űrlapon történik, csak az `is_admin` flag dönt arról, hogy az admin felületet is elérheti-e.

## 3. Adatbázis séma

### `users` (meglévő tábla kiegészítése)

| oszlop   | típus                  | megjegyzés        |
| -------- | ---------------------- | ----------------- |
| is_admin | boolean, default false | admin jogosultság |

### `courses`

| oszlop       | típus                       | megjegyzés                     |
| ------------ | --------------------------- | ------------------------------ |
| id           | bigint PK                   |                                |
| title        | string                      |                                |
| slug         | string, unique              | URL-hez                        |
| description  | text, nullable              |                                |
| is_published | boolean, default false      |                                |
| order        | unsigned integer, default 0 | kurzusok sorrendje listázáshoz |
| timestamps   |                             |                                |

### `lessons`

| oszlop     | típus                       | megjegyzés                        |
| ---------- | --------------------------- | --------------------------------- |
| id         | bigint PK                   |                                   |
| course_id  | FK → courses                |                                   |
| title      | string                      |                                   |
| slug       | string                      | kurzuson belül egyedi             |
| order      | unsigned integer, default 0 | leckék sorrendje a kurzuson belül |
| timestamps |                             |                                   |

### `pages`

| oszlop     | típus                       | megjegyzés                       |
| ---------- | --------------------------- | -------------------------------- |
| id         | bigint PK                   |                                  |
| lesson_id  | FK → lessons                |                                  |
| title      | string                      |                                  |
| slug       | string                      | leckén belül egyedi              |
| order      | unsigned integer, default 0 | oldalak sorrendje a leckén belül |
| timestamps |                             |                                  |

### `content_blocks`

| oszlop     | típus                       | megjegyzés                                  |
| ---------- | --------------------------- | ------------------------------------------- |
| id         | bigint PK                   |                                             |
| page_id    | FK → pages                  |                                             |
| type       | string (enum érték)         | `heading`, `text`, `image`, `youtube_video` |
| data       | json                        | típusonként eltérő szerkezet, lásd lent     |
| order      | unsigned integer, default 0 | blokkok sorrendje az oldalon                |
| timestamps |                             |                                             |

Egyetlen `content_blocks` tábla, `type` megkülönböztető oszloppal és `data` JSON oszloppal — így új blokktípus bevezetése nem igényel migrációt, csak egy új enum értéket és egy új Blade komponenst a megjelenítéshez.

### `enrollments`

A kurzus–felhasználó hozzárendelést egy dedikált tábla írja le (nem egyszerű pivot), mert nyilván kell tartani, ki és mikor rendelte hozzá a kurzust.

| oszlop      | típus                | megjegyzés                         |
| ----------- | -------------------- | ---------------------------------- |
| id          | bigint PK            |                                    |
| user_id     | FK → users           | a felhasználó, aki végzi a kurzust |
| course_id   | FK → courses         | a hozzárendelt kurzus              |
| assigned_by | FK → users, nullable | melyik admin rendelte hozzá        |
| assigned_at | timestamp            | hozzárendelés időpontja            |
| timestamps  |                      |                                    |

Egyedi index: `(user_id, course_id)` — egy felhasználóhoz egy kurzus csak egyszer legyen hozzárendelve.

### `completions`

A felhasználó által elvégzettként megjelölt leckék és oldalak — egy polimorf tábla, mert ugyanaz a jelölés-logika vonatkozik leckére és oldalra is.

| oszlop           | típus      | megjegyzés                        |
| ---------------- | ---------- | --------------------------------- |
| id               | bigint PK  |                                   |
| user_id          | FK → users |                                   |
| completable_type | string     | morph típus: `Lesson` vagy `Page` |
| completable_id   | bigint     | morph id                          |
| completed_at     | timestamp  | elvégzés időpontja                |
| timestamps       |            |                                   |

Egyedi index: `(user_id, completable_type, completable_id)` — egy elem csak egyszer jelölhető elvégzettnek felhasználónként (ismételt jelölésnél `completed_at` frissül).

### `questions`

Leckéhez tartozó teszt kérdés.

| oszlop     | típus                       | megjegyzés                        |
| ---------- | --------------------------- | --------------------------------- |
| id         | bigint PK                   |                                   |
| lesson_id  | FK → lessons                |                                   |
| text       | text                        | a kérdés szövege                  |
| order      | unsigned integer, default 0 | kérdések sorrendje a leckén belül |
| timestamps |                             |                                   |

### `answer_options`

Egy kérdéshez tartozó választási lehetőség.

| oszlop      | típus                       | megjegyzés                           |
| ----------- | --------------------------- | ------------------------------------ |
| id          | bigint PK                   |                                      |
| question_id | FK → questions              |                                      |
| text        | string                      | a válaszlehetőség szövege            |
| is_correct  | boolean, default false      | admin jelöli, melyik a helyes válasz |
| order       | unsigned integer, default 0 | válaszlehetőségek sorrendje          |
| timestamps  |                             |                                      |

### `question_responses`

A felhasználó által a kérdésre adott (elmentett) válasz.

| oszlop           | típus               | megjegyzés            |
| ---------------- | ------------------- | --------------------- |
| id               | bigint PK           |                       |
| user_id          | FK → users          |                       |
| question_id      | FK → questions      |                       |
| answer_option_id | FK → answer_options | a kiválasztott válasz |
| answered_at      | timestamp           | válaszadás időpontja  |
| timestamps       |                     |                       |

Egyedi index: `(user_id, question_id)` — kérdésenként egy aktuális válasz felhasználónként (újbóli beküldésnél felülíródik). _(Megjegyzés: ez egyválasztós kérdést feltételez; többválasztós kérdéshez a válaszadás külön `question_response_answer_option` kapcsolótáblát igényelne — ld. Jövőbeli bővítések.)_

## 4. Tartalmi elem típusok (`data` JSON szerkezete)

- **Címsor (`heading`)**
    ```json
    { "text": "Bevezetés", "level": 2 }
    ```
- **Szövegblokk (`text`)**
    ```json
    { "html": "<p>Formázott szöveg…</p>" }
    ```
- **Kép (`image`)**
    ```json
    {
        "path": "course-media/123/image.jpg",
        "alt": "Leírás",
        "caption": "Opcionális felirat"
    }
    ```
- **YouTube videó (`youtube_video`)**
    ```json
    { "video_id": "dQw4w9WgXcQ", "title": "Opcionális cím" }
    ```

## 5. Eloquent modellek

```
User
 - hasMany(Enrollment::class)
 - belongsToMany(Course::class)->using(Enrollment::class)   // végzett kurzusok
 - hasMany(Completion::class)
 - hasMany(QuestionResponse::class)

Course
 - hasMany(Lesson::class)->orderBy('order')
 - hasMany(Enrollment::class)
 - belongsToMany(User::class)->using(Enrollment::class)     // hozzárendelt felhasználók

Lesson
 - belongsTo(Course::class)
 - hasMany(Page::class)->orderBy('order')
 - hasMany(Question::class)->orderBy('order')
 - morphMany(Completion::class, 'completable')

Page
 - belongsTo(Lesson::class)
 - hasMany(ContentBlock::class)->orderBy('order')
 - morphMany(Completion::class, 'completable')

ContentBlock
 - belongsTo(Page::class)
 - type: ContentBlockType (backed enum: Heading, TextBlock, Image, YoutubeVideo)
 - data: array (cast: 'array' vagy egy dedikált Castable/Data osztály típusonként)

Enrollment
 - belongsTo(User::class)
 - belongsTo(Course::class)
 - belongsTo(User::class, 'assigned_by')                    // hozzárendelő admin

Completion
 - belongsTo(User::class)
 - morphTo(name: 'completable')                              // Lesson vagy Page

Question
 - belongsTo(Lesson::class)
 - hasMany(AnswerOption::class)->orderBy('order')
 - hasMany(QuestionResponse::class)

AnswerOption
 - belongsTo(Question::class)

QuestionResponse
 - belongsTo(User::class)
 - belongsTo(Question::class)
 - belongsTo(AnswerOption::class)
```

`ContentBlockType` enum (`app/Enums/ContentBlockType.php`):

```php
enum ContentBlockType: string
{
    case Heading = 'heading';
    case TextBlock = 'text';
    case Image = 'image';
    case YoutubeVideo = 'youtube_video';
}
```

## 6. Megjelenítés

A `data` szerkezet típusonként eltérő, ezért a renderelés típus szerint delegál (pl. Blade komponens névkonvenció: `content-blocks.heading`, `content-blocks.text`, `content-blocks.image`, `content-blocks.youtube-video`), a `Page` nézet pedig sorban kirendereli az `order` szerint rendezett blokkokat.

Egy regisztrált felhasználó csak azokat a kurzusokat (és azok leckéit/oldalait) érheti el, amelyekhez van `Enrollment` rekordja — ezt egy `EnsureUserIsEnrolled` middleware vagy `CoursePolicy::view()` ellenőrzi a kurzus/lecke/oldal megjelenítő route-jain.

## 7. Elvégzés jelölése (haladás)

- Minden oldal és lecke nézetén megjelenik egy „Elvégeztem” gomb/jelölő.
- Oldal elvégzése: a felhasználó egy oldal megtekintése után jelölheti, ez létrehoz/frissít egy `Completion` rekordot (`completable_type = Page`).
- Lecke elvégzése: önálló jelölés (`completable_type = Lesson`) — nem automatikus az oldalak elvégzéséből, a felhasználó külön jelöli a leckét is elvégzettként.
- A lecke elvégzettség állapota (`Lesson::isCompletedBy($user)`) kapu-feltétel a teszt kérdések eléréséhez (ld. 8. pont).

## 8. Teszt kérdések és válaszok

- Az admin a leckéhez kérdéseket (`Question`) hoz létre, mindegyikhez több válaszlehetőséget (`AnswerOption`), és megjelöli, melyik a helyes (`is_correct`).
- A lecke nézetén a kérdések csak akkor jelennek meg/válaszolhatók, ha a felhasználóhoz tartozik `Completion` rekord az adott leckére (lásd 7. pont) — ezt egy `CanAnswerLessonQuestions` ellenőrzés (policy vagy middleware) kényszeríti ki szerveroldalon is, nem csak UI-szinten.
- A felhasználó minden kérdésre kiválaszt egy választ, ez elmentésre kerül egy `QuestionResponse` rekordként (`user_id`, `question_id`, `answer_option_id`, `answered_at`).
- Újbóli válaszadás esetén a meglévő `QuestionResponse` felülíródik (upsert `(user_id, question_id)` kulcs szerint).
- Helyesség kiértékelése nem tárolt mezőben történik, hanem futásidőben az `AnswerOption::is_correct` alapján (`QuestionResponse::isCorrect(): bool => $this->answerOption->is_correct`).

## 9. Admin felület (Vue alapú, Inertia.js)

Az admin felület **Vue 3 + Inertia.js** alapú — nem különálló SPA/API, hanem a Laravel routing/session-auth réteg felett renderelt Vue komponensek. Így nincs szükség külön REST API-ra, tokenkezelésre vagy CORS-ra az adminhoz, a validáció és jogosultságkezelés a megszokott Laravel Form Request / Policy réteggel történik.

### Új függőségek (jóváhagyást igényelnek)

- PHP: `inertiajs/inertia-laravel`
- JS: `vue`, `@inertiajs/vue3`
- Opcionális: `tightenco/ziggy` (Laravel named route-ok elérése JS-ből `route()` helperrel)

### Szerkezet

- Útvonalak `/admin` prefix alatt, `auth` + egy dedikált `admin` middleware mögött, amely az `is_admin` mezőt ellenőrzi (`App\Http\Middleware\EnsureUserIsAdmin`).
- Admin controllerek (`App\Http\Controllers\Admin\*`) `Inertia::render('Admin/...', [...])` hívással adják át az adatot a Vue oldalaknak, ugyanúgy Form Request + Policy validációval, mint egy hagyományos Blade-es controllernél.
- Vue oldalkomponensek: `resources/js/Pages/Admin/` alatt (pl. `Courses/Index.vue`, `Courses/Edit.vue`, `Lessons/Edit.vue`, `Questions/Edit.vue`, `Users/Enrollments.vue`), egy közös `Admin/Layout.vue` admin-menüvel.
- `resources/js/app.js` Inertia belépési pont, ami a Blade `admin.blade.php` root nézetbe töltődik be (`@inertia` direktíva), elkülönítve a publikus (nem-admin) felhasználói nézetektől, amik maradnak sima Blade alapúak.
- Form submit / lista-frissítés Inertia `router.visit` / `useForm()` segítségével, teljes oldalújratöltés nélkül (SPA-szerű viselkedés).

### Admin funkciók

- Kurzusok, leckék, oldalak, tartalmi blokkok CRUD felülete.
- **Teszt kérdések és válaszlehetőségek létrehozása/szerkesztése** leckénként, a helyes válasz megjelölésével.
- Felhasználók listázása.
- **Kurzus hozzárendelése felhasználóhoz**: admin kiválaszt egy felhasználót és egy kurzust, létrejön egy `Enrollment` rekord (`assigned_by` = bejelentkezett admin, `assigned_at` = most). Törlésnél a hozzárendelés megszüntethető.
- Felhasználói válaszok áttekintése (ki, melyik kérdésre, mit válaszolt, helyes volt-e).

Jogosultság: `CoursePolicy`/`EnrollmentPolicy`/`QuestionPolicy`, amit csak admin tölthet be (az admin middleware ezt eleve kizárja a nem adminoktól).

## 10. Jövőbeli bővítési lehetőségek (nincs most implementálva)

- Többválasztós (checkbox) vagy szabad szöveges kérdéstípusok.
- Teszt eredmény/pontszám összesítése leckénként vagy kurzusonként.
- Lejárat / határidő az `enrollments` rekordokon.
- Önálló kurzus-regisztráció (self-enrollment) opcionális engedélyezése publikus kurzusokhoz.
- További blokktípusok (pl. fájl letöltés, kód blokk).
- Verziózás / vázlat vs. publikált állapot leckénként.
- Kurzus teljesítés feltétele: automatikus levezetés abból, hogy minden oldal/kérdés elvégzett-e (jelenleg a lecke-jelölés független, manuális lépés).
