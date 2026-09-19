#!/usr/bin/env python3
"""Regenerate the WHMCS child theme's stylesheet from index.html.

index.html is the source of truth for the design system. This script lifts its
<style> block and namespaces it so it can live alongside Bootstrap 4 inside
WHMCS: every custom property becomes --hd-*, every class becomes .hd-*, and
every element selector is scoped under .hd-root.

Run from the repo root after changing index.html's styles:
    python3 tools/regen-theme-css.py
"""
import re
import pathlib

ROOT = pathlib.Path(__file__).resolve().parent.parent
SRC = ROOT / 'index.html'
DEST = ROOT / 'whmcs-theme/templates/hostdigi/assets/css/hostdigi.css'

ELEMENTS = {'html', 'body', 'a', 'img', 'svg', 'button', 'input', 'select', 'textarea',
            'table', 'th', 'td', 'thead', 'tbody', 'tr', 'h1', 'h2', 'h3', 'h4', 'p',
            'ul', 'li', 'footer', 'main', 'section', 'nav', 'form', 'i', 'b', 'em',
            'small', 'span', 'div', 'article', 'label'}

HEADER = """/* ==========================================================================
   Hostdigi child theme - namespaced design system
   --------------------------------------------------------------------------
   GENERATED FILE - do not edit by hand.
   Source: index.html <style> block. Regenerate with:
       python3 tools/regen-theme-css.py

   Every selector is scoped under .hd-root and every class carries an "hd-"
   prefix, so nothing here collides with Bootstrap 4 (which defines card, btn
   and badge) or with Twenty-One's own styles. Custom properties are --hd-*
   for the same reason.
   ========================================================================== */
"""


def namespace(css):
    css = re.sub(r'--(?!hd-)([a-z0-9][a-z0-9-]*)', r'--hd-\1', css)
    ns = lambda t: re.sub(r'\.(?!hd-)([a-zA-Z][a-zA-Z0-9_-]*)', r'.hd-\1', t)

    token = re.compile(r'([^{}]+)(\{)|(\})', re.S)
    depth, pos, out = 0, 0, ''
    for m in token.finditer(css):
        if m.group(3):                                    # closing brace
            out += css[pos:m.end()]
            pos, depth = m.end(), depth - 1
            continue
        sel = m.group(1)
        out += css[pos:m.start()]
        pos = m.end()
        if sel.strip().startswith('@'):                   # at-rule: pass through
            out += sel + '{'
            depth += 1
            continue
        parts = []
        for part in (p.strip() for p in sel.split(',')):
            if not part:
                continue
            part = ns(part)
            if part.startswith('body'):
                part = '.hd-root' + part[4:]
            elif part.startswith(('html', ':root')):
                pass                                      # token blocks, theme scopes
            elif part.startswith(('::selection', ':focus-visible', '*')):
                part = '.hd-root ' + part
            else:
                first = re.split(r'[\s>+~]', part)[0]
                bare = re.match(r'^([a-zA-Z][a-zA-Z0-9]*)', first)
                if bare and bare.group(1) in ELEMENTS:
                    part = '.hd-root ' + part
            parts.append(part)
        out += ',\n'.join(parts) + '{'
        depth += 1
    return out + css[pos:]


def main():
    css = re.search(r'<style>(.*?)</style>', SRC.read_text(), re.S).group(1)
    DEST.write_text(HEADER + namespace(css))
    print(f'wrote {DEST.relative_to(ROOT)} ({DEST.stat().st_size} bytes)')


if __name__ == '__main__':
    main()
