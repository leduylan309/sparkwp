#!/usr/bin/env python3
"""Rewrite Spark block markup so Gutenberg 7 save() matches stored HTML."""

from __future__ import annotations

import json
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parents[1]
THEME = ROOT / "wordpress/wp-content/themes/sparkvn"
PATTERNS = THEME / "patterns"
SEED = THEME / "inc/seed-content.json"

LIST_RE = re.compile(
    r"<!-- wp:list(\s+\{.*?\})? -->\s*<(ul|ol)([^>]*)>(.*?)</\2>\s*<!-- /wp:list -->",
    re.S,
)
JSON_ATTRS = r"\{(?:[^{}]|\{(?:[^{}]|\{[^{}]*\})*\})*\}"
BLOCK_OPEN_RE = re.compile(
    r"(<!-- wp:([a-z0-9-]+(?:/[a-z0-9-]+)?)(\s+" + JSON_ATTRS + r")? -->)(\s*)(<([a-z0-9]+)([^>]*?)>)",
    re.S | re.I,
)
SIMPLE_SPACING = re.compile(
    r"^(margin-top|margin-bottom|padding-top|padding-bottom):(\d*\.?\d+(?:px|rem|em))$"
)


def split_lis(inner: str) -> list[str]:
    items: list[str] = []
    i = 0
    lower = inner.lower()
    while True:
        start = lower.find("<li", i)
        if start == -1:
            break
        gt = inner.find(">", start)
        if gt == -1:
            break
        depth = 1
        pos = gt + 1
        while pos < len(inner) and depth:
            nxt_open = lower.find("<li", pos)
            nxt_close = lower.find("</li>", pos)
            if nxt_close == -1:
                break
            if nxt_open != -1 and nxt_open < nxt_close:
                depth += 1
                pos = nxt_open + 3
            else:
                depth -= 1
                pos = nxt_close + 5
                if depth == 0:
                    items.append(inner[start:pos].strip())
                    i = pos
                    break
        else:
            break
    return items


def ul_classes(attrs: str, class_name: str) -> str:
    m = re.search(r'\bclass="([^"]*)"', attrs)
    existing = m.group(1).split() if m else []
    extra = class_name.split() if class_name else []
    out: list[str] = []
    for token in ["wp-block-list", *extra, *existing]:
        if token and token not in out:
            out.append(token)
    return " ".join(out)


def rewrite_list(match: re.Match[str]) -> str:
    json_raw = (match.group(1) or "").strip()
    tag = (match.group(2) or "ul").lower()
    ul_attrs = match.group(3) or ""
    inner = match.group(4) or ""
    if "wp:list-item" in inner:
        return match.group(0)
    attrs: dict = {}
    if json_raw:
        try:
            attrs = json.loads(json_raw)
        except json.JSONDecodeError:
            attrs = {}
    class_name = str(attrs.get("className") or "")
    classes = ul_classes(ul_attrs, class_name)
    extras = [c for c in classes.split() if c != "wp-block-list"]
    if extras:
        attrs["className"] = " ".join(extras)
    elif "className" in attrs:
        del attrs["className"]
    if tag == "ol":
        attrs["ordered"] = True
    items = split_lis(inner)
    if not items:
        return match.group(0)
    wrapped = "".join(
        f"<!-- wp:list-item -->\n{item}\n<!-- /wp:list-item -->" for item in items
    )
    comment = "<!-- wp:list -->" if not attrs else "<!-- wp:list " + json.dumps(attrs, separators=(",", ":")) + " -->"
    return f'{comment}\n<{tag} class="{classes}">{wrapped}</{tag}>\n<!-- /wp:list -->'


def spacing_from_style(style: str) -> dict | None:
    parts = [p.strip() for p in style.split(";") if p.strip()]
    if len(parts) != 1:
        return None
    m = SIMPLE_SPACING.match(parts[0])
    if not m:
        return None
    prop, value = m.group(1), m.group(2)
    side = prop.split("-")[1]
    key = "margin" if prop.startswith("margin") else "padding"
    return {"spacing": {key: {side: value}}}


def merge_json(raw: str | None, extra: dict) -> str:
    data: dict = {}
    if raw:
        try:
            data = json.loads(raw.strip())
        except json.JSONDecodeError:
            data = {}
    extra = extra or {}
    style = data.get("style") if isinstance(data.get("style"), dict) else {}
    spacing = style.get("spacing") if isinstance(style.get("spacing"), dict) else {}
    extra_spacing = extra.get("spacing") or {}
    for key, sides in extra_spacing.items():
        current = spacing.get(key) if isinstance(spacing.get(key), dict) else {}
        current.update(sides)
        spacing[key] = current
    if spacing:
        style["spacing"] = spacing
        data["style"] = style
    return json.dumps(data, separators=(",", ":"))


def strip_clamp_from_json(raw: str | None) -> str | None:
    if not raw:
        return raw
    try:
        data = json.loads(raw.strip())
    except json.JSONDecodeError:
        return raw.strip()
    style = data.get("style")
    if not isinstance(style, dict):
        return raw
    dumped = json.dumps(style)
    if "clamp(" not in dumped and "var(" not in dumped:
        return raw
    data.pop("style", None)
    if not data:
        return None
    return json.dumps(data, separators=(",", ":"))


def comment_json(raw: str | None) -> str:
    if not raw:
        return ""
    cleaned = raw.strip()
    return f" {cleaned}" if cleaned.startswith("{") else ""


def rewrite_open(match: re.Match[str]) -> str:
    _comment, name, json_raw, ws, _tag, tag_name, tag_attrs = match.groups()
    json_raw = strip_clamp_from_json(json_raw)
    style_m = re.search(r'\sstyle="([^"]*)"', tag_attrs)
    if not style_m:
        return f"<!-- wp:{name}{comment_json(json_raw)} -->{ws}<{tag_name}{tag_attrs}>"

    style = style_m.group(1)
    spacing = spacing_from_style(style)
    new_attrs = re.sub(r'\sstyle="[^"]*"', "", tag_attrs)
    if spacing:
        merged = merge_json(json_raw, spacing)
        new_tag = f'<{tag_name}{new_attrs} style="{style}">'
        return f"<!-- wp:{name} {merged} -->{ws}{new_tag}"
    return f"<!-- wp:{name}{comment_json(json_raw)} -->{ws}<{tag_name}{new_attrs}>"


def rewrite_table(html: str) -> str:
    html = html.replace(
        '<figure class="wp-block-table tbl"><div class="tbl-wrap"><table class="tbl">',
        '<figure class="wp-block-table tbl"><table>',
    )
    html = html.replace("</table></div></figure>", "</table></figure>")
    return html


def rewrite_rm_track(html: str) -> str:
    return html.replace(
        '<!-- wp:group {"className":"rm-track"} -->\n<div class="wp-block-group rm-track" style="grid-template-columns:repeat(4,1fr)">',
        '<!-- wp:group {"className":"rm-track cols-4"} -->\n<div class="wp-block-group rm-track cols-4">',
    )


def transform(html: str) -> str:
    html = rewrite_rm_track(html)
    html = rewrite_table(html)
    html = LIST_RE.sub(rewrite_list, html)
    html = BLOCK_OPEN_RE.sub(rewrite_open, html)
    return html


def split_php(text: str) -> tuple[str, str]:
    idx = text.find("?>")
    if idx == -1:
        return "", text
    return text[: idx + 2], text[idx + 2 :]


def fix_patterns() -> int:
    changed = 0
    for path in sorted(PATTERNS.glob("*.php")):
        original = path.read_text()
        header, body = split_php(original)
        updated = header + transform(body)
        if updated != original:
            path.write_text(updated)
            changed += 1
            print(f"pattern {path.name}")
    return changed


def fix_seed() -> bool:
    if not SEED.exists():
        return False
    data = json.loads(SEED.read_text())
    changed = False

    def walk(node):
        nonlocal changed
        if isinstance(node, dict):
            if isinstance(node.get("content"), str):
                nxt = transform(node["content"])
                if nxt != node["content"]:
                    node["content"] = nxt
                    changed = True
            for value in node.values():
                walk(value)
        elif isinstance(node, list):
            for item in node:
                walk(item)

    walk(data)
    if changed:
        SEED.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n")
        print("seed-content.json")
    return changed


def main() -> int:
    if len(sys.argv) > 1 and sys.argv[1] == "--stdin":
        sys.stdout.write(transform(sys.stdin.read()))
        return 0
    n = fix_patterns()
    seed = fix_seed()
    print(f"updated {n} patterns; seed={'yes' if seed else 'no'}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
