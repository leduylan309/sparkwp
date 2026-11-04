# Spark VN — Design and CMS rules

## Tokens

| Token | Value | Use |
|---|---|---|
| `--purple` | `#5D1C61` | Headings, header solid links, primary brand |
| `--purple-700` | `#4A1750` | Hover / depth |
| `--purple-900` | `#320D37` | Footer, dark surfaces |
| `--purple-tint` | `#F3EAF4` | Soft fills |
| `--gold` / `--accent` | `#AF863A` | Eyebrow, gold buttons, ticks |
| `--ivory` | `#FBF7F0` | Page background |
| `--ivory-2` | `#F5EDE0` | Alternating sections |
| `--paper` | `#FFFFFF` | Cards |
| `--ink` | `#2B2530` | Body text |
| `--ink-soft` | `#6A6470` | Secondary |
| `--line` | `#E9E0D1` | Borders |
| `--container` | `1180px` | `theme.json` wideSize |
| `--section-y` | `clamp(72px, 9vw, 132px)` | Section padding |
| `--r-sm / --r / --r-lg` | `8 / 14 / 22` | Radius |

Headings: Avenir Next LT Pro, fallback Nunito Sans (`--font-serif` in tokens). Body: Frank Ruhl Libre (`--font-sans`).

`theme.json` palette slugs: purple, purple-700, purple-900, gold, ivory, ivory-2, paper, ink, ink-soft, line.

Breakpoints (desktop-first): sm 620 / md 760 / lg 860 / xl 960 / xxl 1180.

## UX / UI (from `html/UX-UI-STANDARDS.md`)

- Content images fixed ratio, `object-fit: cover`, max height 460 desktop / 320 mobile.
- Hero images ≥1600×900, `object-position` ~42% from top.
- Split: align top; copy max 60ch (`.copy` 720px).
- One H2 per section. H1 hero 54–70px/900. Eyebrow gold uppercase.
- Hit targets ≥44px. First tab active. FAQ uses native `details`.
- Content pages end with related + CTA band (exceptions: Home no related; Exclusive / Teachers Corner / Teacher Blogs / Contact / single post omit CTA or related as in HTML).

## SCSS component API

Port lives in `wordpress/wp-content/themes/sparkvn/sass/`. Compile: `npx sass sass/main.scss css/main.css`.

1. A component file only styles its own root class.
2. A parent may only **position** a child (margin, width, order). Never recolor it.
3. Appearance from outside = set the child’s public custom properties.
4. Dark surfaces (`.bg-purple`, `.hero`, `.cta-band`) only set CSS variables.

Public properties: `--heading-color`, `--lead-color`, `--eyebrow-color`, `--btn-bg`, `--chip-bg`, `--container-max`, `--brand-h`, `--split-img-ratio`. See `html/scss/README.md`.

Do not use Underscores Sass. Do not add descendant color rules on `.bg-purple`.

## Pattern vs block vs PHP

| Use | When |
|---|---|
| Core block + block style | Group, Columns, Cover, Details, Buttons, Heading, List |
| Unsynced pattern | Repeated section; copy/image/links change per page |
| Custom block | Tabs, hero video/overlay, CPT query, form, or required `templateLock` |
| PHP template part | Header, footer, breadcrumb |

Do not create a custom block per SCSS file. Do not sync CTA/Related (copy differs). Optional `sparkvn/section` wrapper only if Group breaks layout in QA.

## Editor rules

- Inserter categories: `sparkvn-pages`, `sparkvn-layout`, `sparkvn-cards`, `sparkvn-content`, `sparkvn-cta`, `sparkvn-connect`. Blocks: `sparkvn`.
- Program CPT starts with an unlocked starter (hero, summary, split, tabs, FAQ, related, CTA). Tabs: add / rename / remove from the tab bar. Tides has no Tabs block.
- Forms POST to `POST /wp-json/sparkvn/v1/lead` and store `spark_lead`. No third-party form plugin.
- Marketing copy and images are Gutenberg blocks (`core/heading`, `core/paragraph`, `core/list`, `core/image`, `core/buttons`, `core/details`, `core/table`, `core/group`, `core/columns`, `sparkvn/hero`, `sparkvn/card`). Do not wrap them in `wp:html`.
- Custom HTML is allowed only when core cannot express the DOM (map iframe).
- Seed sideloads theme files into the Media Library. In the editor, Replace an image — do not edit a raw `src`.
- Rewrite HTML `.html` links to WordPress permalinks. Never leave `about.html` in content.

## WordPress standards

- PHP 8.3, `declare(strict_types=1)` on new namespaced files; theme procedural files match Underscores prefix `sparkvn_`.
- Escape output (`esc_html`, `esc_url`, `esc_attr`). Sanitize inbound REST with `sanitize_text_field` / `sanitize_email`.
- `show_in_rest` true on public CPTs. `register_block_type` from `block.json`.
- No ACF. No page-*.php copies of full HTML documents.
- Enqueue compiled `css/main.css` + Google fonts. Keep `style.css` as theme header only.
