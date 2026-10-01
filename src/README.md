# Theme core (`src/`)

The core of the theme: loading, assets, settings, modules, consent, updates and
the WP-CLI commands. The core always runs; it is never a module and cannot be
switched off. Template helpers from the base theme live in `inc/` instead.

## Loading

`functions.php` loads `loader.php`, and `loader.php` loads every file of the
theme by its path with `require_once`, in a fixed order:

1. `constants.php`: slug, version, paths, minimum versions and the URL of the
   update manifest (`CREATIONELL_WP_THEME_*`).
2. `functions-api.php`: the public functions for templates, child themes and
   plugins.
3. The classes of the folders below, then the template helpers of `inc/`.
4. `Core\Theme::boot()` registers the hooks. With PHP or WordPress too old it
   registers only an admin notice.

The theme has no autoloader. A new file is added to the list in `loader.php`;
a class that a Bootstrap line needs, and the libraries in `lib/`, load through
`Compiler\Compiler_Factory` only.

## Folders

| Folder | Namespace | Content |
|---|---|---|
| `core/` | `Core` | Boot, theme supports and template mode, requirements, capabilities, lock of the Site Editor styles |
| `admin/` | `Admin` | Admin notices, the admin menu of the theme and the page Import/Export |
| `assets/` | `Assets` | Front-end and editor assets, location of the compiled stylesheets, bridge to CreaBootstrapBlocks |
| `compiler/` | `Compiler` | Stylesheet compiler interface, compile request and result, fingerprint, license banner, atomic writes, individual stylesheet; `line-5/` holds the compiler of Bootstrap line 5 |
| `settings/` | `Settings` | Settings registry, definitions, values, the setter, sanitizer, contrast rules, fonts, Bootstrap line; `transfer/` holds import and export |
| `modules/` | `Modules` | Module catalog, manifest and state |
| `posts/` | `Posts` | Query arguments, card options, pagination, content guard and slider options of the post blocks; hook-free |
| `consent/` | `Consent` | Consent gate, categories, script marker and the JavaScript bridge |
| `child/` | `Child` | Headers of the active child theme and the child theme rules |
| `update/` | `Update` | Theme updater, update manifest and its error codes |
| `wpml/` | `Wpml` | WPML integration, language switcher, snapshots per language |
| `cli/` | `Cli` | WP-CLI commands below `wp creationell-theme` |

## Conventions

- Namespace `Creationell\WpTheme\<Area>`; class names with underscores, e.g.
  `Theme_Support`.
- One class per file, named after the class in lower case with hyphens:
  `class-` for classes (`core/class-theme-support.php`), `interface-` for
  interfaces, `enum-` for enums.
- Every file starts with its DocBlock, then `declare(strict_types=1);`, then
  `defined( 'ABSPATH' ) || exit;`.
- Every class, method, function, constant and hook has an English PHPDoc
  block with `@since`; the public API has an example.
- Nothing is translated before `init`: labels are closures with a literal
  `__()` call.
- Hooks, options, transients and constants start with `creationell_wp_theme_`
  or `CREATIONELL_WP_THEME_`.

## Public functions

`functions-api.php` holds the functions that templates, child themes and
plugins may call; they are not pluggable:

- `creationell_wp_theme_module_state( $slug )`: effective state of a module.
- `creationell_wp_theme_setting( $key )`: value of a theme setting.
- `creationell_wp_theme_bootstrap_line()`: active Bootstrap line.
- `creationell_wp_theme_consent_allowed( $category )`,
  `creationell_wp_theme_consent_categories()`,
  `creationell_wp_theme_consent_inline_script( $category, $javascript )`:
  the consent interface.
- `creationell_wp_theme_language_switcher( $args )`: markup of the language
  switcher.

```php
if ( 'active' === creationell_wp_theme_module_state( 'contact-form-7' ) ) {
	// The module runs.
}
```

Classes marked `@api` in their DocBlock are stable for child themes and
plugins; the other classes may change between versions.
