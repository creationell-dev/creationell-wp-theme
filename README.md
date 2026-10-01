# creationell Theme

**Version:** 0.1.0

**Requires at least:** 7.1

**Tested up to:** 7.1

**Requires PHP:** 8.3

Classic WordPress theme of creationell with Bootstrap 5, based on
[Bootscore](https://bootscore.me/) (MIT). PHP templates render the pages; the
block editor and the Bootstrap blocks of creationell work in the content area.
Projects keep their changes in a child theme, so the theme itself can be
updated.

## Requirements

- WordPress 7.1 or later
- PHP 8.3 or later
- Recommended: the plugin CreaBootstrapBlocks for the Bootstrap blocks in the
  content area (see [CreaBootstrapBlocks](#creabootstrapblocks))

## Installation

1. Upload the theme folder `creationell-wp-theme` to `wp-content/themes/`.
2. Upload a child theme next to it. The child theme template
   `creationell-wp-child-theme` ships separately. Copy it and
   keep the folder `creationell-wp-child-theme` in every project; write the
   project name into its `Theme Name` only.
3. Activate the child theme under Appearance > Themes. WordPress then uses this
   theme as the parent theme.

The folder name `creationell-wp-theme` must not change: it is the slug that
child themes and updates refer to. Activating the theme stores no options;
every function starts with its default.

## Child theme

A child theme holds everything that belongs to one project: logos, project
styles, template overrides and the defaults and locks of the theme functions.
Its `style.css` keeps `Template: creationell-wp-theme`, `Update URI: false` and
the header `Bootstrap Lines`, which names the Bootstrap major versions its
project styles support. The README of the child theme template lists what a
child theme may and may not change; `wp creationell-theme child check` checks a
child theme against these rules.

A child theme sets defaults and locks through two filters in its
`functions.php`:

- `creationell_wp_theme_modules`: the state of each module, as `default` (the
  site may change it) or `locked` (the site cannot).
- `creationell_wp_theme_settings`: defaults and locks of the theme settings in
  the same form.

```php
add_filter(
	'creationell_wp_theme_modules',
	static function ( mixed $modules ): mixed {
		$modules = is_array( $modules ) ? $modules : array();
		$modules['woocommerce'] = array( 'locked' => 'off' );
		return $modules;
	}
);
```

The logos in `assets/img/logo/` and `screenshot.png` are placeholders. A
child theme replaces the logos with files of the same name in its
`assets/img/logo/` and shows its own `screenshot.png`; `assets/README.md`
names the filters for another logo URL and size.

To override a template, copy it from this theme to the same path in the child
theme, for example `template-parts/header/main-menu.php`, and change the copy.
Prefer a filter or an action of the theme where one exists: a copied template
does not receive later fixes of the original.

## Modules

Optional functions of the theme are modules: blocks, integrations with plugins,
the cookie banner. Every module is `off` until a site or its child theme
switches it on. `wp creationell-theme module list` shows the state of every
module and where it comes from. The order of precedence, lowest first: the
default `off`, the default of the child theme, the state switched in the
backend, the lock of the child theme and the constant
`CREATIONELL_WP_THEME_MODULE_<SLUG>` in `wp-config.php`, for example:

```php
define( 'CREATIONELL_WP_THEME_MODULE_CONTACT_FORM_7', 'active' );
```

`modules/README.md` lists the modules, their states and the folder convention.

## Where to set what

One place per setting; editors change texts and, with the module
`header-footer`, header and footer, administrators the rest.

| What | Where | Who |
|---|---|---|
| Header and footer (layout, blocks, order) | Module `header-footer` active: **Header & Footer** in the admin menu, which opens the parts in the Site Editor; module `off`: the PHP header and footer of the theme, changed only in a child theme | editors and administrators; a child theme can keep them with the administrators |
| Footer text, contact data, social media links | Theme settings > Texts, per language with WPML | editors and administrators; a child theme can keep them with the administrators (`revoke['basic']`) |
| Menus of header and footer | Appearance > Menus (with the module: Header & Footer > Menus) | administrators; editors only with the module active and the menus unlocked by the child theme, and then only these two menus |
| Colors and fonts | Theme settings > Design; never in the Site Editor (its styles have no effect) | administrators; editors when the child theme unlocks the design |
| Modules | Theme settings > Modules | administrators; editors when the child theme unlocks the modules |

## Updates

The theme updates itself from the update manifest named by its `Update URI`
header, not from wordpress.org. WordPress shows a new version on the updates
screen like any other theme update; the theme checks the package (https,
checksum, requirements, Bootstrap line) before WordPress installs it.
Automatic updates of the theme are locked: an administrator installs every
update by hand, after a backup and a look at the changelog.

`wp creationell-theme update status` shows the installed and the offered
version and why no update is offered. A test site may predefine
`CREATIONELL_WP_THEME_MANIFEST_URL` in `wp-config.php` with another https URL;
administrators then see a notice that the site uses a test manifest.

The theme uses the Bootstrap line 5 (Bootstrap 5). The constant
`CREATIONELL_WP_THEME_BOOTSTRAP_LINE` holds a line for a site; a line that this
theme version does not include keeps line 5, and `wp creationell-theme doctor`
names the reason.

## Translations

The texts of the theme are English, with the text domain
`creationell-wp-theme`. The folder `languages/` holds the template
`creationell-wp-theme.pot` and German translations in two forms of address:
`de_DE` (informal) and `de_DE_formal` (formal). WordPress loads them when the
site language is one of these. Further languages are translated per project
with WPML; `wpml-config.xml` tells WPML which theme data to translate.

## Accessibility

The templates aim at WCAG 2.2 level AA: labelled forms, named landmarks and
dialogs, skip links, menu items with a submenu as a link plus a toggle button,
visible focus rings that the sticky header does not hide, colors with a
contrast of at least 4.5:1, and inline icons that assistive technology skips
or names. The design settings refuse color pairs below the required contrast.
In the dark color mode (`data-bs-theme="dark"` on the page or on a part of it)
the focus ring uses the primary color, lightened where needed to reach 3:1 on
the dark page and on its tertiary background; a child theme can set
`$focus-ring-color-dark` in `_project-variables.scss`.

## CreaBootstrapBlocks

The theme loads Bootstrap for the Bootstrap blocks of the plugin
CreaBootstrapBlocks. While the theme is active it defines the constants
`CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_CSS`, `CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_JS` and
`CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_ICONS` as `false`, so the plugin loads no second
Bootstrap. A constant that `wp-config.php` sets to `true` stays, and
administrators see a notice; so does a Bootstrap version of the plugin that
differs from the one of the theme. The theme stylesheet and the Bootstrap bundle
load before the assets of the plugin. The Bootstrap Icons font loads with the
plugin active or when the filter `creationell_wp_theme_load_icon_font` returns
`true`.

### Leaving the theme

After switching to another theme, switch the Bootstrap stylesheet, script and
icons on again in the settings of CreaBootstrapBlocks, unless the new theme
loads Bootstrap itself.

## Import and export of settings

The page *Theme settings > Import/Export* exports the theme settings and the
module states as a JSON file, per language on WPML sites, and imports such a
file after a preview that lists every value with its result. Before every
import and restore the theme backs up the current settings; it keeps the last
five backups, and each one can be restored the same way. A log keeps who
exported, imported, restored or backed up which sections for 90 days, without
values.

- Opening the page needs the capability to view the advanced settings; export,
  import, restore and "Back up now" need `creationell_wp_theme_import_export`.
  Other users see the backups only.
- Sensitive values are never exported. The Bootstrap line, capabilities and
  unlocks are never imported. Every value goes through the same checks as the
  settings pages (type, locks, contrast); rejected values stay as they are and
  the result lists them.
- Switching a module that published contents use needs an explicit
  confirmation in the preview.
- Files have at most 1 MB and end in `.json`; nothing is stored in the media
  library.

## Command line

The theme adds the WP-CLI command `wp creationell-theme`. The commands below
only read; each one has `--format=json` for scripts.

| Command | Shows |
|---|---|
| `wp creationell-theme doctor` | Health of the theme: version and requirements, update manifest, Bootstrap line, CreaBootstrapBlocks, child theme, consent, WPML; problems as warnings |
| `wp creationell-theme module list` | Every module with its state and the reason |
| `wp creationell-theme module status <slug>` | One module in detail |
| `wp creationell-theme child check [<path>]` | Findings of a child theme against the child theme rules |
| `wp creationell-theme update status` | Installed and offered version, manifest state |
| `wp creationell-theme wpml status` | State of the WPML integration |

## For developers

- `src/README.md`: the theme core in `src/` (loading, naming, public
  functions such as `creationell_wp_theme_module_state()` and
  `creationell_wp_theme_setting()`).
- `inc/README.md`: the template helpers in `inc/` and their filters.
- `assets/README.md`: stylesheets, scripts, images and vendored libraries.
- `modules/README.md`: the module catalog and the folder convention.

Every function, class, method and hook of the theme has a PHPDoc block in
English; `phpdoc.xml` builds the API documentation with phpDocumentor 3.

## Files

- `style.css`: theme header; `Version` is the only version source.
- `functions.php`: loads `src/loader.php`, which loads the theme core in `src/`
  and the template helpers in `inc/`.
- `theme.json`: editor settings; the color palette refers to the Bootstrap
  custom properties.
- PHP templates in the theme root, `template-parts/`, `page-templates/` and
  `single-templates/`; block patterns in `patterns/`.
- `assets/css/bootstrap-5/theme.min.css`: compiled stylesheet (Bootstrap 5 and
  the theme styles from `assets/scss/`); `root-vars.min.css`: its root custom
  properties for the editor screens; `build.json`: Bootstrap line, fingerprint
  and component versions of the build.
- `assets/js/theme.js`: theme script; `assets/vendor/`: vendored Bootstrap,
  Bootstrap Icons and further libraries with their licenses.
- `assets/img/logo/`: placeholder logos for the light and the dark color
  scheme; `screenshot.png`: placeholder preview for Appearance > Themes.
- `lib/`: PHP libraries of the stylesheet compiler, in their own namespace.
- `languages/`: translation template and German translations.
- `modules/`: the theme modules.

## License

GPL-2.0-or-later, see `LICENSE`. Licenses of the included third-party code:
`THIRD-PARTY-NOTICES`.

## Changelog

### 0.1.0

- First development version: installable theme with the compiled Bootstrap 5
  stylesheet, the Bootstrap bundle and the theme script.
- CreaBootstrapBlocks runs on the Bootstrap of the theme; Bootstrap Icons font,
  child theme stylesheet and editor canvas styles.
- Accessibility: labelled forms, named landmarks and dialogs, menu items with
  submenu as link plus toggle button, visible focus rings, colours with at
  least 4.5:1, a real top button, focus kept clear of the sticky header;
  inline icons from Bootstrap Icons; patterns without external images.
- Hardening: core HTML filters for term and author descriptions stay, links
  in comments are no longer rewritten, no shortcodes in menu titles.
- Stylesheet rebuilt after the update of the CSS parser to 9.5.0: same rules,
  shorter output; the new fingerprint makes installed sites build their
  individual stylesheet once. Child theme SCSS: the parser now accepts
  `@container`, `@layer` and the modern colour syntax; an IE hack such as
  `width: 100px\9` is no longer rejected but compiles to a tab character.
