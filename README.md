# RatingStar für Shopware 6

Bindet das RatingStar-Siegel und die Google-Sterne (Rich Snippets) einer
RatingStar-Filiale (https://ratingstar.de) in einen Shopware-6-Shop ein.

**Status: in Entwicklung** — Funktionsumfang und Roadmap stehen in den
Issues dieses Repos.

## Geplanter Funktionsumfang

- Plugin-Konfiguration (System Config): RatingStar-Profil-Slug + Embed-Key
- Siegel-Widget im vollen Satz (Rundsiegel, Banderole, Profil-Karte,
  Bewertungsleiste, Gesamtnote, Zitat, Karussell, Bewertungswand, Fußleiste)
  als Twig-Funktion `rs_seal()` — inkl. Optik-Optionen wie
  `overrides={theme: 'dark'}` — und als CMS-Element
- Google-Sterne: serverseitiges JSON-LD über den offiziellen
  RatingStar-Endpunkt (`/seal/k/<key>.json`)

## Entwicklung

Dieses Repo enthält **nur das Plugin**. Die lokale Test-Instanz liegt
drumherum: DDEV-Projekt `ratingstar-plugin-shopware`
(https://ratingstar-plugin-shopware.ddev.site, Admin `/admin`), das
Plugin ist dort unter `custom/plugins/RatingStarSeal` ausgecheckt.

```bash
cd ~/Sites/Dev/ratingstar-plugin-shopware
ddev start
ddev exec bin/console plugin:refresh
ddev exec bin/console plugin:install --activate RatingStarSeal
```
