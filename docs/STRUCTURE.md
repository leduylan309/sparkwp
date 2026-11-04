# Spark VN — Information architecture

Source of truth: `html/` (34 published pages + `design-system.html`). Theme: `wordpress/wp-content/themes/sparkvn/`.

CMS rule: **repeatable same frame = CPT**. **Unique layout = Page + patterns**. Custom blocks only for Hero, Tabs, Query, Form.

## Sitemap (nav from `html/shell.js`)

```
Home (/)
About (/about/)
  Our Story (/our-story/)
  Joe Evans (/joe-evans/)
  English Support (/english-support/)
  The Spark Method (/the-spark-method/)
  Class Size (/class-size/)
  Community (/community/)
Assessment (/assessment/)
Programs (/programs/)
  Phonics (/programs/phonics/)          CPT spark_program
  ESL (/programs/esl/)                  CPT spark_program
  IB English (/programs/ib-english/)    CPT spark_program
  Tides (/programs/tides/)              CPT spark_program
  IELTS (/programs/ielts/)              CPT spark_program
  Speak Clearly → /contact/             CPT item, no dedicated HTML
  Policy (/policy/)
Exclusive (/exclusive/)
  Teachers Corner (/teachers-corner/)
    Ask a Teacher (/ask-a-teacher/)
    Teacher Blogs (/teacher-blogs/)
  Parent Center (/parent-center/)
    Why Academic English Matters (/why-academic-english-matters/)
    Academic Roadmap (/academic-roadmap/)
    Results Dashboard (/results-dashboard/)
    Parent Success Roadmap (/parent-success-roadmap/)
  Resource Library (/resource-library/)
    International School Support Guide (/international-school-support-guide/)
    IB School Guide (/ib-school-guide/)
    Parent's Guide → Ask a Teacher
    Reading Age Growth Center (/reading-age-growth-center/)
  Student Portal (/student-portal/)
  Spark Store (/spark-store/)
Contact (/contact/)
Blog (/blog/)                           post archive
  Single post (/blog/{slug}/)
```

`design-system.html` is not published.

## CMS types

| Type | Slug | HTML source | Why |
|---|---|---|---|
| Page | `page` | hubs, guides, forms, legal | Unique section composition |
| Program | `spark_program` | phonics, esl, ib-english, tides, ielts-program | Shared template |
| Post | `post` | blog.html, blog-post.html, teacher-blogs.html | Native posts + category chips |
| Prize | `spark_prize` | spark-store.html | Title, image, points, note, `prize_group` |
| Resource | `spark_resource` | resource-library.html | Title, kind, excerpt, file, best-for |
| Lead | `spark_lead` | forms | REST submissions, not public |

Front page = Page “Home”. Posts page = empty Page “Blog”.

## Pattern catalog

PHP parts: `header`, `footer`, `crumb-bar` (not patterns). Solid header (`data-solid-header`): contact, policy, joe-evans, single post.

**Custom block `sparkvn/hero`:** `home` (video), `page`, `page-center` (+ `short`).

**Unsynced section patterns (`sparkvn/{name}`), grouped in the inserter:**

- Pages: page-home, page-hub (About), page-guide (Class Size), page-program (Phonics), page-form (Assessment)
- Layout: split, split-rev, split-tall, copy-section, sec-head, img-pair, photo-strip
- Cards: hub-grid, info-grid, advantage-cards, program-feature, program-detail, pathway, testimonials, team-grid, q-cards
- Content: summary, two-lists, feat-list, faq, notice, flag-list, table, cefr, verdict, pillars, scorecard, steps, roadmap, chips
- CTAs: cta-band, related, inline-cta, promo-band
- Forms: form-split, contact-list, map-card, portal-card, store-anchor-nav

**Custom blocks:** Hero, Tabs (`default` / `opt-tabs` / `school-tabs`), query-programs, query-posts, query-prizes, query-resources, Form (assessment / contact / resource / ask-teacher).

CTA and Related are **unsynced** (copy and links differ per page).

## Page checklist (P pattern · C custom · PHP · CPT)

Full section order matches `html/*.html`. Crumb is PHP except Home.

### Home — `index.html` — Page `/`

1. C hero-home 2. P split 3. P sec-head + copy 4. P promo-band 5. P advantage-cards 6. P split-rev + two-lists 7. P pathway 8. P program-detail + img-pair 9. P testimonials 10. P faq 11. P cta-band. No crumb, no related.

### Hubs

- **about.html** `/about/` — C hero-page · P hub-grid ×7 · P related · P cta-band
- **access.html** `/exclusive/` — C hero-page-center · P sec-head + hub-grid row ×5 · P img-pair · P related. No CTA.
- **parent-center.html** `/parent-center/` — C hero-page · P copy + hub-grid ×4 · P related · P cta-band
- **teachers-corner.html** `/teachers-corner/` — C hero-page-center · P sec-head + hub-grid ×2 + img-pair · P related. No CTA.
- **programs.html** `/programs/` — C hero-page · P copy + C query-programs (6 `.pf`, Speak Clearly → Contact) · P related · P cta-band

### Programs — CPT `/programs/{slug}/`

- **phonics** — C hero · P summary · P split · P copy-section · C tabs (L1–5) · P split-rev · P two-lists · P faq · P related · P cta-band
- **esl** — C hero · P summary · P split-tall · P copy-section ×2 · P feat-list · C tabs (14-level) · P two-lists · P faq · P related · P cta-band
- **ib-english** — C hero short · P summary · P split-tall · P copy-section + feat-list · C tabs (L6–13) · P split-rev · P two-lists · P faq · P related · P cta-band
- **ielts** — C hero · P summary · P split-tall · P copy-section + feat-list · P info-grid · C tabs · P two-lists · P feat-list · P faq · P related · P cta-band. No timeline.
- **tides** — C hero · P summary · P split · P feat-list · P split · P feat-list · P copy-section · P two-lists · P faq · P related · P cta-band. No tabs.

### Forms, store, blog, legal

- **assessment.html** — C hero · P form-split + C form(assessment) + P info-grid · P split · P steps · P inline-cta · P faq cols-2 · P related · P cta-band
- **contact.html** — solid header · C hero · P contact-list + C form(contact) + P map-card · P related. No CTA band.
- **policy.html** — solid header, no hero · P copy-section · P related · P cta-band
- **student-portal.html** — C hero-page-center · P portal-card + info-grid · P related · P cta-band
- **spark-store.html** — C hero-page-center · P sec-head + store-anchor-nav + C query-prizes · P related · P cta-band
- **resource-library.html** — C hero · P split · C query-resources · P form-split + C form(resource) + P info-grid · P hub-grid ×4 · P related · P cta-band
- **blog.html** — post archive · C hero-page-center short · C query-posts · P cta-band. No related.
- **blog-post.html** — single post · solid header · PHP article-head + prose · C query-posts More Posts · P related. No hero, no CTA.
- **teacher-blogs.html** — C hero · P copy + two-lists · C query-posts (teachers) · P feat-list · P split · P related. No CTA.

### Guides

- **our-story.html** — C hero · P summary · P split · P copy-section · P feat-list · P team-grid · P advantage-cards · P copy-section · P faq · P related · P cta-band
- **joe-evans.html** — solid header, no hero · P summary · P split-tall · P split-rev · P pillars · P copy-section · P faq · P related · P cta-band
- **english-support.html** — C hero · P summary · P notice · P two-lists · C tabs opt-tabs · P flag-list · P split · P scorecard · P faq · P related-reading · P related · P cta-band
- **the-spark-method.html** — C hero · P summary · P split · C tabs S.P.A.R.K. + P notice + P photo-strip · P related · P cta-band
- **class-size.html** — C hero · P summary · P copy-section ×4 (with img-pair/photo-strip) · P faq · P related · P cta-band
- **community.html** — C hero · P summary · P split · P sec-head · P split-tall · P faq · P related · P cta-band
- **why-academic-english-matters.html** — C hero · P summary · P split-tall · P verdict · P split-rev · P info-grid · P steps · P split · P scorecard · P faq · P related · P cta-band
- **academic-roadmap.html** — C hero · P split + roadmap · C tabs · P split · P related · P cta-band. No summary, no FAQ.
- **results-dashboard.html** — C hero · P summary · P split-tall · C tabs · P info-grid · P inline-cta · P faq · P related · P cta-band
- **parent-success-roadmap.html** — C hero · P copy + roadmap · P info-grid cols-4 · P split + scorecard · P inline-cta · P related · P cta-band
- **ib-school-guide.html** — C hero · P summary · P split-tall · P verdict · P cefr · P table · P split + numbered faq · P split-rev · P faq · P related · P cta-band
- **international-school-support-guide.html** — C hero · P split · C tabs school-tabs · P steps · P split-rev · P faq · P related · P cta-band. No summary.
- **reading-age-growth-center.html** — C hero · P summary · P split-tall · P split + q-cards · P steps · P info-grid · P split-rev · P faq · P related · P cta-band
- **ask-a-teacher.html** — C hero · P split + q-lines · P form-split + C form(ask-teacher) · P faq cols-2 · P related · P cta-band. Form before FAQ.

## URLs

Pretty permalinks `/%postname%/`. Program rewrite base `programs`. Blog ` /blog/%postname%/`. Store page `/spark-store/`. Exclusive hub slug `exclusive` (HTML `access.html`).
