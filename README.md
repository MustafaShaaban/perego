# Perego

The Perego website — a **Corex client site**.

- **App code:** `perego-site/` (`PeregoSite\`)
- **Theme:** `perego-theme/`
- **Specs / docs:** `specs/`, `docs/`
- **Workflow + agent rules:** [AGENTS.md](./AGENTS.md)

Edit only the client plugin/theme — never the Corex framework. Scaffold with `wp corex make:*`.

## Theme assets (`--starter`)

Source in `perego-theme/assets/src/{scss,js,images}/`; build to `assets/{css,js,images}/`:

```bash
cd perego-theme
npm install
npm run build      # styles (SCSS→CSS) + scripts (JS) + images (optimized + .webp)
```

`functions.php` enqueues the **compiled** output through the CoreX asset helpers — never hardcoded paths:

```php
\Corex\Assets\Style::enqueue('perego-theme-main', 'css/main.css', ['base' => 'perego-theme']);
\Corex\Assets\Script::enqueue('perego-theme-main', 'js/main.js', ['base' => 'perego-theme', 'in_footer' => true]);
echo \Corex\Assets\Image::picture('images/hero.jpg', ['alt' => 'Hero']);
```

**SCSS is source only** — never enqueue a `.scss`; enqueue the built `assets/css/` output. `Corex\Assets\*` is for
these source-controlled assets; `Corex\Media\*` is for Media Library uploads.
