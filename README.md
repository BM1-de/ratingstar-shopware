# RatingStar für Shopware 6

[RatingStar](https://ratingstar.de) ist das Bewertungstool für Unternehmen: Kundenbewertungen sammeln, Bewertungen aus Google und anderen Portalen bündeln und alles mit Siegeln auf der eigenen Website zeigen. Dieses Plugin bringt die Siegel und Widgets in Ihren Shopware-Shop und gibt die Sterne für Google serverseitig aus.

## Funktionen

- **Siegel und Widgets in neun Varianten** als Erlebniswelt-Element und als Twig-Funktion für Themes: Rundsiegel, Banderole, Profil-Karte, Bewertungsleiste, Gesamtnote, Zitat, Karussell, Bewertungswand und Fußleiste.
- **Je Einbindung einstellbar:** Variante, Filiale oder Gruppe, Position der Profil-Karte und alle Darstellungsoptionen des Code-Generators von RatingStar.
- **Gruppen-Siegel** über alle Filialen einer Gruppe.
- **Sterne für Google:** ein Block mit strukturierten Daten (schema.org) im Seitenkopf, vom Shop-Server abgeholt und zwischengespeichert.
- **Statisches Bild** ohne JavaScript für Rundsiegel, Banderole und Profil-Karte.
- **Verbindung prüfen:** Die Plugin-Konfiguration bestätigt Filiale, Gruppe und API-Key am RatingStar-Server.
- Alle Einstellungen gelten für alle Verkaufskanäle und lassen sich je Verkaufskanal überschreiben.

## Voraussetzungen

- Shopware 6.7
- Ein RatingStar-Konto. Rundsiegel und Banderole laufen in jedem Tarif; die Live-Widgets von der Profil-Karte bis zur Fußleiste und die Gesamtbewertung in den Sternen für Google gibt es ab dem 4-Star-Tarif. Darunter zeigt RatingStar an Stelle eines Live-Widgets von selbst die Banderole.

## Installation

Das Plugin gibt es auf zwei Wegen: als ZIP unter [Releases](https://github.com/BM1-de/ratingstar-shopware/releases) oder per Composer als Paket [`ratingstar/shopware-seal`](https://packagist.org/packages/ratingstar/shopware-seal).

### Als ZIP

1. Laden Sie das ZIP in der Administration unter **Erweiterungen → Meine Erweiterungen → Erweiterung hochladen** hoch.
2. Installieren und aktivieren Sie **RatingStar Siegel & Google-Sterne**.
3. Öffnen Sie über das Menü mit den drei Punkten **Konfigurieren**.

Auf der Kommandozeile:

```bash
bin/console plugin:zip-import RatingStarSeal-<version>.zip
bin/console plugin:refresh
bin/console plugin:install --activate RatingStarSeal
bin/console cache:clear
```

Für ein Update laden Sie das neue ZIP genauso hoch und klicken in der Liste der Erweiterungen auf **Aktualisieren**.

### Per Composer

Für Shops, deren Abhängigkeiten Composer verwaltet:

```bash
composer require ratingstar/shopware-seal
bin/console plugin:refresh
bin/console plugin:install --activate RatingStarSeal
bin/console cache:clear
```

Updates holen Sie mit `composer update ratingstar/shopware-seal` und `bin/console plugin:update RatingStarSeal`.

## Einrichtung

In der Plugin-Konfiguration tragen Sie ein:

| Feld | Bedeutung |
|---|---|
| **Filiale (Profil-Slug)** | Der Teil nach `/t/` in der Adresse Ihres RatingStar-Profils, zum Beispiel `cafe-sonne`. |
| **Gruppe (Gruppen-Slug)** | Nur für das Siegel einer ganzen Gruppe: der Teil nach `/g/` in der Adresse des Gruppenprofils. Ist das Feld gefüllt, zeigen Siegel ohne eigene Angabe die Gruppe statt der Filiale. |
| **API-Key** | Schlüssel der Filiale oder der Gruppe im Format `rs_live_` und 40 Zeichen. Sie finden ihn in RatingStar auf der Seite „Google-Sterne“. Der Shop-Server holt damit die Daten für die Sterne in Google ab. |
| **Adresse des RatingStar-Servers** | Standard `https://ratingstar.de`. Nur für eine Test- oder eigene Installation ändern. |
| **Sterne für Google ausgeben** | Schaltet den Block mit den strukturierten Daten im Seitenkopf ein oder aus. |

**Verbindung prüfen** schlägt Filiale, Gruppe und API-Key am RatingStar-Server nach und nennt den gefundenen Namen. Die Prüfung nutzt die Werte aus dem Formular, auch ungespeicherte, und blockiert nichts: Speichern können Sie unabhängig vom Ergebnis.

Wird der API-Key in RatingStar neu erzeugt, verliert der alte sofort seine Gültigkeit. Tragen Sie dann den neuen hier ein.

## Siegel einbinden

### Erlebniswelt-Element

Im Layout-Editor der Erlebniswelten finden Sie den Block **RatingStar-Siegel** in der Kategorie „Commerce“. Das Element lässt sich auch in den Platz eines anderen Blocks tauschen. In den Einstellungen des Elements wählen Sie:

- die **Variante**,
- bei Rundsiegel, Banderole und Profil-Karte die **Ausgabe** als Live-Siegel oder als statisches Bild,
- bei der Profil-Karte die **Position**: an dieser Stelle oder schwebend in einer der vier Bildschirmecken,
- eine eigene **Filiale** oder **Gruppe**, wenn dieses Siegel nicht das Profil aus der Plugin-Konfiguration zeigen soll,
- die **Darstellung der Variante**, zum Beispiel Größe, Farben, Anzahl der Bewertungen oder die helle und dunkle Fassung,
- **zusätzliche data-Attribute**, zum Beispiel aus dem Code-Generator in RatingStar kopiert.

Jede Darstellungsoption steht zunächst auf **Standard (Portal)**. Das Plugin gibt dann nichts vor, und es gilt, was Sie in RatingStar unter „Siegel & Widgets“ eingestellt haben. Erst eine Auswahl im Element überschreibt die Einstellung für genau dieses Siegel. Dieselbe Variante kann so mehrfach mit unterschiedlicher Darstellung im Shop stehen.

### Twig-Funktion für Themes

```twig
{# Profil aus der Plugin-Konfiguration, Variante Banderole #}
{{ rs_seal() }}

{# Variante, Position und andere Filiale #}
{{ rs_seal(variant='profile-card', position='bottom-left', slug='andere-filiale') }}

{# Gruppen-Siegel mit Darstellungsoptionen #}
{{ rs_seal(variant='hero', group='meine-gruppe', overrides={theme: 'dark', heroShowBranches: false}) }}

{# Attribute aus dem Code-Generator übernehmen #}
{{ rs_seal(variant='carousel', attributes='data-car-count="4" data-car-width="s"') }}

{# Statisches Bild ohne JavaScript #}
{{ rs_seal(variant='seal-circle', mode='static') }}
```

| Argument | Bedeutung |
|---|---|
| `variant` | Variante, siehe unten. Ohne Angabe die Banderole. |
| `slug` | Filiale dieses Siegels. Ohne Angabe gilt die Plugin-Konfiguration. |
| `group` | Gruppe dieses Siegels. Hat Vorrang vor `slug`. |
| `position` | Nur Profil-Karte: `inline`, `bottom-right`, `bottom-left`, `top-right`, `top-left`. |
| `overrides` | Darstellungsoptionen der Variante als Schlüssel-Wert-Paare, siehe unten. |
| `attributes` | Zusätzliche `data-`Attribute als Zeichenkette oder als Schlüssel-Wert-Paare. Sie haben Vorrang vor `overrides`. |
| `mode` | `live` (Standard) oder `static`. |
| `award` | Nur mit `mode='static'`: Auszeichnung der Filiale im Format Typ, Bindestrich, Stufe. |
| `noRichSnippet` | `true` unterdrückt die strukturierten Daten durch das Siegel. Ohne Angabe entscheidet das Plugin selbst. |

Die Funktion lässt sich an jeder Stelle eines Storefront-Templates aufrufen, auch im Kopf- und Fußbereich.

### Varianten

| Schlüssel | Name | Art |
|---|---|---|
| `seal-circle` | Rundsiegel | statisches Siegel, jeder Tarif |
| `seal-circle-banner` | Banderole | statisches Siegel, jeder Tarif |
| `profile-card` | Profil-Karte | Live-Widget, an Ort und Stelle oder schwebend |
| `bar` | Bewertungsleiste | Live-Widget |
| `hero` | Gesamtnote | Live-Widget |
| `quote` | Zitat | Live-Widget |
| `carousel` | Karussell | Live-Widget |
| `wall` | Bewertungswand | Live-Widget |
| `footer-bar` | Fußleiste | Live-Widget |

### Darstellungsoptionen

Die Schlüssel für `overrides` heißen wie im Code-Generator von RatingStar. Als Attribut wird aus `pcColor` zum Beispiel `data-pc-color`. Ja-Nein-Werte schreiben Sie als `true` und `false` oder `1` und `0`.

| Variante | Optionen |
|---|---|
| Rundsiegel, Banderole | `size` (120–320) |
| Profil-Karte | `pcWidth` (100–215), `pcShowCount`, `pcColor` (`gold`, `silber`, `weiss`), `pcMobile` (`compact`, `hide`, `full`), `pcClick` (`overlay`, `profile`) |
| Bewertungsleiste | `barShowCount`, `barShowVerified`, `theme` |
| Gesamtnote | `heroShowCount`, `heroShowVerified`, `heroShowBranches` (nur Gruppen), `theme` |
| Zitat | `quotePick` (`best`, `newest`, `rotate`, `manual`), `quoteWidth` (`s`, `m`, `l`, `full`), `reviewsOnlyNamed`, `reviewsOnlyWithComment`, `theme` |
| Karussell | `carCount` (3–20), `carRotate` (0–30), `carMin` (1–5), `carWidth` (`s`, `m`, `l`, `full`), `reviewsOnlyNamed`, `reviewsOnlyWithComment`, `theme` |
| Bewertungswand | `wallCols` (2–4), `wallTotal` (2–50), `wallMin` (1–5), `wallSort` (`newest`, `best`, `mix`), `wallWidth` (`m`, `l`, `xl`, `full`), `reviewsOnlyNamed`, `reviewsOnlyWithComment`, `theme` |
| Fußleiste | `footerBarPosition` (`fixed`, `inline`), `footerBarBg`, `footerBarText`, `footerBarLinkColor`, `footerBarLinkHover` (Farben als `#rrggbb`) |

`theme` kennt `light` und `dark`. Optionen einer anderen Variante und Werte in falscher Form lässt das Plugin weg; Zahlen außerhalb des Bereichs und unbekannte Werte gleicht RatingStar selbst an.

Das Feld für zusätzliche Attribute nimmt nur Namen an, die mit `data-` beginnen. Profil, Gruppe, Variante und Position haben eigene Felder und lassen sich dort nicht überschreiben.

## Gruppen-Siegel

Alle neun Varianten gibt es auch für eine Gruppe über alle ihre aktiven Filialen. Tragen Sie dazu den Gruppen-Slug ein, in der Plugin-Konfiguration für den ganzen Shop oder am einzelnen Siegel. Ein Siegel zeigt immer entweder eine Filiale oder eine Gruppe; ist beides angegeben, gewinnt die Gruppe.

Für die Sterne für Google gehört dann der API-Key der Gruppe in die Plugin-Konfiguration.

## Sterne für Google

Ist ein gültiger API-Key hinterlegt, holt der Shop-Server die strukturierten Daten Ihrer Bewertungen bei RatingStar ab und gibt sie als einen Block im Seitenkopf aus (schema.org). Je Seite gibt es genau einen Block; die Siegel verzichten dann von selbst auf eine eigene Ausgabe.

Eine Gesamtbewertung enthält der Block ab dem 4-Star-Tarif und sobald es Bewertungen gibt. Es zählen dieselben Bewertungen wie in Ihrem Siegel. Ob Google Sterne anzeigt, entscheidet Google.

Steht eine Seite für eine andere Filiale oder Gruppe als der übrige Shop, zum Beispiel eine Standortseite, geben Sie ihr deren Schlüssel mit:

- im Erlebniswelt-Element im Feld **API-Key für diese Seite**, oder
- im Seiten-Template eines Themes auf oberster Ebene:

  ```twig
  {% sw_extends '@Storefront/storefront/page/content/index.html.twig' %}
  {% set rsSealApiKey = 'rs_live_…' %}
  ```

Die Seite erhält dann die Daten dieses Schlüssels statt der des Shops. Es bleibt bei einem Block je Seite.

## Statisches Bild

Rundsiegel, Banderole und Profil-Karte gibt es auch als verlinktes Bild ohne JavaScript, zum Beispiel für Seiten, auf denen kein Skript laufen soll. Das Bild kommt fertig vom RatingStar-Server und verlinkt auf Ihr Profil. Für die übrigen Varianten gibt es kein statisches Bild; dort bleibt es beim Live-Widget.

## Zwischenspeicher

- Die Daten für die Sterne für Google speichert das Plugin 6 Stunden zwischen. Einen fehlgeschlagenen Abruf versucht es nach 15 Minuten erneut. Ein Abruf dauert höchstens 5 Sekunden; antwortet RatingStar nicht, erscheint die Seite ohne den Block.
- Nimmt der Cache des Shops keine Einträge an (Ordner `var/cache` ohne Schreibrecht, Redis nicht erreichbar oder schreibgeschützt), erscheint die Seite ohne den Block und das Plugin fragt RatingStar nicht ab. Im Log des Shops steht dann ein Fehler, der mit „RatingStar: the cache“ beginnt.
- Seiten mit Siegel liegen im HTTP-Cache von Shopware.
- Beim Speichern der Plugin-Konfiguration leert das Plugin beides von selbst.
- Von Hand, zum Beispiel nach einem neu erzeugten API-Key:

  ```bash
  bin/console ratingstar:seal:cache:clear
  ```

## Domain freigeben

Für die Live-Siegel: Tragen Sie die Domain Ihres Shops in RatingStar unter **Siegel & Widgets → Domains & Statistik** im Abschnitt „Freigegebene Domains“ ein und bestätigen Sie sie mit dem DNS-Eintrag `TXT _ratingstar.ihre-domain.de`.

Sobald dort erstmals eine Domain bestätigt ist, liefert RatingStar die Siegel-Daten nur noch an bestätigte Domains. Auf anderen Domains bleibt das Siegel leer. Das bloße Eintragen einer Domain ändert noch nichts; erst die Bestätigung per DNS schaltet den Schutz ein.

Für ein Gruppen-Siegel gilt die eigene Liste im Backend der Gruppe, ebenfalls unter **Siegel & Widgets → Domains & Statistik**. Die Listen der Filialen gelten nicht für das Gruppen-Siegel und umgekehrt.

Die Sterne für Google holt der Shop-Server mit dem API-Key ab. Das ist von der Freigabe nicht betroffen und in jedem Tarif möglich.

## Datenschutz und Einwilligung

- **Live-Siegel:** Der Browser Ihrer Besucher lädt `seal.js`, `seal.css`, die Bewertungsdaten und ein Zählpixel vom RatingStar-Server (Standard: `ratingstar.de`) und baut dafür eine Verbindung zu diesem Server auf. Nennen Sie ihn in Ihrer Datenschutzerklärung und berücksichtigen Sie ihn in Ihrem Consent-Tool.
- **Statisches Bild:** Der Browser lädt eine Bilddatei vom RatingStar-Server.
- **Sterne für Google:** Die Daten holt Ihr Shop-Server ab. Dafür entsteht keine Verbindung vom Browser zu RatingStar.

Das Plugin trägt nichts in den Cookie-Consent von Shopware ein und macht das Laden der Siegel nicht von einer Einwilligung abhängig. Soll ein Siegel erst nach Einwilligung erscheinen, regeln Sie das über Ihr Consent-Tool oder im Theme.

Die Datenschutzerklärung von RatingStar: <https://ratingstar.de/datenschutz>

## Wie das Plugin seal.js lädt

Jedes Live-Siegel besteht aus dem Element `<div class="rs-seal" …>` und einem kurzen Skript, das `<script src="…/seal.js" async>` in die Seite einfügt, sofern es dort noch nicht steht. So wird `seal.js` je Seite genau einmal geladen, gleich wie viele Siegel sie enthält und auch dann, wenn Shopware Kopf- und Fußbereich getrennt vom Inhalt zwischenspeichert. Ohne Siegel auf der Seite wird nichts von RatingStar geladen.

Für Shops mit eigener Content-Security-Policy: Das eingefügte Skript ist ein Inline-Skript, wie Shopware sie im Storefront selbst verwendet. Der RatingStar-Server muss für Skripte, Stylesheets, Bilder und Abrufe erlaubt sein.

## Entwicklung

Das Repository enthält das Plugin mit den gebauten Dateien für die Administration unter `src/Resources/public/administration`. Nach Änderungen unter `src/Resources/app/administration/src` bauen Sie sie in einer Shopware-6.7-Installation mit installiertem und aktivem Plugin neu:

```bash
SHOPWARE_ADMIN_BUILD_ONLY_EXTENSIONS=1 bin/build-administration.sh
```

Das installierbare ZIP entsteht im Plugin-Verzeichnis:

```bash
bin/build-zip.sh
```

Das Skript legt `dist/RatingStarSeal-<version>.zip` an: das Plugin mit den gebauten Dateien, ohne Repository und Entwicklungsdateien.

Die Listen der Varianten und Optionen stehen an zwei Stellen und werden gemeinsam gepflegt: `src/Service/VariantRegistry.php` für den Storefront und `src/Resources/app/administration/src/constant/rs-seal.constant.js` für die Administration.

## Hilfe

Fragen und Rückmeldungen: <https://ratingstar.de/support>

## Lizenz

GNU General Public License, Version 2 oder später (GPL-2.0-or-later), siehe `LICENSE`.
