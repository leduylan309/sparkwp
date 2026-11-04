#!/usr/bin/env python3
"""Generate Gutenberg patterns and WP seed JSON from html/ pages."""
from __future__ import annotations

import html as html_lib
import json
import re
from pathlib import Path

ROOT = Path("/Users/lanle/WORK/09_SPARK_V2")
HTML = ROOT / "html"
THEME = ROOT / "wordpress/wp-content/themes/sparkvn"
PATTERNS = THEME / "patterns"
SEED = THEME / "inc/seed-content.json"
PATTERNS.mkdir(parents=True, exist_ok=True)

VOID = {
    "img", "br", "hr", "input", "source", "meta", "link", "area",
    "col", "embed", "wbr", "track",
}

HTML_TO_PATH = {
    "index.html": "/",
    "about.html": "/about/",
    "our-story.html": "/our-story/",
    "joe-evans.html": "/joe-evans/",
    "english-support.html": "/english-support/",
    "the-spark-method.html": "/the-spark-method/",
    "class-size.html": "/class-size/",
    "community.html": "/community/",
    "assessment.html": "/assessment/",
    "programs.html": "/programs/",
    "phonics.html": "/programs/phonics/",
    "esl.html": "/programs/esl/",
    "ib-english.html": "/programs/ib-english/",
    "tides.html": "/programs/tides/",
    "ielts-program.html": "/programs/ielts/",
    "policy.html": "/policy/",
    "access.html": "/exclusive/",
    "teachers-corner.html": "/teachers-corner/",
    "ask-a-teacher.html": "/ask-a-teacher/",
    "teacher-blogs.html": "/teacher-blogs/",
    "parent-center.html": "/parent-center/",
    "why-academic-english-matters.html": "/why-academic-english-matters/",
    "academic-roadmap.html": "/academic-roadmap/",
    "results-dashboard.html": "/results-dashboard/",
    "parent-success-roadmap.html": "/parent-success-roadmap/",
    "resource-library.html": "/resource-library/",
    "international-school-support-guide.html": "/international-school-support-guide/",
    "ib-school-guide.html": "/ib-school-guide/",
    "reading-age-growth-center.html": "/reading-age-growth-center/",
    "student-portal.html": "/student-portal/",
    "spark-store.html": "/spark-store/",
    "contact.html": "/contact/",
    "blog.html": "/blog/",
    "blog-post.html": "/blog/",
}

FORM_BY_SLUG = {
    "assessment": "assessment",
    "contact": "contact",
    "resource-library": "resource",
    "ask-a-teacher": "ask-teacher",
}

CTX: dict = {"slug": "", "store": False, "res": False, "programs": False, "teacher_posts": False}


def esc_attr(s: str) -> str:
    return html_lib.escape(s or "", quote=True)


def unescape(s: str) -> str:
    return html_lib.unescape(s or "")


def strip_tags(s: str) -> str:
    s = re.sub(r"<svg[\s\S]*?</svg>", "", s, flags=re.I)
    s = re.sub(r"<[^>]+>", "", s)
    return unescape(re.sub(r"\s+", " ", s)).strip()


def rewrite_href(href: str) -> str:
    if not href:
        return ""
    href = href.strip()
    if href in HTML_TO_PATH:
        return HTML_TO_PATH[href]
    base = href.split("#")[0].split("?")[0]
    name = base.rsplit("/", 1)[-1]
    if name in HTML_TO_PATH:
        rest = href[len(base):] if href.startswith(base) else ""
        return HTML_TO_PATH[name] + rest.lstrip("/")
    return href


def rewrite_links_in_html(raw: str) -> str:
    def repl(m):
        return f'{m.group(1)}{rewrite_href(m.group(2))}{m.group(3)}'

    return re.sub(r'(href=["\'])([^"\']+)(["\'])', repl, raw)


def clean_inline(raw: str) -> str:
    raw = re.sub(r"<svg[\s\S]*?</svg>", "", raw, flags=re.I)
    raw = re.sub(r"</?(?:span)(?:\s[^>]*)?>", "", raw, flags=re.I)
    raw = rewrite_links_in_html(raw)
    return raw.strip()


def parse_attrs(attr_str: str) -> dict[str, str]:
    out: dict[str, str] = {}
    for m in re.finditer(r'([a-zA-Z_:][\w:.-]*)(?:\s*=\s*("([^"]*)"|\'([^\']*)\'|(\S+)))?', attr_str or ""):
        key = m.group(1)
        val = m.group(3) if m.group(3) is not None else (m.group(4) if m.group(4) is not None else (m.group(5) or ""))
        out[key] = val
    return out


def parse_el(el: str) -> tuple[str, dict[str, str], str]:
    el = (el or "").strip()
    m = re.match(r"<([a-zA-Z][\w-]*)([^>]*)>", el)
    if not m:
        return "", {}, el
    tag = m.group(1).lower()
    attrs = parse_attrs(m.group(2))
    if tag in VOID or (m.group(2) or "").rstrip().endswith("/"):
        return tag, attrs, ""
    start = m.end()
    depth = 1
    j = start
    pat = re.compile(r"</?" + re.escape(tag) + r"(?:\s[^>]*)?>", re.I)
    while True:
        mm = pat.search(el, j)
        if not mm:
            return tag, attrs, el[start:]
        if mm.group(0).startswith("</"):
            depth -= 1
            if depth == 0:
                return tag, attrs, el[start:mm.start()]
        else:
            depth += 1
        j = mm.end()


def split_top_level(html: str) -> list[str]:
    html = html or ""
    parts: list[str] = []
    i = 0
    n = len(html)
    while i < n:
        while i < n and html[i].isspace():
            i += 1
        if i >= n:
            break
        if html.startswith("<!--", i):
            j = html.find("-->", i)
            i = n if j < 0 else j + 3
            continue
        if html[i] != "<":
            j = html.find("<", i)
            chunk = html[i:] if j < 0 else html[i:j]
            if chunk.strip():
                parts.append(chunk)
            i = n if j < 0 else j
            continue
        m = re.match(r"<(/?)([a-zA-Z][\w-]*)([^>]*)>", html[i:])
        if not m:
            i += 1
            continue
        tag = m.group(2).lower()
        if m.group(1) == "/":
            i += m.end()
            continue
        if tag in VOID or (m.group(3) or "").rstrip().endswith("/"):
            end = i + m.end()
            parts.append(html[i:end])
            i = end
            continue
        start = i
        i += m.end()
        depth = 1
        pat = re.compile(r"</?" + re.escape(tag) + r"(?:\s[^>]*)?>", re.I)
        while depth and i < n:
            mm = pat.search(html, i)
            if not mm:
                parts.append(html[start:])
                return parts
            if mm.group(0).startswith("</"):
                depth -= 1
            else:
                depth += 1
            i = mm.end()
        parts.append(html[start:i])
    return parts


def block(name: str, attrs: dict | None = None, inner: str | None = None, self_close: bool = False) -> str:
    if attrs:
        js = json.dumps(attrs, ensure_ascii=False, separators=(",", ":"))
        head = f"<!-- wp:{name} {js} -->"
        close_self = f"<!-- wp:{name} {js} /-->"
    else:
        head = f"<!-- wp:{name} -->"
        close_self = f"<!-- wp:{name} /-->"
    if self_close or inner is None:
        return close_self
    return f"{head}\n{inner}\n<!-- /wp:{name} -->"


def gutenberg_spacing(style: str) -> dict:
    """Map margin-/padding-* declarations to a Gutenberg style.spacing object."""
    if not style:
        return {}
    margin = parse_margin_style(style)
    padding: dict[str, str] = {}
    for side in ("top", "right", "bottom", "left"):
        m = re.search(rf"padding-{side}\s*:\s*([^;]+)", style, flags=re.I)
        if m:
            padding[side] = m.group(1).strip()
    spacing: dict[str, dict[str, str]] = {}
    if margin:
        spacing["margin"] = margin
    if padding:
        spacing["padding"] = padding
    return {"spacing": spacing} if spacing else {}


def spacing_style_string(style: str) -> str:
    """Keep only margin-/padding-* so saved HTML matches Gutenberg save()."""
    parts: list[str] = []
    for prop in (
        "margin-top",
        "margin-right",
        "margin-bottom",
        "margin-left",
        "padding-top",
        "padding-right",
        "padding-bottom",
        "padding-left",
    ):
        m = re.search(rf"{re.escape(prop)}\s*:\s*([^;]+)", style or "", flags=re.I)
        if m:
            parts.append(f"{prop}:{m.group(1).strip()}")
    return ";".join(parts)


def group(class_name: str, inner: str, style: str = "") -> str:
    class_name = " ".join((class_name or "").split())
    if not inner or not inner.strip():
        return ""
    attrs: dict = {}
    if class_name:
        attrs["className"] = class_name
    gstyle = gutenberg_spacing(style)
    if gstyle:
        attrs["style"] = gstyle
    cls = f" wp-block-group {class_name}".rstrip() if class_name else " wp-block-group"
    html_style = spacing_style_string(style)
    style_attr = f' style="{esc_attr(html_style)}"' if html_style else ""
    return block("group", attrs or None, f'<div class="{cls.strip()}"{style_attr}>\n{inner}\n</div>')


def parse_margin_style(style: str) -> dict[str, str]:
    """Pull margin-* declarations into a Gutenberg spacing.margin map."""
    out: dict[str, str] = {}
    for side in ("top", "right", "bottom", "left"):
        m = re.search(rf"margin-{side}\s*:\s*([^;]+)", style or "", flags=re.I)
        if m:
            out[side] = m.group(1).strip()
    return out


def heading_block(level: int, inner: str, cls: str = "", style: str = "") -> str:
    text = clean_inline(inner)
    if not strip_tags(text):
        return ""
    attrs: dict = {}
    if level != 2:
        attrs["level"] = level
    if cls:
        attrs["className"] = cls
    spacing = parse_margin_style(style)
    if spacing:
        attrs["style"] = {"spacing": {"margin": spacing}}
    cls_attr = f' class="wp-block-heading {cls}"' if cls else ' class="wp-block-heading"'
    html_style = spacing_style_string(style)
    style_attr = f' style="{esc_attr(html_style)}"' if html_style else ""
    return block("heading", attrs or None, f"<h{level}{cls_attr}{style_attr}>{text}</h{level}>")


def para_block(inner: str, cls: str = "", style: str = "") -> str:
    text = clean_inline(inner)
    if not strip_tags(text):
        return ""
    attrs: dict = {}
    if cls:
        attrs["className"] = cls
    gstyle = gutenberg_spacing(style)
    if gstyle:
        attrs["style"] = gstyle
    cls_attr = f' class="{cls}"' if cls else ""
    html_style = spacing_style_string(style)
    style_attr = f' style="{esc_attr(html_style)}"' if html_style else ""
    return block("paragraph", attrs or None, f"<p{cls_attr}{style_attr}>{text}</p>")


def list_block(tag: str, inner: str, cls: str = "") -> str:
    items = re.findall(r"<li\b[^>]*>([\s\S]*?)</li>", inner, flags=re.I)
    if not items:
        return ""
    wrap_q = "q-list" in (cls or "").split()
    lis = []
    for it in items:
        body = clean_inline(it)
        if wrap_q:
            lis.append(f'<li><span class="q-item">{body}</span></li>')
        else:
            lis.append(f"<li>{body}</li>")
    attrs: dict = {}
    if tag == "ol":
        attrs["ordered"] = True
    if cls:
        attrs["className"] = cls
    cls_attr = f' class="{cls}"' if cls else ""
    return block("list", attrs or None, f"<{tag}{cls_attr}>{''.join(lis)}</{tag}>")


def step_grid_block(inner: str, cls: str = "") -> str:
    """Editable Group cards for ol.step-grid (CMS can edit titles/copy/chips/notes)."""
    # Top-level steps only — nested <li> in .chips must not flatten the grid.
    parts = re.split(r"<li\b[^>]*>\s*(?=<span\b[^>]*class=\"[^\"]*\bsn\b)", inner, flags=re.I)
    items = [p for p in parts[1:] if p.strip()] if len(parts) > 1 else []
    if not items:
        items = re.findall(r"<li\b[^>]*>([\s\S]*?)</li>", inner, flags=re.I)
    if not items:
        return ""
    cards: list[str] = []
    for i, it in enumerate(items, start=1):
        sn_m = re.search(r'class="sn"[^>]*>([^<]+)', it)
        sn = strip_tags(sn_m.group(1)) if sn_m else f"{i:02d}"
        h_m = re.search(r"<h3[^>]*>([\s\S]*?)</h3>", it)
        title = strip_tags(h_m.group(1)) if h_m else f"Step {i}"
        body_blocks = heading_block(3, title)
        # First non-note paragraph after the heading.
        body = ""
        note = ""
        for pm in re.finditer(r"<p(\s[^>]*)?>([\s\S]*?)</p>", it, flags=re.I):
            attrs = pm.group(1) or ""
            text = clean_inline(pm.group(2))
            if not strip_tags(text):
                continue
            if re.search(r'class="[^"]*\bnote\b', attrs):
                note = text
            elif not body:
                body = text
        if body:
            body_blocks += "\n\n" + para_block(body)
        chips_m = re.search(r'<ul\b[^>]*class="[^"]*\bchips\b[^"]*"[^>]*>([\s\S]*?)</ul>', it, flags=re.I)
        if chips_m:
            chip_block = list_block("ul", chips_m.group(1), "chips")
            if chip_block:
                body_blocks += "\n\n" + chip_block
        if note:
            body_blocks += "\n\n" + para_block(note, "note")
        sn_block = block("paragraph", {"className": "sn"}, f'<p class="sn">{sn}</p>')
        card_inner = sn_block + "\n\n" + group("", body_blocks)
        cards.append(group("step-item", card_inner))
    grid_cls = cls if "step-grid" in (cls or "").split() else f"step-grid {cls}".strip()
    return group(grid_cls, "\n\n".join(cards))


def image_block(src: str, alt: str = "") -> str:
    if not src:
        return ""
    attrs = {"url": src}
    if alt:
        attrs["alt"] = unescape(alt)
    return block(
        "image",
        attrs,
        f'<figure class="wp-block-image"><img src="{esc_attr(src)}" alt="{esc_attr(unescape(alt))}"/></figure>',
    )


def buttons_block(items: list[tuple[str, str, str]]) -> str:
    """Emit Spark <a class="btn"> so SCSS (.promo .btn, pills) still matches."""
    if not items:
        return ""
    chunks = []
    for label, href, cls in items:
        label = strip_tags(label)
        if not label:
            continue
        classes = " ".join(cls.split()) if cls else "btn btn-primary"
        if "btn" not in classes.split():
            classes = f"btn {classes}"
        href = rewrite_href(href)
        chunks.append(para_block(f'<a class="{esc_attr(classes)}" href="{esc_attr(href)}">{label}</a>'))
    return "\n\n".join(c for c in chunks if c)


def details_block(el: str) -> str:
    _tag, attrs, inner = parse_el(el)
    sm = re.search(r"<summary\b[^>]*>([\s\S]*?)</summary>", inner, flags=re.I)
    summary_inner = sm.group(1) if sm else "Question"
    summary_inner = re.sub(r'<span class="q-ic"\s*></span>', "", summary_inner)
    rest = re.sub(r"<summary\b[\s\S]*?</summary>", "", inner, count=1, flags=re.I).strip()
    if rest.startswith("<"):
        rtag, rattrs, rinner = parse_el(rest)
        if rtag == "div" and "faq-a" in rattrs.get("class", "").split():
            rest = rinner
    ans = convert_nodes(rest) or para_block(rest)
    ans_group = group("faq-a", ans) if ans else ""
    # crit-list keeps .n / .t / small; flattening them concatenates "1Class sizeAsk…"
    if re.search(r'class="[^"]*\b(?:n|t)\b', summary_inner):
        summary_html = rewrite_links_in_html(summary_inner) + '<span class="q-ic"></span>'
    else:
        summary_html = f"{esc_attr(strip_tags(summary_inner))}<span class=\"q-ic\"></span>"
    wp_attrs: dict = {"className": "is-style-faq faq-item"}
    open_attr = ""
    if "open" in attrs:
        wp_attrs["showContent"] = True
        open_attr = " open"
    return block(
        "details",
        wp_attrs,
        f'<details class="wp-block-details is-style-faq faq-item"{open_attr}><summary>{summary_html}</summary>\n'
        f"{ans_group}\n"
        "</details>",
    )


def table_block(el: str) -> str:
    table_html = el
    if "<table" not in el.lower():
        m = re.search(r"(<table[\s\S]*?</table>)", el, flags=re.I)
        if not m:
            return ""
        table_html = m.group(1)
    table_html = rewrite_links_in_html(table_html)
    return block("table", {"className": "tbl"}, f'<figure class="wp-block-table tbl">{table_html}</figure>')


def html_block(inner: str) -> str:
    return f"<!-- wp:html -->\n{inner.strip()}\n<!-- /wp:html -->"


def card_block(
    variation: str,
    title: str,
    excerpt: str,
    url: str = "",
    image: str = "",
    go: str = "Explore",
    role: str = "",
    cite: str = "",
    source: str = "",
    date: str = "",
    bio: str = "",
) -> str:
    attrs: dict = {
        "variation": variation,
        "title": unescape(title),
        "excerpt": clean_inline(excerpt) if excerpt else "",
    }
    if url:
        attrs["url"] = rewrite_href(url)
    if image:
        attrs["imageUrl"] = image
    if go and variation == "hub":
        attrs["goLabel"] = strip_tags(go) or "Explore"
    if go and variation == "program":
        attrs["goLabel"] = strip_tags(go)
    if role:
        attrs["role"] = unescape(role)
    if cite:
        attrs["cite"] = unescape(cite)
    if source:
        attrs["source"] = unescape(source)
    if date:
        attrs["date"] = unescape(date)
    if bio:
        attrs["bio"] = bio
    return block("sparkvn/card", attrs, self_close=True)


def find_child(inner: str, class_token: str) -> str:
    for part in split_top_level(inner):
        _t, a, _i = parse_el(part)
        cls = a.get("class", "")
        if class_token in cls.split():
            return part
    # fallback regex
    m = re.search(rf'<[^>]+class="[^"]*\b{re.escape(class_token)}\b[^"]*"[\s\S]*$', inner)
    return m.group(0) if m else ""


def split_block(el: str, cls: str) -> str:
    _t, _a, inner = parse_el(el)
    copy_el = ""
    media_el = ""
    others: list[str] = []
    for part in split_top_level(inner):
        _pt, pa, _pi = parse_el(part)
        pcls = pa.get("class", "")
        if "split-copy" in pcls.split():
            copy_el = part
        elif "split-media" in pcls.split():
            media_el = part
        elif part.strip():
            others.append(part)
    if not media_el and others:
        media_el = others[0]
    if not copy_el and not media_el:
        return convert_nodes(inner)
    rev = "rev" in cls.split()
    style = "split-rev" if rev else "split"
    _ct, copy_attrs, copy_inner = parse_el(copy_el) if copy_el else ("", {}, "")
    copy_cls = copy_attrs.get("class", "split-copy") or "split-copy"
    if "split-copy" not in copy_cls.split():
        copy_cls = f"split-copy {copy_cls}".strip()
    media_tag, media_attrs, media_inner = parse_el(media_el) if media_el else ("", {}, "")
    media_cls = media_attrs.get("class", "") or "split-media"
    copy_blocks = convert_nodes(copy_inner)
    img_m = re.search(r'<img[^>]+src="([^"]+)"', media_inner)
    alt_m = re.search(r'<img[^>]+alt="([^"]*)"', media_inner)
    media_tokens = set(media_cls.split())
    widget = next((w for w in ("q-card", "scorecard", "info-card") if w in media_tokens), "")
    if widget:
        # HTML is .split > .q-card / .scorecard. Put the skin on the column
        # only — a nested group with the same class double-pads the card.
        media_blocks = convert_nodes(media_inner)
        media_cls = widget
    elif img_m and "split-media" in media_tokens:
        media_blocks = image_block(img_m.group(1), alt_m.group(1) if alt_m else "")
    else:
        media_blocks = convert_element(media_el) if media_el else convert_nodes(media_inner)
        if media_blocks.startswith("<!-- wp:group") and "split-media" not in media_tokens:
            media_cls = media_cls or "split-media"
    col_copy = block(
        "column",
        {"className": copy_cls},
        f'<div class="wp-block-column {copy_cls}">\n{copy_blocks}\n</div>',
    )
    col_media = block(
        "column",
        {"className": media_cls},
        f'<div class="wp-block-column {media_cls}">\n{media_blocks}\n</div>',
    )
    cols_inner = f"{col_copy}\n{col_media}"
    cols_cls = f"is-style-{style} split" + (" rev" if rev else "")
    return block(
        "columns",
        {"className": cols_cls},
        f'<div class="wp-block-columns {cols_cls}">\n{cols_inner}\n</div>',
    )


def hub_cards(el: str) -> str:
    cards = []
    for m in re.finditer(
        r'<a class="hub-card[^"]*" href="([^"]+)"[^>]*>([\s\S]*?)</a>',
        el,
        flags=re.I,
    ):
        href, body = m.group(1), m.group(2)
        img = re.search(r'<img[^>]+src="([^"]+)"', body)
        title = re.search(r"<h3[^>]*>([\s\S]*?)</h3>", body)
        para = re.search(r"<p[^>]*>([\s\S]*?)</p>", body)
        go = re.search(r'<span class="go"[^>]*>([\s\S]*?)</span>', body)
        cards.append(
            card_block(
                "hub",
                strip_tags(title.group(1)) if title else "Card",
                strip_tags(para.group(1)) if para else "",
                href,
                img.group(1) if img else "",
                strip_tags(go.group(1)) if go else "Explore",
            )
        )
    if not cards:
        return convert_nodes(parse_el(el)[2])
    return group("hub-grid", "\n\n".join(cards))


def adv_cards(el: str) -> str:
    _tag, attrs, _inner = parse_el(el)
    cls = attrs.get("class", "adv-grid") or "adv-grid"
    compact = re.sub(r"\s+", "", attrs.get("style", ""))
    if "repeat(2" in compact and "cols-2" not in cls.split():
        cls = f"{cls} cols-2"
    cards = []
    for m in re.finditer(r"<article class=\"adv-card[\s\S]*?</article>", el, flags=re.I):
        body = m.group(0)
        img = re.search(r'<img[^>]+src="([^"]+)"', body)
        title = re.search(r"<h3[^>]*>([\s\S]*?)</h3>", body)
        para = re.search(r"<p[^>]*>([\s\S]*?)</p>", body)
        cards.append(
            card_block(
                "advantage",
                strip_tags(title.group(1)) if title else "",
                clean_inline(para.group(1)) if para else "",
                "",
                img.group(1) if img else "",
            )
        )
    return group(cls, "\n\n".join(cards)) if cards else ""


def team_cards(el: str) -> str:
    cards = []
    for m in re.finditer(r"<article class=\"team-card[\s\S]*?</article>", el, flags=re.I):
        body = m.group(0)
        img = re.search(r'<img[^>]+src="([^"]+)"', body)
        title = re.search(r"<h3[^>]*>([\s\S]*?)</h3>", body)
        role = re.search(r'<p class="role"[^>]*>([\s\S]*?)</p>', body)
        details = re.search(r"<details\b[\s\S]*?</details>", body, flags=re.I)
        intro = re.sub(r"<details\b[\s\S]*?</details>", "", body, flags=re.I)
        paras = re.findall(r"<p(?![^>]*\brole\b)[^>]*>([\s\S]*?)</p>", intro)
        excerpt = paras[0] if paras else ""
        bio = ""
        if details:
            bio_paras = re.findall(r"<p[^>]*>([\s\S]*?)</p>", details.group(0))
            bio = "".join(
                f"<p>{clean_inline(p)}</p>"
                for p in bio_paras
                if strip_tags(p).strip()
            )
        cards.append(
            card_block(
                "team",
                strip_tags(title.group(1)) if title else "",
                strip_tags(excerpt),
                "",
                img.group(1) if img else "",
                role=strip_tags(role.group(1)) if role else "",
                bio=bio,
            )
        )
    return group("team-grid", "\n\n".join(cards)) if cards else ""


def testi_cards(el: str) -> str:
    cards = []
    for m in re.finditer(r"<figure class=\"testi[\s\S]*?</figure>", el, flags=re.I):
        body = m.group(0)
        src = re.search(r'<span class="gsrc"[^>]*>([\s\S]*?)</span>', body)
        quote = re.search(r"<blockquote[^>]*>([\s\S]*?)</blockquote>", body)
        cite = re.search(r"<b>([\s\S]*?)</b>", body)
        date = re.search(r"</b>\s*<span>([\s\S]*?)</span>", body)
        img = re.search(r'<img[^>]+src="([^"]+)"', body)
        cards.append(
            card_block(
                "testimonial",
                strip_tags(cite.group(1)) if cite else "",
                clean_inline(quote.group(1)) if quote else "",
                "",
                img.group(1) if img else "",
                cite=strip_tags(cite.group(1)) if cite else "",
                source=strip_tags(src.group(1)) if src else "",
                date=strip_tags(date.group(1)) if date else "",
            )
        )
    return group("testi-grid", "\n\n".join(cards)) if cards else ""


def program_cards(el: str) -> str:
    cards = []
    for m in re.finditer(r"<article class=\"pf\"[\s\S]*?</article>", el, flags=re.I):
        body = m.group(0)
        img = re.search(r'<img[^>]+src="([^"]+)"', body)
        title = re.search(r"<h3[^>]*>([\s\S]*?)</h3>", body)
        excerpt = re.search(r"<p(?![^>]*class=\"tag\")[^>]*>([\s\S]*?)</p>", body)
        href = re.search(r'<a[^>]+href="([^"]+)"', body)
        go = re.search(r"<a[^>]*class=\"[^\"]*btn[^\"]*\"[^>]*>([\s\S]*?)</a>", body)
        cards.append(
            card_block(
                "program",
                strip_tags(title.group(1)) if title else "",
                strip_tags(excerpt.group(1)) if excerpt else "",
                href.group(1) if href else "",
                img.group(1) if img else "",
                strip_tags(go.group(1)) if go else "Explore the Program",
            )
        )
    return "\n\n".join(cards)


def faq_list(el: str) -> str:
    _t, a, inner = parse_el(el)
    items = []
    for part in split_top_level(inner):
        if part.lower().lstrip().startswith("<details"):
            items.append(details_block(part))
    cls = a.get("class", "faq-list")
    return group(cls, "\n\n".join(items)) if items else ""


def tabs_block(el: str) -> str:
    _t, a, inner = parse_el(el)
    cls = a.get("class", "")
    # Encode rich button labels without HTML quotes (those break Gutenberg JSON).
    # <span class="k">A</span>B     →  A::B   (opt-tabs)
    # <span class="L">S</span><span class="T">Title</span>  →  S::Title  (letters)
    labels = []
    for raw in re.findall(r"<button\b[^>]*>([\s\S]*?)</button>", inner):
        k = re.search(r'<span class="k">([\s\S]*?)</span>([\s\S]*)', raw)
        L = re.search(r'<span class="L">([\s\S]*?)</span>', raw)
        T = re.search(r'<span class="T">([\s\S]*?)</span>', raw)
        if k:
            labels.append(f"{strip_tags(k.group(1))}::{strip_tags(k.group(2))}")
        elif L and T:
            labels.append(f"{strip_tags(L.group(1))}::{strip_tags(T.group(1))}")
        else:
            text = strip_tags(raw).strip()
            if text:
                labels.append(text)
    panels_wrap = ""
    list_cls = ""
    for part in split_top_level(inner):
        _pt, pa, _pi = parse_el(part)
        ptokens = pa.get("class", "").split()
        if "tab-list" in ptokens:
            list_cls = pa.get("class", "")
        if "tab-panels" in ptokens:
            panels_wrap = part
    panel_els = split_top_level(parse_el(panels_wrap)[2]) if panels_wrap else []
    groups = []
    for p in panel_els:
        _pt, pa, pi = parse_el(p)
        pcls = pa.get("class", "tab-panel copy")
        if "tab-panel" not in pcls.split():
            pcls = "tab-panel " + pcls
        # tab-panel.split IS the Spark grid. Do not wrap children in columns —
        # that nests a second grid and squeezes the panel into one column.
        inner_blocks = convert_nodes(pi)
        groups.append(group(pcls, inner_blocks))
    variation = "default"
    if "opt-tabs" in cls.split():
        variation = "opt-tabs"
    elif "school-tabs" in cls.split():
        variation = "school-tabs"
    elif "spark-letters" in list_cls.split():
        variation = "letters"
    attrs = {"labels": " | ".join(labels) if labels else "Tab 1 | Tab 2", "variation": variation}
    return block("sparkvn/tabs", attrs, "\n\n".join(g for g in groups if g))


def form_block(el: str) -> str:
    heading = ""
    sub = ""
    hm = re.search(r"<h3[^>]*>([\s\S]*?)</h3>", el)
    sm = re.search(r'<p class="sub"[^>]*>([\s\S]*?)</p>', el)
    if hm:
        heading = strip_tags(hm.group(1))
    if sm:
        sub = strip_tags(sm.group(1))
    variation = FORM_BY_SLUG.get(CTX.get("slug", ""), "contact")
    attrs: dict = {"variation": variation}
    if heading:
        attrs["heading"] = heading
    if sub:
        attrs["sub"] = sub
    return block("sparkvn/form", attrs, self_close=True)


def hero_block(raw: str) -> str:
    m = re.search(r'(<section class="hero[\s\S]*?</section>)', raw, flags=re.I)
    if not m:
        return ""
    el = m.group(1)
    _t, a, inner = parse_el(el)
    cls = a.get("class", "")
    is_home = "hero--page" not in cls.split() and "hero" in cls.split()
    attrs: dict = {
        "variation": "home" if is_home else "page",
        "title": "Spark English Center",
    }
    if "center" in cls.split():
        attrs["center"] = True
    if "short" in cls.split():
        attrs["short"] = True
    aria = a.get("aria-label")
    if aria:
        attrs["ariaLabel"] = unescape(aria)
    img = re.search(r'<div class="hero-bg">[\s\S]*?<img[^>]+src="([^"]+)"', el)
    video = re.search(r'<video[^>]+src="([^"]+)"', el)
    poster = re.search(r'poster="([^"]+)"', el)
    if img:
        attrs["imageUrl"] = img.group(1)
    if video:
        attrs["videoUrl"] = video.group(1)
    if poster:
        attrs["posterUrl"] = poster.group(1)
    eye = re.search(r'<span class="eyebrow"[^>]*>([\s\S]*?)</span>', el)
    if eye:
        attrs["eyebrow"] = strip_tags(eye.group(1))
    h1 = re.search(r"<h1[^>]*>([\s\S]*?)</h1>", el)
    if h1:
        attrs["title"] = strip_tags(h1.group(1))
    lead = re.search(r'<p class="lead[^"]*"[^>]*>([\s\S]*?)</p>', el)
    if lead:
        attrs["lead"] = strip_tags(lead.group(1))
    lines_m = re.search(r'<p class="lines"[^>]*>([\s\S]*?)</p>', el)
    if lines_m:
        small = re.search(r"<small>([\s\S]*?)</small>", lines_m.group(1))
        main = re.sub(r"<small>[\s\S]*?</small>", "", lines_m.group(1))
        attrs["lines"] = strip_tags(main)
        if small:
            attrs["linesSmall"] = strip_tags(small.group(1))
    chips = []
    for cm in re.finditer(r'<span class="chip\b[^>]*>', el):
        start = cm.end()
        depth = 1
        j = start
        while j < len(el) and depth:
            nm = re.search(r"</?span\b[^>]*>", el[j:], flags=re.I)
            if not nm:
                break
            if nm.group(0).lower().startswith("</"):
                depth -= 1
                if depth == 0:
                    text = strip_tags(el[start : j + nm.start()])
                    if text:
                        chips.append(text)
                    break
            else:
                depth += 1
            j += nm.end()
    if chips:
        attrs["chips"] = " | ".join(chips)
    cta = re.search(r'<div class="hero-cta">[\s\S]*?<a[^>]+href="([^"]+)"[^>]*>([\s\S]*?)</a>', el)
    if cta:
        attrs["ctaUrl"] = rewrite_href(cta.group(1))
        attrs["ctaLabel"] = strip_tags(cta.group(2))
    return block("sparkvn/hero", attrs, self_close=True)


def skip_store_chunk(cls: str) -> bool:
    tokens = set(cls.split())
    if tokens & {"store-grid", "store-h", "anchor-nav"} or "prize" in cls:
        if not CTX.get("store"):
            CTX["store"] = True
            return False  # first one emits query
        return True
    return False


def convert_element(el: str) -> str:
    el = el.strip()
    if not el:
        return ""
    if not el.startswith("<"):
        return para_block(el) if strip_tags(el) else ""
    tag, attrs, inner = parse_el(el)
    cls = attrs.get("class", "")
    tokens = set(cls.split())

    if "crumb-bar" in tokens:
        return ""
    if tag == "script" or tag == "template":
        return ""

    if "form-card" in tokens or tag == "form" or attrs.get("id") == "bookForm":
        return form_block(el)
    if "tabs" in tokens and "tab-list" not in tokens and "tab-panel" not in tokens:
        return tabs_block(el)
    if tag == "details" or "faq-item" in tokens:
        return details_block(el)
    if "faq-list" in tokens:
        return faq_list(el)
    if "hub-grid" in tokens:
        return hub_cards(el)
    if "adv-grid" in tokens:
        return adv_cards(el)
    if "team-grid" in tokens:
        return team_cards(el)
    if "testi-grid" in tokens:
        return testi_cards(el)
    if "res-grid" in tokens:
        if CTX.get("res"):
            return ""
        CTX["res"] = True
        return block("sparkvn/query-resources", self_close=True)
    if "post-grid" in tokens or ("blog-toolbar" in tokens and CTX.get("slug") == "teacher-blogs"):
        if CTX.get("teacher_posts"):
            return ""
        CTX["teacher_posts"] = True
        return block("sparkvn/query-posts", {"category": "teachers", "title": "Featured Teacher Articles"}, self_close=True)
    if skip_store_chunk(cls) or (CTX.get("slug") == "spark-store" and tokens & {"store-grid", "store-h", "anchor-nav"}):
        if CTX.get("slug") == "spark-store":
            if not CTX.get("store_emitted"):
                CTX["store_emitted"] = True
                return block("sparkvn/query-prizes", self_close=True)
            return ""
    if "map-card" in tokens or tag == "iframe":
        iframe = re.search(r"<iframe[\s\S]*?</iframe>", el, flags=re.I)
        chunk = html_block(iframe.group(0) if iframe else el)
        return group("map-card", chunk) if "map-card" in tokens else chunk
    if tag == "table" or "tbl-wrap" in tokens:
        table = table_block(el)
        return group("tbl-wrap", table) if "tbl-wrap" in tokens else table
    if "split" in tokens and tag == "div" and ("split-copy" in inner or "split-media" in inner):
        return split_block(el, cls)
    if tag in {"h1", "h2", "h3", "h4", "h5", "h6"}:
        return heading_block(int(tag[1]), inner, cls, attrs.get("style", ""))
    if tag == "p":
        return para_block(inner, cls, attrs.get("style", ""))
    if tag in {"ul", "ol"}:
        if "step-grid" in tokens:
            return step_grid_block(inner, cls)
        return list_block(tag, inner, cls)
    if tag == "img":
        return image_block(attrs.get("src", ""), attrs.get("alt", ""))
    if tag == "a" and "btn" in tokens:
        return buttons_block([(inner, attrs.get("href", ""), cls)])
    if tag == "br":
        return ""
    if tag == "hr":
        hr_cls = cls or "rule"
        return block(
            "separator",
            {"className": hr_cls},
            f'<hr class="wp-block-separator has-alpha-channel-opacity {hr_cls}"/>',
        )
    if tag == "span" and "spark" in tokens and not strip_tags(inner or ""):
        return block("paragraph", {"className": "spark"}, '<p class="spark"></p>')
    if "pf-ideal" in tokens:
        label_m = re.search(r"<b>([\s\S]*?)</b>", inner or "")
        rest = strip_tags(re.sub(r"<b>[\s\S]*?</b>", "", inner or "")).strip()
        label = strip_tags(label_m.group(1)) if label_m else "Ideal for"
        bits = [para_block(f"<b>{label}</b>")]
        if rest:
            bits.append(para_block(rest))
        return group(cls, "\n\n".join(b for b in bits if b))
    if tag in {"span", "section", "div", "nav", "article", "figure", "main", "aside"} and cls and not re.search(r"<[a-zA-Z]", inner or ""):
        text = strip_tags(inner or "")
        return para_block(text, cls) if text else ""
    if tag == "span" and "label" in tokens:
        text = strip_tags(inner or "")
        if not text:
            return ""
        spark = '<span class="spark"></span>' if re.search(r'class="[^"]*\bspark\b', inner or "") else ""
        return block("paragraph", {"className": "label"}, f'<p class="label">{spark}{text}</p>')
    if tag == "span":
        text = strip_tags(inner or "")
        return para_block(text, cls) if text else ""
    if tag in {"section", "div", "nav", "article", "figure", "main", "aside"}:
        if "no-results" in tokens or attrs.get("id") in {"noResults", "pagination", "blogCount", "blogFilters", "blogSearch"}:
            return ""
        if not inner.strip():
            # Timeline dots are CSS ::before on .rm-step — skip empty .rm-dot nodes.
            if "rm-dot" in tokens:
                return ""
            return ""
        # Phrasing-only wrappers (.need = <b>label</b>body) must stay one paragraph.
        # img / hr are not phrasing — .img-pair / .photo-strip would otherwise vanish.
        if not re.search(
            r"<(?:div|section|article|ul|ol|h[1-6]|p|details|figure|blockquote|nav|aside|table|form|img|hr|picture)\b",
            inner,
            flags=re.I,
        ):
            para = para_block(inner)
            return group(cls, para, attrs.get("style", "")) if para else ""
        converted = convert_nodes(inner)
        if not converted:
            return ""
        # Always keep the wrapper. Spark CSS uses child combinators / direct-child grids.
        return group(cls, converted, attrs.get("style", ""))
    text = strip_tags(inner or el)
    return para_block(text, cls) if text else ""


def convert_nodes(html: str) -> str:
    parts = split_top_level(html)
    out: list[str] = []
    i = 0
    while i < len(parts):
        tag, attrs, inner = parse_el(parts[i])
        if tag == "a" and "btn" in attrs.get("class", "").split():
            btns = []
            while i < len(parts):
                t, a, inn = parse_el(parts[i])
                if t == "a" and "btn" in a.get("class", "").split():
                    btns.append((inn, a.get("href", ""), a.get("class", "")))
                    i += 1
                else:
                    break
            chunk = buttons_block(btns)
            if chunk:
                out.append(chunk)
            continue
        chunk = convert_element(parts[i])
        if chunk:
            out.append(chunk)
        i += 1
    return "\n\n".join(out)


def convert_page(raw: str, slug: str) -> str:
    CTX.update({"slug": slug, "store": False, "res": False, "programs": False, "teacher_posts": False, "store_emitted": False})
    raw = re.sub(r"<script[\s\S]*?</script>", "", raw, flags=re.I)
    raw = re.sub(r"<template[\s\S]*?</template>", "", raw, flags=re.I)
    parts = []
    hero = hero_block(raw)
    if hero:
        parts.append(hero)
    main = re.search(r"<main\b[^>]*>([\s\S]*)</main>", raw, flags=re.I)
    body = main.group(1) if main else ""
    body = re.sub(r'<div class="crumb-bar"[\s\S]*?</nav></div></div>\s*', "", body, count=1)
    converted = convert_nodes(body)
    if converted:
        parts.append(converted)
    return "\n\n".join(parts)


# ---------------------------------------------------------------------------
# Patterns (placeholder Gutenberg markup)
# ---------------------------------------------------------------------------

def write_pattern(slug: str, title: str, category: str, markup: str, description: str = "") -> None:
    if not markup or not markup.strip():
        print(f"WARN empty pattern: {slug}")
        return
    desc = f" * Description: {description}\n" if description else ""
    header = f"""<?php
/**
 * Title: {title}
 * Slug: sparkvn/{slug}
 * Categories: {category}
{desc} * Viewport Width: 1180
 */
?>
"""
    (PATTERNS / f"{slug}.php").write_text(header + markup.strip() + "\n", encoding="utf-8")


def _extract_section(html_name: str, token: str) -> str:
    raw = (HTML / html_name).read_text(encoding="utf-8")
    for m in re.finditer(r"<section\b[\s\S]*?</section>", raw, flags=re.I):
        if token in m.group(0):
            return m.group(0)
    return ""


def pattern_from_html(html_name: str, token: str) -> str:
    slug = HTML_TO_PATH.get(html_name, html_name.replace(".html", "")).strip("/").split("/")[-1]
    prev = dict(CTX)
    CTX.update({"slug": slug, "store": False, "res": False, "programs": False, "teacher_posts": False, "store_emitted": False})
    section = _extract_section(html_name, token)
    out = convert_nodes(section) if section else ""
    CTX.clear()
    CTX.update(prev)
    return out


IMG = "assets/img/campus-classroom.jpg"
IMG2 = "assets/img/campus-group.jpg"
IMG3 = "assets/img/campus-writing.jpg"

write_pattern("summary", "Summary (In short)", "sparkvn-content", pattern_from_html("phonics.html", "summary"), "In-short takeaways from a program page.")
write_pattern("split", "Split", "sparkvn-layout", pattern_from_html("index.html", 'class="split"'), "Image + copy row from Home.")
write_pattern("split-rev", "Split reverse", "sparkvn-layout", pattern_from_html("index.html", "two-lists"), "Reversed split with two lists from Home.")
write_pattern("split-tall", "Split tall", "sparkvn-layout", pattern_from_html("esl.html", "split-media tall") or pattern_from_html("esl.html", "tall"), "Portrait split from ESL.")
write_pattern("copy-section", "Copy section", "sparkvn-layout", pattern_from_html("phonics.html", 'class="copy"'), "Long-form copy block.")
write_pattern("sec-head", "Section heading", "sparkvn-layout", pattern_from_html("index.html", "Your child's Secret") or pattern_from_html("index.html", "sec-head"), "Centered section heading.")
write_pattern("hub-grid", "Hub grid", "sparkvn-cards", pattern_from_html("about.html", "hub-grid"), "About hub cards with Explore arrows.")
write_pattern("info-grid", "Info cards", "sparkvn-cards", pattern_from_html("assessment.html", "info-grid") or pattern_from_html("student-portal.html", "info-grid"), "Info card grid.")
write_pattern("advantage-cards", "Advantage cards", "sparkvn-cards", pattern_from_html("index.html", "adv-grid"), "Home advantage cards.")
write_pattern("two-lists", "Two lists", "sparkvn-content", pattern_from_html("index.html", "two-lists"), "Paired lists from Home.")
write_pattern("img-pair", "Image pair", "sparkvn-layout", pattern_from_html("index.html", "img-pair") or pattern_from_html("access.html", "img-pair"), "Two photos side by side.")
write_pattern("photo-strip", "Photo strip", "sparkvn-layout", pattern_from_html("the-spark-method.html", "photo-strip") or pattern_from_html("class-size.html", "photo-strip"), "Horizontal photo strip.")
write_pattern("faq", "FAQ", "sparkvn-content", pattern_from_html("index.html", "faq-list"), "FAQ accordion from Home.")
write_pattern("cta-band", "CTA band", "sparkvn-cta", pattern_from_html("about.html", "cta-band"), "Purple assessment band.")
write_pattern("related", "Related pages", "sparkvn-cta", pattern_from_html("about.html", 'class="related"'), "Related page pills.")
write_pattern("inline-cta", "Inline CTA", "sparkvn-cta", pattern_from_html("assessment.html", "inline-cta") or pattern_from_html("results-dashboard.html", "inline-cta"), "In-page assessment prompt.")
write_pattern("steps", "Numbered steps", "sparkvn-content", pattern_from_html("assessment.html", "num-steps") or pattern_from_html("assessment.html", "What happens next"), "Numbered process steps.")
write_pattern(
    "roadmap",
    "Roadmap",
    "sparkvn-content",
    pattern_from_html("academic-roadmap.html", "roadmap"),
    "Horizontal program pathway (Phonics → IELTS).",
)
write_pattern(
    "roadmap-vertical",
    "Parent success timeline",
    "sparkvn-content",
    pattern_from_html("parent-success-roadmap.html", "roadmap rm-v")
    or pattern_from_html("parent-success-roadmap.html", "roadmap"),
    "Vertical month-by-month timeline (dot + label + card).",
)
# Single step: first .rm-step inside parent success roadmap (for duplicating rows).
_psr = (ROOT / "html" / "parent-success-roadmap.html").read_text(encoding="utf-8", errors="replace")
_step_m = re.search(
    r'(<div class="rm-step">[\s\S]*?</div>\s*</div>\s*</div>)',
    _psr,
)
if _step_m:
    write_pattern(
        "roadmap-step",
        "Roadmap month step",
        "sparkvn-content",
        convert_nodes(_step_m.group(1)),
        "One timeline row (dot, month label, card).",
    )
write_pattern("cefr", "CEFR levels", "sparkvn-content", pattern_from_html("ib-school-guide.html", "cefr"), "CEFR level scale.")
write_pattern("verdict", "Verdict grid", "sparkvn-content", pattern_from_html("why-academic-english-matters.html", "verdict"), "Minus / Yes / Plus grid.")
write_pattern("pillars", "Pillars", "sparkvn-content", pattern_from_html("joe-evans.html", "pillars"), "Numbered pillars.")
write_pattern("feat-list", "Feature list", "sparkvn-content", pattern_from_html("our-story.html", "feat-list") or pattern_from_html("esl.html", "feat-list"), "Feature rows.")
write_pattern("notice", "Notice", "sparkvn-content", pattern_from_html("english-support.html", "notice"), "Parent callout.")
write_pattern("scorecard", "Scorecard", "sparkvn-content", pattern_from_html("english-support.html", "scorecard") or pattern_from_html("why-academic-english-matters.html", "scorecard"), "Progress scorecard.")
write_pattern("q-cards", "Question cards", "sparkvn-cards", pattern_from_html("reading-age-growth-center.html", "q-card"), "Question card grid.")
write_pattern("flag-list", "Flag list", "sparkvn-content", pattern_from_html("english-support.html", "flag-list") or pattern_from_html("english-support.html", "flags"), "Watch-out flags.")
write_pattern("table", "Mapping table", "sparkvn-content", pattern_from_html("ib-school-guide.html", "tbl-wrap") or pattern_from_html("ib-school-guide.html", "<table"), "CEFR mapping table.")
write_pattern("testimonials", "Testimonials", "sparkvn-cards", pattern_from_html("index.html", "testi-grid"), "Parent testimonials from Home.")
write_pattern("promo-band", "Promo band", "sparkvn-cta", pattern_from_html("index.html", "promo-band"), "Home assessment promo.")
write_pattern("team-grid", "Team grid", "sparkvn-cards", pattern_from_html("our-story.html", "team-grid"), "Team photos.")
write_pattern("portal-card", "Portal card", "sparkvn-connect", pattern_from_html("student-portal.html", "portal-card"), "Student portal card.")
write_pattern("form-split", "Form split", "sparkvn-connect", pattern_from_html("assessment.html", "final-grid") or pattern_from_html("assessment.html", "form-card"), "Assessment form + copy.")
write_pattern("contact-list", "Contact + map", "sparkvn-connect", pattern_from_html("contact.html", "contact-list") or pattern_from_html("contact.html", "final-grid"), "Contact details and map.")
write_pattern("map-card", "Map card", "sparkvn-connect", pattern_from_html("contact.html", "map-card"), "Google Map embed.")
write_pattern("store-anchor-nav", "Store catalog", "sparkvn-connect", block("sparkvn/query-prizes", self_close=True), "Live Spark Store prizes.")
write_pattern("chips", "Chip list", "sparkvn-content", pattern_from_html("programs.html", "chips") or list_block("ul", "<li>Phonics</li><li>Academic English</li><li>IELTS</li>", "chips is-style-chips"), "Filter chips.")
write_pattern("pathway", "Pathway", "sparkvn-cards", pattern_from_html("index.html", 'id="pathway"'), "Home learning pathway.")


def convert_fragment(el: str, slug: str = "programs") -> str:
    prev = dict(CTX)
    CTX.update({"slug": slug, "store": False, "res": False, "programs": False, "teacher_posts": False, "store_emitted": False})
    out = convert_element(el) if el.strip().startswith("<") else convert_nodes(el)
    CTX.clear()
    CTX.update(prev)
    return out


def program_card_slug(el: str) -> str:
    href = re.search(r'<a[^>]+href="([^"]+)"', el)
    src = href.group(1) if href else ""
    name = src.rsplit("/", 1)[-1]
    mapped = HTML_TO_PATH.get(name, "")
    if mapped == "/contact/" or name == "contact.html":
        return "speak-clearly"
    if mapped:
        return mapped.strip("/").split("/")[-1]
    title = re.search(r"<h3[^>]*>([\s\S]*?)</h3>", el)
    raw = strip_tags(title.group(1)) if title else "program"
    return re.sub(r"[^a-z0-9]+", "-", raw.lower()).strip("-")[:40] or "program"


_programs_html = (HTML / "programs.html").read_text(encoding="utf-8")
_pf_articles = re.findall(r'<article class="pf"[\s\S]*?</article>', _programs_html)
_pf_blocks = [convert_fragment(art) for art in _pf_articles]
if _pf_blocks:
    write_pattern("program-card", "Program card", "sparkvn-cards", _pf_blocks[0], "One program teaser. Edit copy and link to the program page.")
    write_pattern("program-feature", "Program listing", "sparkvn-cards", group("prog-feature", "\n\n".join(_pf_blocks)), "All six program teasers with links to program pages.")
    for art, markup in zip(_pf_articles, _pf_blocks):
        hm = re.search(r"<h3[^>]*>([\s\S]*?)</h3>", art)
        label = strip_tags(hm.group(1)) if hm else "Program"
        short = label.split(":")[0].replace(" Program", "").strip()
        write_pattern(
            f"program-card-{program_card_slug(art)}",
            f"Program: {short}",
            "sparkvn-cards",
            markup,
            f"{label} — links through to the program page.",
        )
write_pattern("program-detail", "Home program detail", "sparkvn-cards", pattern_from_html("index.html", "prog-detail"), "Home program detail + photos.")
write_pattern("page-hub", "Page: About", "sparkvn-pages", convert_page((HTML / "about.html").read_text(encoding="utf-8"), "about"), "Full About page: hero, hub cards, related, CTA.")
write_pattern("page-our-story", "Page: Our Story", "sparkvn-pages", convert_page((HTML / "our-story.html").read_text(encoding="utf-8"), "our-story"), "Full Our Story page: hero, team, programs, FAQ, CTA.")
write_pattern("page-joe-evans", "Page: Joe Evans", "sparkvn-pages", convert_page((HTML / "joe-evans.html").read_text(encoding="utf-8"), "joe-evans"), "Full Joe Evans page: summary, split, pillars, FAQ, CTA.")
write_pattern("page-english-support", "Page: English Support", "sparkvn-pages", convert_page((HTML / "english-support.html").read_text(encoding="utf-8"), "english-support"), "Full English Support guide: profiles, option tabs, evaluation, FAQ, CTA.")
write_pattern("page-guide", "Page: Guide", "sparkvn-pages", convert_page((HTML / "class-size.html").read_text(encoding="utf-8"), "class-size"), "Class Size guide starter.")
write_pattern("page-class-size", "Page: Class Size", "sparkvn-pages", convert_page((HTML / "class-size.html").read_text(encoding="utf-8"), "class-size"), "Full Class Size guide: summary, research, photos, FAQ, CTA.")
write_pattern("page-program", "Page: Program", "sparkvn-pages", convert_page((HTML / "phonics.html").read_text(encoding="utf-8"), "phonics"), "Phonics program starter.")
write_pattern("page-home", "Page: Home", "sparkvn-pages", convert_page((HTML / "index.html").read_text(encoding="utf-8"), "home"), "Full Home page.")
write_pattern("page-form", "Page: Assessment", "sparkvn-pages", convert_page((HTML / "assessment.html").read_text(encoding="utf-8"), "assessment"), "Assessment form page.")
write_pattern("page-programs", "Page: Programs", "sparkvn-pages", convert_page((HTML / "programs.html").read_text(encoding="utf-8"), "programs"), "Programs hub: intro copy, six program cards, related, CTA.")

print(f"Wrote {len(list(PATTERNS.glob('*.php')))} patterns")

# ---------------------------------------------------------------------------
# Seed pages
# ---------------------------------------------------------------------------

PAGE_MAP = [
    ("index.html", "page", "home", "Home", None, {"front": True}),
    ("about.html", "page", "about", "About", None, {}),
    ("our-story.html", "page", "our-story", "Our Story", "about", {}),
    ("joe-evans.html", "page", "joe-evans", "Joe Evans", "about", {"solid": True}),
    ("english-support.html", "page", "english-support", "English Support", "about", {}),
    ("the-spark-method.html", "page", "the-spark-method", "The Spark Method", "about", {}),
    ("class-size.html", "page", "class-size", "Class Size", "about", {}),
    ("community.html", "page", "community", "Community", "about", {}),
    ("assessment.html", "page", "assessment", "Assessment", None, {}),
    ("programs.html", "page", "programs", "Programs", None, {}),
    ("policy.html", "page", "policy", "Policy", "programs", {"solid": True}),
    ("access.html", "page", "exclusive", "Exclusive", None, {}),
    ("teachers-corner.html", "page", "teachers-corner", "Teachers Corner", "exclusive", {}),
    ("ask-a-teacher.html", "page", "ask-a-teacher", "Ask a Teacher", "teachers-corner", {}),
    ("teacher-blogs.html", "page", "teacher-blogs", "Teacher Blogs", "teachers-corner", {}),
    ("parent-center.html", "page", "parent-center", "Parent Center", "exclusive", {}),
    ("why-academic-english-matters.html", "page", "why-academic-english-matters", "Why Academic English Matters", "parent-center", {}),
    ("academic-roadmap.html", "page", "academic-roadmap", "Academic Roadmap", "parent-center", {}),
    ("results-dashboard.html", "page", "results-dashboard", "Results Dashboard", "parent-center", {}),
    ("parent-success-roadmap.html", "page", "parent-success-roadmap", "Parent Success Roadmap", "parent-center", {}),
    ("resource-library.html", "page", "resource-library", "Resource Library", "exclusive", {}),
    ("international-school-support-guide.html", "page", "international-school-support-guide", "International School Support Guide", "resource-library", {}),
    ("ib-school-guide.html", "page", "ib-school-guide", "IB School Guide", "resource-library", {}),
    ("reading-age-growth-center.html", "page", "reading-age-growth-center", "Reading Age Growth Center", "resource-library", {}),
    ("student-portal.html", "page", "student-portal", "Student Portal", "exclusive", {}),
    ("spark-store.html", "page", "spark-store", "Spark Store", "exclusive", {}),
    ("contact.html", "page", "contact", "Contact", None, {"solid": True}),
]

PROGRAMS = [
    ("phonics.html", "phonics", "Phonics & Literacy Development"),
    ("esl.html", "esl", "ESL Program"),
    ("ib-english.html", "ib-english", "IB English Program"),
    ("tides.html", "tides", "Tides: Upper-Primary English Program"),
    ("ielts-program.html", "ielts", "IELTS Success Program"),
]


def hero_image(raw: str) -> str:
    m = re.search(r'<div class="hero-bg">[\s\S]*?<img[^>]+src="([^"]+)"', raw)
    return m.group(1) if m else ""


pages = []
for html_name, wp_type, slug, title, parent, extra in PAGE_MAP:
    raw = (HTML / html_name).read_text(encoding="utf-8", errors="replace")
    desc_m = re.search(r'<meta name="description" content="([^"]*)"', raw)
    pages.append(
        {
            "type": wp_type,
            "slug": slug,
            "title": title,
            "parent": parent,
            "solid": bool(extra.get("solid")),
            "front": bool(extra.get("front")),
            "excerpt": desc_m.group(1) if desc_m else "",
            "content": convert_page(raw, slug),
            "html": html_name,
        }
    )

programs = []
for html_name, slug, title in PROGRAMS:
    raw = (HTML / html_name).read_text(encoding="utf-8", errors="replace")
    desc_m = re.search(r'<meta name="description" content="([^"]*)"', raw)
    programs.append(
        {
            "slug": slug,
            "title": title,
            "excerpt": desc_m.group(1) if desc_m else "",
            "content": convert_page(raw, slug),
            "html": html_name,
            "image": hero_image(raw),
        }
    )

post_raw = (HTML / "blog-post.html").read_text(encoding="utf-8", errors="replace")
post_main = re.search(r'<article class="prose"[^>]*>([\s\S]*?)</article>', post_raw)
full_body = convert_nodes(post_main.group(1)) if post_main else ""
blog_list = (HTML / "blog.html").read_text(encoding="utf-8", errors="replace")
teacher_raw = (HTML / "teacher-blogs.html").read_text(encoding="utf-8", errors="replace")
teacher_titles = set(re.findall(r'data-title="([^"]+)"', teacher_raw))
posts = []
for art in re.finditer(
    r'<article class="post-card" data-cat="([^"]*)" data-title="([^"]+)">[\s\S]*?<img src="([^"]+)"[\s\S]*?<p class="pc-ex">([^<]+)</p>[\s\S]*?<div class="pc-meta">(?:<b>[^<]*</b>)?<span>([^<]+)</span>',
    blog_list,
):
    cats = art.group(1).split()
    title = art.group(2).replace("&amp;", "&")
    if title in teacher_titles or any(t in title for t in ("Teacher", "Classroom", "Guess Words")):
        if "teachers" not in cats:
            cats.append("teachers")
    slug = re.sub(r"[^a-z0-9]+", "-", title.lower()).strip("-")[:80]
    date_s = art.group(5).strip()
    try:
        date_iso = __import__("datetime").datetime.strptime(date_s, "%B %d, %Y").strftime("%Y-%m-%d")
    except Exception:
        date_iso = "2026-10-01"
    posts.append(
        {
            "slug": slug,
            "title": title,
            "excerpt": art.group(4),
            "content": full_body if "Phonics Programs" in title else para_block(art.group(4)),
            "category": cats[0] if cats else "parents",
            "categories": cats,
            "image": art.group(3),
            "date": date_iso,
        }
    )

store = (HTML / "spark-store.html").read_text(encoding="utf-8", errors="replace")
prizes = []
store_parts = re.split(r'<div class="store-h" id="([^"]+)">', store)
for i in range(1, len(store_parts), 2):
    gid = store_parts[i]
    body = store_parts[i + 1] if i + 1 < len(store_parts) else ""
    hm = re.search(r"<h2>([^<]+)</h2>(?:<small>([^<]*)</small>)?", body)
    gname = hm.group(1) if hm else gid
    gdesc = (hm.group(2) if hm and hm.lastindex >= 2 else "") or ""
    for art in re.finditer(
        r'<article class="prize"><img src="([^"]+)"[^>]*>[\s\S]*?<h3>([^<]+)</h3>(?:<small>([^<]*)</small>)?<div class="pts"><b>([^<]+)</b></div>',
        body,
    ):
        prizes.append(
            {
                "group": gid,
                "group_name": gname,
                "group_desc": gdesc,
                "img": art.group(1),
                "title": art.group(2),
                "note": art.group(3) or "",
                "points": art.group(4),
            }
        )

lib = (HTML / "resource-library.html").read_text(encoding="utf-8", errors="replace")
resources = []
for art in re.finditer(
    r'<article class="res-card"><span class="kind">([^<]+)</span><h3>([^<]+)</h3><p>([^<]+)</p><p class="best"><b>Best for</b>([^<]+)</p><a class="btn[^"]*" href="([^"]+)"',
    lib,
):
    resources.append(
        {
            "kind": art.group(1),
            "title": art.group(2),
            "excerpt": art.group(3),
            "best_for": art.group(4),
            "file": art.group(5),
        }
    )

payload = {
    "pages": pages,
    "programs": programs,
    "posts": posts,
    "prizes": prizes,
    "resources": resources,
    "categories": ["phonics", "speaking", "academic", "ielts", "parents", "compare", "teachers"],
}
SEED.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

# sanity
html_blocks = sum(p["content"].count("<!-- wp:html") for p in pages + [{"content": pr["content"]} for pr in programs])
wp_html_pages = sum(1 for p in pages if "<!-- wp:html -->" in p["content"] and "iframe" not in p["content"])
print(
    f"Seed: {len(pages)} pages, {len(programs)} programs, {len(posts)} posts, {len(prizes)} prizes, {len(resources)} resources"
)
print(f"wp:html occurrences (pages+programs): {html_blocks}")
print("home starts:", pages[0]["content"][:180].replace("\n", " "))
print("about has card:", "sparkvn/card" in pages[1]["content"], "html?", "<!-- wp:html" in pages[1]["content"])
print("programs has query:", "query-programs" in next(p["content"] for p in pages if p["slug"] == "programs"))
print("programs has pf cards:", next(p["content"] for p in pages if p["slug"] == "programs").count('className":"pf"'))
print("store has query:", "query-prizes" in next(p["content"] for p in pages if p["slug"] == "spark-store"))
print("phonics has tabs:", "sparkvn/tabs" in next(p["content"] for p in programs if p["slug"] == "phonics"))
print("assessment has form:", "sparkvn/form" in next(p["content"] for p in pages if p["slug"] == "assessment"))
