# Spark VN

Classic WordPress theme for Spark VN, built from [Underscores (`_s`)](https://underscores.me/).

Underscores is archived (September 2025) now that Automattic focuses on block themes. This project still uses `_s` as the PHP template boilerplate, then adds `theme.json` so the block editor matches the front end.

## Rename map (from `_s`)

| Underscores | Spark VN |
|---|---|
| Text domain `'\_s'` | `'sparkvn'` |
| Functions `_s_` | `sparkvn_` |
| Handles `_s-` | `sparkvn-` |
| Constants `_S_` | `SPARKVN_` |
| Package `_s` | `Spark_VN` |

## Structure

- `style.css` / `sass/` — front-end CSS (Sass source + compiled)
- `theme.json` — block editor palette, typography, content width
- `header.php`, `footer.php`, `index.php`, `single.php`, `page.php`, `archive.php`, `search.php`, `404.php`
- `template-parts/` — loop partials
- `inc/` — setup helpers, Customizer, WooCommerce, Jetpack
- `js/navigation.js` — mobile menu toggle

## Dev commands

```sh
composer install
npm install
npm run watch
composer lint:php
```
