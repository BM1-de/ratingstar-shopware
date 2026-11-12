# 1.1.0

- Sterne für Google: Das Plugin fragt bei RatingStar den schema.org-Typ ab, der in Ihrem RatingStar-Konto eingestellt ist (Seite „Google-Sterne“: Product oder LocalBusiness), und nimmt beide an; bisher nur LocalBusiness
- „Verbindung prüfen“ nimmt ebenfalls beide Typen an
- Die Hilfetexte nennen keinen festen Typ mehr und sagen, dass der Block dieselben Bewertungen zählt wie Ihr Siegel

# 1.0.0

- Erlebniswelt-Element und -Block „RatingStar-Siegel“ mit allen neun Varianten, Filiale oder Gruppe je Element, Position der Profil-Karte, allen Einzeloptionen und einem Feld für zusätzliche data-Attribute
- Gruppen-Siegel: Einstellung „Gruppe“ in der Plugin-Konfiguration (je Verkaufskanal) und je Einbindung; `data-group` statt `data-slug`, statische Gruppenbilder, Gruppen-Key für die Sterne für Google
- Statisches Bild für Rundsiegel, Banderole und Profil-Karte (verlinktes SVG, ohne JavaScript), wahlweise eine Auszeichnung
- Schaltfläche „Verbindung prüfen“ in der Plugin-Konfiguration: prüft Filiale, Gruppe und API-Key am RatingStar-Server, ohne das Speichern zu blockieren
- seal.js wird je Seite genau einmal geladen, auch wenn Kopf- und Fußbereich als eigene Fragmente zwischengespeichert sind
- Sterne für Google: ein LocalBusiness-Block je Seite; eine Seite kann für eine andere Filiale oder Gruppe stehen (API-Key am Element oder `rsSealApiKey` im Seiten-Template)
- Speichern der Plugin-Konfiguration oder `bin/console ratingstar:seal:cache:clear` erneuert jetzt auch die zwischengespeicherten Seiten mit Siegel
- Abrufe bei RatingStar sind auf insgesamt 5 Sekunden begrenzt, Fehler werden 15 Minuten gemerkt, API-Keys erscheinen in Logzeilen nur gekürzt
- Twig-Funktion `rs_seal()`: neue Argumente `group`, `attributes`, `mode` und `award`
- Hinweise zu Domain-Freigabe und Datenschutz in der Plugin-Konfiguration
- Service- und Routen-Definitionen von XML auf PHP umgestellt
- Beim Deinstallieren ohne Datenerhalt werden die Siegel-Blöcke und -Elemente aus allen Erlebniswelten entfernt

# 0.1.0

- Plugin-Konfiguration: Adresse des RatingStar-Servers, Profil-Slug, API-Key
- Twig-Funktion `rs_seal()` für Themes
- Serverseitiges LocalBusiness-JSON-LD (Sterne für Google) im Seitenkopf
- Konsolenbefehl `ratingstar:seal:cache:clear`
