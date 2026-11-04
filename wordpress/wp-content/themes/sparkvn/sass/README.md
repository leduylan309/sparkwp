# Spark site-v2 — SCSS architecture

Build: `npx sass scss/main.scss css/main.css` (pages load only `css/main.css`).

```
scss/
  main.scss            entry — import order = cascade order
  abstracts/_mixins    $breakpoints + @include down(sm|md|lg|xl|xxl|<px>)
  base/                tokens (:root custom properties), reset, typography, animations
  layout/              container, section + surfaces (.bg-purple, .bg-ivory2), composition
  components/          one file per reusable component (used on ≥2 pages or in shell.js)
  sections/            page-specific blocks (used on one page) — _home, _spark-store …
  utilities/           .reveal
```

## Component rules
1. A component file only contains selectors that start with its own root class (`.faq-item …`, `.btn-ghost:hover`). It never styles another component.
2. A parent may only **position** a child component (margin, width, alignment, order). Never colors, fonts, padding or borders.
3. To change a child's **appearance** from outside, set the child's public custom properties on the parent. Custom properties inherit, so the component looks right anywhere, with no class dependency.
4. Dependencies are only `base/_tokens` (colors, fonts, radius, shadow) + `base/_reset`.
5. Dark surfaces (`.bg-purple`, `.hero`, `.cta-band`) only set variables. They contain no descendant selectors.

## Public API (custom properties)
| Component | Variables |
|---|---|
| Headings (h1–h4) | `--heading-color` (h1/h2), `--subheading-color` (h3/h4) |
| `.lead` | `--lead-color` `--lead-size` `--lead-lh` `--lead-weight` |
| `.eyebrow` | `--eyebrow-color` |
| `.spark` icon | `--spark-color` `--spark-size` |
| `.chip` | `--chip-bg` `--chip-fg` `--chip-border` `--chip-shadow` `--chip-size` `--chip-weight` `--chip-blur` `--chip-spark-color` · modifier `.chip--filter` (+ `.active`) |
| `.trust-chips` | `--trust-chips-justify` |
| `.btn-primary` | `--btn-bg` `--btn-fg` `--btn-border` `--btn-hover-bg` |
| `.btn-ghost` | `--btn-ghost-fg` `--btn-ghost-border` `--btn-ghost-hover-fg` `--btn-ghost-hover-bg` `--btn-ghost-hover-border` |
| `.container` | `--container-max` `--container-position` `--container-z` |
| `.brand` | `--brand-h` `--logo-white` `--logo-purple` |
| `.split-media` | `--split-media-position` `--split-img-ratio` `--split-img-maxh` `--split-img-maxh-sm` |

Example: a ghost button on a dark band, with no CSS changes:
```html
<div style="--btn-ghost-fg:#fff;--btn-ghost-border:rgba(255,255,255,.45)"><a class="btn btn-ghost">Learn more</a></div>
```

## Audit (refactor)
- 4 legacy files (spark/blog/site/v2.css, ~100 KB, 1,100 rules with layered overrides) were merged into 67 partials. Each selector is now defined once.
- 231 unused selectors were removed (old home sections, unused blog-article blocks, `.hero--form`, `.callout`, `.author-box`, …).
- Computed styles were compared old vs new on all 34 pages at 1280 / 820 / 390 px. They match, with one intentional change: chips now use `line-height: 1.3` everywhere.
