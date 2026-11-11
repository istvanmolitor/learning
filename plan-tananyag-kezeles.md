# Tananyag-kezelés újragondolása — terv

Szerkesztés, import, export, AI-s módosítás (MCP), és a „mappából importálható zip" skill.

## 0. Alapgondolat

A tananyagok jellemzően a rendszeren **kívül** állnak elő (AI-val generált `.md` fájlok).
Ezért a rendszer kanonikus csereformátuma a **kurzuscsomag**:

```
kurzus-slug.zip
├── course.json              ← manifest: minden metaadat + struktúra + sorrend
├── pages/<lecke>/<oldal>.md ← oldalankénti Markdown tartalom
├── pages/<lecke>/<oldal>.txt / .mp3  ← narráció (opcionális)
├── images/…                 ← a tartalomban hivatkozott képek
└── thumbnail.<ext>          ← kurzus borítókép (ÚJ, most hiányzik)
```

**Vezérelv:** `DB → export → üres DB-be import → export` eredménye bitre-pontosan
ugyanaz a csomag legyen (veszteségmentes oda-vissza út). Minden más funkció (szerkesztő,
import-wizard, MCP, skill) erre a formátumra épül.

---

## 1. Jelenlegi állapot és problémák

Ami már megvan:

- `CourseImporter`: zip (course.json-nal vagy anélkül), ömlesztett `.md` fájlok, önálló
  `course.json` import; slug-alapú teljes resync; kép- és narráció-beemelés.
- `CourseExporter`: course.json + oldalankénti `.md` + képek + narráció.
- Admin szerkesztők: kurzus meta (`courses/Edit.vue`), lecke/oldal lista drag&drop
  oldal-átrendezéssel (`courses/Lessons.vue`), oldal-szerkesztő content blockokkal
  (`pages/Edit.vue`), kérdés-szerkesztő.
- `course-builder` skill: nyersanyagból (PDF stb.) csomagot épít.

Problémák:

1. **Nem veszteségmentes a kör:** az exporter a content blockokat egyetlen Markdown
   dokumentummá lapítja, az importer pedig mindent egyetlen `TextBlock`-ba tölt vissza.
   A blokkstruktúra (heading/image/youtube blokkok) elvész. A thumbnail egyáltalán nem
   kerül bele az exportba. A képfájlnevek importkor random prefixet kapnak, így két
   egymás utáni export nem determinisztikus.
2. **Zavaros import UI:** három kártya (zip / md fájlok / course.json) ugyanarra az
   endpointra, hosszú magyarázó szövegekkel; a felhasználónak kell tudnia, melyik mód
   mit csinál, és nincs előnézet arról, hogy az import mit fog létrehozni/frissíteni/
   **törölni** (a resync törli a csomagban nem szereplő leckéket — ez így kockázatos).
3. **Szerkesztési hiányok:** md-importból jött kurzusnál a leírás, thumbnail, publikálás,
   kérdések üresek, de semmi nem jelzi, mi hiányzik. Meglévő kurzushoz nem lehet utólag
   `.md` fájlt feltölteni (csak teljes zip-resync-kel). Lecke-sorrendezés és inline
   átnevezés nincs (csak oldal drag&drop és külön szerkesztő oldalak).
4. **Nincs AI-s szerkesztési út** futó rendszeren (csak az export → kézi szerkesztés →
   import kör).

---

## 2. Tartalommodell: Markdown-first (ELDÖNTVE)

**Döntés:** az oldal tartalma egyetlen Markdown dokumentum (`pages.markdown` oszlop).
Speciális elemek (kép, YouTube) nyers HTML-ként élnek az md-ben — a `course-builder`
skill és az importer már most is így dolgozik. A `content_blocks` tábla kivezetésre
kerül (migráció: a meglévő blokkokat a jelenlegi exporter logikájával md-vé konvertáljuk
oldalanként). Indok: az előállítás (AI, md), a csere (zip), az AI-szerkesztés (MCP) és
a diff-elhetőség mind Markdown-t akar; a gyakorlatban az importált kurzusok ma is egyetlen
TextBlock-ból állnak oldalanként. A megjelenítéshez mentéskor cache-eljük a renderelt
HTML-t (`pages.body_html`), így a tanulói oldal nem változik/lassul.

### Túl hosszú md fájlok kezelése

Technikai korlát gyakorlatban nincs (longtext oszlop, a CommonMark parser lineáris, a
renderelt HTML cache-elve), a valódi kockázat máshol van:

- **Tanulói UX:** egy óriási md = egyetlen végtelen oldal, durva haladás-követéssel.
  Az oldal a tördelés egysége — a tartalomnak oldalakra bontva kell érkeznie.
- **Szerkesztő:** az élő előnézet nagy dokumentumnál belassulhat → debounce-olt,
  igény szerinti újrarenderelés.
- **MCP / AI szerkesztés:** a `update_page_markdown` teljes dokumentumot cserél; sok
  tízezer soros oldalnál ez tokenpazarló és csonkolás-veszélyes. Kis oldalakkal ez
  magától megoldódik; később kiegészíthető szekció-szintű tool-lal
  (`update_page_section`, `##` fejezet cseréje).

Védelem a beérkezési pontokon, nem a tárolásban:

- A validátor / import dry-run **figyelmeztet**, ha egy oldal átlép egy küszöböt
  (javaslat: ~3000 szó vagy ~50 KB), és felajánlja a bontást.
- Az importer **opcionálisan automatikusan szétbontja** a túl hosszú md-t `##`
  címsorok mentén több oldallá (a wizard megerősítő lépésében kapcsolható).
- A `course-builder` skill szabálya marad: leckénként 3–6, jól körülhatárolt oldal.

Adatmodell-változások:

| Változás | Miért |
| --- | --- |
| `pages.markdown` (longtext) + `pages.body_html` (cache) | kanonikus tartalom |
| migráció: content_blocks → markdown, majd tábla + enum + admin blokk-UI kivezetése | egyszerűsítés |
| export/import: thumbnail be a csomagba (`thumbnail.<ext>` + manifest mező) | teljesség |
| importkor determinisztikus média-fájlnevek (eredeti név megtartása, ütközésnél számozás — random prefix helyett) | stabil roundtrip |

---

## 3. Export

Három exportformátum, két eltérő céllal:

| Formátum | Cél | Jelleg |
| --- | --- | --- |
| **zip** | átvitel másik rendszerbe | kanonikus, veszteségmentes, visszaimportálható |
| **PDF** | olvasható/nyomtatható, igényes kiadvány | egyirányú publikáció |
| **PPTX** | előadható diasor | egyirányú publikáció |

A zip marad az egyetlen *csere*formátum (csak ez importálható vissza); a PDF és a PPTX
a Markdown-first tartalomból generált kiadvány. Az admin export oldalon kurzusonként
formátumválasztó.

### 3.a PDF (designos)

- Már létezik dompdf-alapú export (`pdf.course` nézet), de a dompdf CSS-támogatása
  gyenge, igényes dizájnhoz kevés. **Javaslat: áttérés Chromium-alapú renderelésre**
  (`spatie/laravel-pdf` / Browsershot): a kurzust egy erre szánt Blade/print nézet
  rendereli — borítóoldal (thumbnail, cím, leírás), tartalomjegyzék, leckénkénti
  fejezetkezdő oldalak, fej-/lábléc oldalszámmal, tipográfia a webes témával összhangban.
- **Képek benne** (a tartalom `images/` hivatkozásai a tárolt médiából ágyazódnak be).
- Videó PDF-ben nem lehetséges → a YouTube-blokk helyére a videó indexképe + cím +
  kattintható link (opcionálisan QR-kód a nyomtatott változathoz).
- A lecke-kérdések a fejezetek végén „Ellenőrző kérdések" szakaszként (megoldókulcs
  opcionálisan a dokumentum végén).

### 3.b PPTX (designos)

- Új funkció, `phpoffice/phppresentation` alapon. Leképezés: kurzus → címdia +
  tartalomjegyzék-dia; lecke → szekcióelválasztó dia; oldal → 1+ dia, az oldal md-jét
  `##`/`###` címsorok és felsorolások mentén bontva diákra (egy dia = egy címsor + a
  hozzá tartozó, diányi mennyiségű tartalom; hosszú bekezdések tömörítve/folytatódiára
  törve).
- Egységes **export-téma** (színek, betűk, logó, dia-masterek) — a PDF és a PPTX
  ugyanazt a témadefiníciót használja, hogy egy családba tartozzanak.
- **Képek benne:** a tartalom képei saját diára vagy szöveg melletti elrendezésbe kerülnek.
- **Videók, ha lehet:** a PHPPresentation nem tud natív online-videó elemet beágyazni,
  ezért alapmegoldás a videó indexképe + kattintható YouTube-link a dián (PowerPointban
  kattintva nyílik). Második lépésben megpróbálható a generált pptx utófeldolgozása
  (OOXML szinten a webes videó elem beszúrása), hogy a dián valóban lejátszható beágyazás
  legyen — ha ez túl törékenynek bizonyul, marad a link+indexkép.

### 3.c Zip (kanonikus csereformátum)

- Tartalma: course.json (title, slug, description, is_published, order, thumbnail,
  leckék/oldalak/kérdések/válaszok minden mezője és sorrendje) + oldalankénti `.md` +
  narráció `.txt`/`.mp3` + `images/` + thumbnail.
- **Nem** tartalmazza (ELDÖNTVE): enrollments, completions, question_responses,
  created_by — tehát sem a kurzus-hozzárendeléseket, sem a felhasználók haladását, sem a
  tesztválaszokat. Ezek felhasználói/példány-adatok, nem tananyag; a célrendszerben
  keletkeznek újra.
- `course.json`-ba kerüljön `format_version` mező (pl. `1`), hogy a formátum később
  bővíthető legyen törés nélkül.
- **Elfogadási teszt (automata):** seedelt kurzus → export → friss DB → import → export;
  a két csomag manifestje és fájllistája/tartalma azonos. Ez a teszt őrzi a vezérelvet.

## 4. Import újragondolva: egy drop-zóna + kétlépcsős wizard

Egyetlen import oldal, **egy** feltöltő mezővel; a backend ismeri fel, mit kapott
(zip course.json-nal / zip csak md-kkel / ömlesztett .md-k / önálló course.json — a
felismerő logika már most megvan az importerben, csak a UI tördeli szét).

**1. lépés — Elemzés (dry-run):** a feltöltés után az importer *tervet* készít, nem ír DB-t:

- mit ismert fel (formátum, kurzus cím/slug),
- új kurzus jön létre, vagy meglévőt frissít (slug-egyezés),
- diff-előnézet: létrejövő / frissülő / **törlődő** leckék és oldalak listája,
- figyelmeztetések (hiányzó fájl a manifestből, üres oldalak, hiányzó képek).

**2. lépés — Megerősítés:** a felhasználó itt dönthet:

- cél: új kurzus (akár módosított slug-gal) vagy a meglévő frissítése,
- törlésekkel járó resync-hez külön pipa („a csomagban nem szereplő tartalom törlődik").

Technika: a feltöltött csomagot az elemzés után ideiglenes helyre tesszük
(`storage/app/tmp/import-<token>`), a megerősítés a tokennel futtatja le a tényleges
importot; a tmp rendszeres takarítással jár le.

**Új import-mód:** meglévő kurzus **kiegészítése** — a kurzus-szerkesztőből (nem az
import oldalról) lehessen egy leckére `.md` fájl(oka)t dobni → új oldalak a lecke végére.
Ez nem resync: nem töröl semmit.

## 5. Szerkesztő felület (kritikus: kényelem)

Cél: **egyetlen kurzus-szerkesztő képernyő**, nem oldalanként külön űrlapok.

- **Bal oldali fa:** leckék és oldalak; drag&drop sorrendezés *mindkét szinten* (az
  oldal-mozgatás már megvan, a lecke-sorrendezés új); **inline átnevezés** (dupla
  kattintás / ceruza ikon, külön oldal betöltése nélkül); új lecke/oldal gomb; leckére
  dobott `.md` fájl = új oldalak (4. pont).
- **Jobb oldal:** a kiválasztott elem szerkesztője.
  - Kurzus: meta (cím, leírás, thumbnail, publikálás, sorrend).
  - Oldal: **Markdown szerkesztő élő előnézettel** (split nézet; CodeMirror vagy hasonló
    könnyű editor), alatta narráció (szöveg + hang). A blokk-editor megszűnik.
  - Lecke: cím + kérdések szerkesztése.
- **Hiányosság-jelzők:** a fa és a kurzuslista mutassa badge-dzsel, ha nincs leírás /
  thumbnail / egy lecke oldal vagy kérdés nélkül áll — így az md-importból jött kurzusnál
  azonnal látszik, mit kell még kitölteni.
- Mentés oldalanként (explicit Mentés gomb + piszkos-állapot jelzés); a slug átnevezéskor
  maradjon stabil (a slug csak explicit kérésre változzon, mert az import-resync kulcsa).

## 6. AI-s szerkesztés MCP-vel

Két, egymást kiegészítő út:

1. **Zip-kör (már majdnem kész):** export → Claude Code lokálisan szerkeszti az md-ket →
   import-wizard frissíti a kurzust. A dry-run diff (4. pont) ezt biztonságossá teszi.
2. **MCP szerver a futó alkalmazásban (új):** a hivatalos `laravel/mcp` csomaggal.
   - Transzport (ELDÖNTVE: élesben is kell): **HTTP (Streamable HTTP)** az elsődleges —
     a `laravel/mcp` route-ként publikálja (pl. `/mcp/courses`), így ugyanúgy megy
     lokálisan és az éles szerveren. Lokális fejlesztéshez emellett mehet stdio is.
   - **Auth élesben:** Sanctum API token + `manage-courses` jogosultság-ellenőrzés a
     tool-ok szintjén; a token előállításához egy egyszerű admin felület vagy artisan
     parancs (`php artisan mcp:token <user>`). Claude Code oldalon:
     `claude mcp add --transport http courses https://<site>/mcp/courses -H "Authorization: Bearer <token>"`.
   - Éles védelem: csak HTTPS, rate limit a route-on, és minden író tool művelete
     naplózva (ki, mikor, melyik oldalt módosította).
   - Tool-készlet (mind a meglévő service-ekre/modellekre épül):
     - `list_courses`, `get_course_structure` (fa: leckék/oldalak/kérdések, slugokkal)
     - `get_page_markdown`, `update_page_markdown`
     - `create_lesson`, `create_page`, `rename_item`, `reorder_items`, `delete_item`
     - `update_course_meta` (leírás, publikálás stb.)
     - `set_questions` (lecke kérdéseinek cseréje)
     - `export_course` / `import_course` (a meglévő service-ek hívása)
   - Így „írd át a 3. lecke bevezetőjét közérthetőbbre" típusú kérés közvetlenül a DB-n
     végezhető el, a szerkesztőben pedig azonnal látszik az eredmény.
   - A markdown-first modell (2. pont) itt is kifizetődik: a toolok sima md-t adnak-vesznek.

## 7. Skill: ömlesztett dokumentum-mappából importálható zip

A meglévő `course-builder` skill már a nyersanyag → csomag utat írja le; ezt kell
általánosítani és a formátum-változásokhoz igazítani (nem új skill kell, hanem ennek a
kibővítése):

- **Bemenet:** egy mappa vegyes, ömlesztett dokumentumokkal (PDF, docx, pptx-ből mentett
  PDF, txt, md, képek) — explicit lépés a feltárásra: mi van a mappában, mi tartozik össze.
- **Struktúra-javaslat:** a skill először kurzusvázlatot készít (leckék, oldalak címei) és
  azt mutatja meg jóváhagyásra, csak utána ír tartalmat.
- **Tartalom:** leckénként 3–6 md oldal (a meglévő skill szabályai szerint), képek
  kinyerése, kérdések generálása leckénként a course.json-ba.
- **Validálás:** új `php artisan course:validate <zip>` parancs (dry-run az importer
  elemző lépésével): manifest-séma, hivatkozott fájlok megléte, slug-ütközések, üres
  oldalak. A skill a zip összerakása után **mindig** lefuttatja; ugyanezt használja az
  import-wizard 1. lépése is — egy kód, két felhasználó.
- **Kimenet:** validált `<kurzus-slug>.zip`, amit az import-wizard (vagy a skill maga,
  artisan paranccsal) betölt.

## 8. Ütemezés — fázisok és függések

| Fázis | Tartalom | Függés |
| --- | --- | --- |
| **F1** | Export teljessé tétele (thumbnail, format_version, determinisztikus nevek) + roundtrip elfogadási teszt | — |
| **F2** | Markdown-first migráció (pages.markdown, blokkok konvertálása, blokk-UI kivezetés) | F1 (a konverzió az exporter logikáját használja) |
| **F3** | Import-wizard: elemzés/dry-run + megerősítés + `course:validate` parancs | F1 |
| **F4** | Kurzus-szerkesztő képernyő (fa + md editor + hiányosság-jelzők + lecke-sorrend + inline átnevezés + md-feltöltés leckébe) | F2 |
| **F5** | MCP szerver (`laravel/mcp`, HTTP transzport + tokenes auth + toolok) | F2 |
| **F6** | `course-builder` skill frissítése az új formátumra + validátor-használat | F1, F3 |
| **F7** | Publikációs exportok: PDF áttérés Chromium-renderelésre + export-téma; PPTX generálás (képek, videó-link, majd opcionális OOXML videó-beágyazás) | F2 (Markdown-first tartalom) |

F1+F2 a fundamentum: amíg a roundtrip nem veszteségmentes és a tartalom nem egységesen
Markdown, addig a wizard, az MCP és a skill is a mai felemás modellre épülne.

## 9. Nyitott kérdések

1. ~~A `content_blocks` teljes kivezetése rendben van-e?~~ — **Eldöntve: Markdown-first,
   a blokkok kivezetésre kerülnek** (lásd 2. pont).
2. Import-resync meglévő kurzusnál: a tanulói haladás (completions) oldal-törléskor
   elvész — elfogadható, vagy kell „megőrző" mód (törlés helyett archiválás)?
3. ~~MCP: elég-e a lokális stdio?~~ — **Eldöntve: a HTTP transzport az elsődleges,
   élesben is működnie kell** (tokenes auth, lásd 6. pont), mert a tananyagot az éles
   oldalon is AI-val kell tudni javítani.
4. ~~Kell-e oldalhoz kötött kérdés?~~ — **Eldöntve: a kérdések lecke-szinten maradnak,
   és minden lecke után (az utolsó oldala végén) jelennek meg a tanulónak.** Oldalhoz
   kötött kérdés nem kell.
