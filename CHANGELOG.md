# 1.0.0

- Shopping experience element and block "RatingStar seal" with all nine variants, branch or group per element, position of the profile card, every per-embed option and a field for additional data attributes
- Group seals: setting "Group" in the plugin configuration (per sales channel) and per embed; `data-group` instead of `data-slug`, static group images, group key for the stars for Google
- Static image mode for round seal, banner seal and profile card (linked SVG, no JavaScript), optionally an award badge
- "Check connection" button in the plugin configuration: looks up branch, group and API key on the RatingStar server without blocking the save
- seal.js is loaded exactly once per page even when header and footer are cached as separate fragments
- Stars for Google: one LocalBusiness block per page; a page can stand for another branch or group (API key on the element or `rsSealApiKey` in the page template)
- Saving the plugin configuration or `bin/console ratingstar:seal:cache:clear` now also refreshes the cached pages that show a seal
- Requests to RatingStar are limited to 5 seconds in total, failures are remembered for 15 minutes, API keys are masked in log output
- Twig function `rs_seal()`: new arguments `group`, `attributes`, `mode` and `award`
- Notes on domain approval and privacy in the plugin configuration
- Service and route definitions moved from XML to PHP

# 0.1.0

- Plugin configuration: address of the RatingStar server, profile slug, API key
- Twig function `rs_seal()` for themes
- Server-side LocalBusiness JSON-LD (stars for Google) in the page head
- Console command `ratingstar:seal:cache:clear`
