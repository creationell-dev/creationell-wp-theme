# Assets (`assets/`)

Stylesheets, scripts, images and vendored libraries of the theme. The theme
needs no build step on the site: the compiled files ship with it.

| Path | Content | Edit |
|---|---|---|
| `scss/` | Theme styles: `theme.scss` imports Bootstrap and the theme partials `_theme-*.scss` and `theme/*.scss`; `child-defaults/` holds empty stand-ins for the project SCSS of a child theme | yes |
| `css/bootstrap-5/` | Compiled from `scss/` for Bootstrap line 5: `theme.min.css`, `root-vars.min.css` (the root custom properties for the editor screens) and `build.json` (line, fingerprint and component versions of the build) | no, rebuilt |
| `css/editor-canvas.css` | Styles of the block editor canvas, written by hand | yes |
| `js/theme.js` | Theme script: plain JavaScript without a build step and without jQuery; texts come from PHP as attributes | yes |
| `js/consent-bridge.js` | Browser side of the consent interface, printed inline by the theme | yes |
| `img/` | `logo/logo.svg` and `logo/logo-theme-dark.svg` (placeholder logos a child theme replaces, see [Logos](#logos)), `patterns/` (placeholder images of the patterns) | yes |
| `vendor/` | Bootstrap (`bootstrap-5/`), Bootstrap Icons (`bootstrap-icons/`) and the cookie consent library (`cookieconsent/`), each with `VERSION`, `UPSTREAM`, `CHECKSUMS.txt` and `LICENSE` | no, synced |

## Rules

- Styles use logical properties (`margin-inline-start`, `padding-block`,
  `inset-inline-end`) instead of left and right, so that right-to-left
  languages need no second set of rules.
- Colors and focus rings keep a contrast of at least 4.5:1 for text and 3:1
  for focus rings and borders.
- The compiled files in `css/bootstrap-5/` and the files in `vendor/` are never
  edited by hand. The first come from the stylesheet compiler of the theme, the
  second from their upstream releases; `CHECKSUMS.txt` records every file.
- A site with its own design settings gets an individual stylesheet in the
  uploads folder; the files here stay the default.

## Logos

`img/logo/logo.svg` (light color scheme) and `img/logo/logo-theme-dark.svg`
(dark color scheme) are placeholders: a monogram in the primary color of the
theme, square like the header image of 30 x 30 pixels. The header loads them
with `get_theme_file_uri()`, so files of the same name in `assets/img/logo/` of
the child theme replace them without further code. The filter
`creationell_wp_theme_logo` sets another URL per variant (`default`,
`theme-dark`); `creationell_wp_theme_logo_width` and
`creationell_wp_theme_logo_height` set the size of the image, for example for a
wide word mark. The header and the logo block take the alternative text of the
image from the site name, so a logo file needs no title of its own. Without
logo files the logo block prints the site name.

`screenshot.png` in the theme root (1200 x 900 pixels) is a placeholder too;
the child theme shows its own screenshot in Appearance > Themes.

## Loading order

On the front end the theme enqueues the compiled stylesheet (`theme.min.css`
or the individual stylesheet of the site) as `creationell-wp-theme-main`, the
Bootstrap bundle from `vendor/bootstrap-5/js/` as
`creationell-wp-theme-bootstrap` and `js/theme.js` as
`creationell-wp-theme-script`, before the assets of CreaBootstrapBlocks. The
Bootstrap Icons font loads only with CreaBootstrapBlocks active or on request
of the filter `creationell_wp_theme_load_icon_font`; the templates use inline
SVG icons.
