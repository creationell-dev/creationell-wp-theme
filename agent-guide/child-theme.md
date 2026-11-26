# Child theme

The child theme holds everything of one project; the parent theme stays untouched, so it can be updated. This file is the contract of the child theme: what it may hold, what it must not do, and how it sets defaults. The child theme is not part of the theme package.

## No child theme yet

If the project has no child theme, ask the human for the child theme package of the project; do not create one yourself. Use the scaffold in the [appendix](#appendix-minimal-child-theme) only if the human asks you to create one.

## Folder and header

- The folder is always `creationell-wp-child-theme`, in every project: menus, template parts and other data of WordPress belong to the folder name. The name of the project goes into the header `Theme Name` only.
- The header of `style.css` keeps `Template: creationell-wp-theme`, `Update URI: false`, `Bootstrap Lines: 5` and `Text Domain: creationell-wp-child-theme`.
- `Bootstrap Lines` lists the Bootstrap lines (major versions) the project styles support, comma-separated, for example `5`. List a further line only once the installed parent theme includes it; the section `bootstrap_line` of `wp creationell-theme doctor` lists the available lines. The parent theme uses the project styles only for a declared line.
- Source texts of the child theme are English and use the text domain `creationell-wp-child-theme`, for example `__( 'Read more', 'creationell-wp-child-theme' )`.

## Allowed paths

A child theme may hold these paths and nothing else:

- `style.css`: the header; styles belong in the project SCSS.
- `functions.php`: the four filters below and short adjustments through other filters and actions of the parent theme.
- `README.md` and `LICENSE`.
- `screenshot.png`, or the same name as `.jpg`, `.jpeg` or `.webp`.
- `theme.json` without color palette, font families and font sizes.
- `assets/img/logo/`: logos as `.svg`, `.png`, `.jpg`, `.jpeg` or `.webp` files. Only `assets/img/logo/logo.svg` (light) and `assets/img/logo/logo-theme-dark.svg` (dark) replace the logos of the parent theme; a file with another name or format shows only through the filter [`creationell_wp_theme_logo`](reference/hooks-modules.md#creationell_wp_theme_logo).
- `assets/scss/`: `.scss` files, also in subfolders ([styles-and-scss.md](styles-and-scss.md)).
- `assets/fonts/`: `.woff2` files directly in the folder, with names of letters, digits, `-`, `_` and `.`.
- `assets/fonts/README.md`.
- `parts/header.html` and `parts/footer.html`; the parent theme uses them while the module `header-footer` is active.
- Template overrides: a template that the parent theme has at the same path. Templates are the PHP files in the theme root (except `functions.php`), in `template-parts/`, in `page-templates/` and in `single-templates/`.

Override a template only at the path the parent theme uses: `single.php` covers posts. The parent theme has no `single-post.php`, so `wp creationell-theme child check` rejects one. Every template with its hooks: [reference/templates.md](reference/templates.md).

Anything with files or logic of its own goes into a project plugin, not into the child theme.

## Rules

`wp creationell-theme child check` reports each finding with the id of its rule:

| Rule | Forbidden |
|---|---|
| `update-uri-false` | an `Update URI` header other than `false` |
| `template-slug` | a `Template` header other than `creationell-wp-theme` |
| `lines-declared` | a missing `Bootstrap Lines` header, or a line the parent theme does not include |
| `no-block-theme` | `templates/index.html` or `block-templates/index.html`: they turn the child into a block theme |
| `no-token-presets` | `settings.color.palette`, `settings.typography.fontFamilies` or `settings.typography.fontSizes` in `theme.json`, also per block: these tokens come from the parent theme |
| `no-new-settings` | options pages and settings of its own: `acf_add_options_page()`, `acf_add_local_field_group()`, `register_setting()`, `add_option()` |
| `no-updater` | hooks of theme updates: `upgrader_pre_download`, `update_themes_*`, `site_transient_update_*` |
| `no-blocks` | a `blocks/` folder, `block.json` files, `register_block_type()`, `register_block_type_from_metadata()`: blocks belong in a plugin |
| `no-roles` | `add_role()`, `add_cap()`, `remove_cap()`: role changes stay in the database after a theme switch |
| `allowed-paths` | any path not listed under [Allowed paths](#allowed-paths) |

The nine forbidden calls are the functions of `no-new-settings`, `no-blocks` and `no-roles`. The check reads files only, runs no code of the child theme and skips comments in PHP code.

## The four filters

The `functions.php` of the child theme sets the defaults of the project through four filters of the parent theme. Each callback gets the value so far and returns it with the changes of the project.

| Filter | Sets |
|---|---|
| [`creationell_wp_theme_modules`](reference/hooks-core.md#creationell_wp_theme_modules) | per module slug a default `array( 'default' => 'active' )` or a lock `array( 'locked' => 'off' )`; states `active`, `hidden`, `off` ([reference/modules.md](reference/modules.md)) |
| [`creationell_wp_theme_settings`](reference/hooks-core.md#creationell_wp_theme_settings) | per setting key a default `array( 'default' => '#0b5ed7' )` or a lock `array( 'locked' => 'system-serif' )` ([reference/settings.md](reference/settings.md)) |
| [`creationell_wp_theme_capabilities`](reference/hooks-core.md#creationell_wp_theme_capabilities) | the rights of editors: `unlock`, `revoke`, `restricted_admins` and `locked` ([Rights of editors](#rights-of-editors)) |
| [`creationell_wp_theme_font_catalog`](reference/hooks-core.md#creationell_wp_theme_font_catalog) | project fonts for the design page, with `.woff2` faces in `assets/fonts/` ([styles-and-scss.md](styles-and-scss.md)) |

```php
add_filter(
	'creationell_wp_theme_modules',
	static function ( $modules ) {
		$modules = is_array( $modules ) ? $modules : array();
		// Default: the site starts with the blog on; the backend may switch it off.
		$modules['blog'] = array( 'default' => 'active' );
		// Lock: the project has no shop; only a constant can change this.
		$modules['woocommerce'] = array( 'locked' => 'off' );
		return $modules;
	}
);

add_filter(
	'creationell_wp_theme_settings',
	static function ( $settings ) {
		$settings = is_array( $settings ) ? $settings : array();
		$settings['color_primary'] = array( 'default' => '#0b5ed7' );
		$settings['font_headings'] = array( 'locked' => 'system-serif' );
		return $settings;
	}
);
```

A module that needs a plugin or another module stays off without it, whatever its default: `post-filter` needs the plugin JPKCom Post Filter and the module `blog`.

## Precedence

A setting value, from low to high: theme default < child default < value saved in the backend < child lock < constant `CREATIONELL_WP_THEME_<KEY>` in `wp-config.php`.

A module state, from low to high: default `off` < child default < value saved in the backend < child lock < constant `CREATIONELL_WP_THEME_MODULE_<SLUG>`.

The highest layer that is set wins. A locked value shows read-only on the settings pages and a saved value no longer counts. An invalid setting value is skipped with a notice for developers; an invalid module state turns the module off (`invalid_state`). Colors need the contrast of WCAG 2.2 AA; the settings pages reject colors below it.

## Module states

`wp creationell-theme module list` and `wp creationell-theme module status <slug>` show for each module:

- `configured`: the state of the highest layer that is set.
- `effective`: the state that runs; a check below can turn a configured state off.
- `origin`: the winning layer: `default`, `child`, `backend`, `locked` or `constant`.
- `reason`: the winning layer, or why the module is off.

| Reason | Meaning |
|---|---|
| `default`, `child`, `backend`, `locked`, `constant` | the layer that set the state |
| `invalid_state` | the winning layer holds a value that is no state of the module; the module is off |
| `invalid_backend` | the value saved in the backend is no state of the module and was skipped |
| `not_installed` | the module has no folder in this theme version |
| `unknown_module` | the slug is no module of the theme |
| `invalid_manifest` | the manifest of the module is broken |
| `legacy_plugin:<path>` | an older plugin that the module replaces is active; deactivate it |
| `missing_plugin:<path>` | a plugin the module needs is not active |
| `missing_blockstudio` | the module needs Blockstudio, which is not loaded |
| `missing_module:<slug>` | a module this module needs is off |
| `dependency_cycle` | the module needs itself through other modules |

A plugin counts when it is active on the site or, in a network, for the whole network. All modules with their slugs and states: [reference/modules.md](reference/modules.md).

## Rights of editors

The filter `creationell_wp_theme_capabilities` sets what editors may do by default. It changes no role and stores nothing.

- `unlock` opens an area of the administrators to editors: `design`, `modules`, `import_export`, `menus`, `consent`.
- `revoke` takes an area of the editors back to the administrators: `basic` (the texts), `template_parts` (header and footer in the Site Editor, with their menus).
- `restricted_admins` lists the logins of administrators who may use only the unlocked areas.
- `locked` lists the entries that keep the value of the child theme.

An administrator may change every entry that is not locked under Theme settings > Rights. The Bootstrap line is never opened to editors. Import and export never carry rights.

```php
add_filter(
	'creationell_wp_theme_capabilities',
	static function ( $caps ) {
		$caps = is_array( $caps ) ? $caps : array();
		$caps['unlock']['menus'] = true;
		// Keep this value, so the rights page cannot change it.
		$caps['locked'][] = 'menus';
		return $caps;
	}
);
```

## Overrides

Prefer a filter or an action of the parent theme ([reference/hooks.md](reference/hooks.md)); a copied template misses later fixes of the original. To override, copy the template of the installed parent theme to the same path in the child theme and change the copy.

With the module `blog` on, the blog parts hang on calls in the templates. An override keeps these calls at the same places, or the parts are missing:

| Own file in the child theme | Keeps |
|---|---|
| `single.php`, `single-templates/*.php` | 5 calls: `do_action( 'creationell_wp_theme_entry_header_open', $context )` after `the_post()`, `creationell_wp_theme_category_badge( $context )`, `if ( ! creationell_wp_theme_entry_meta( $context ) )` around its own meta line, `if ( ! creationell_wp_theme_entry_terms( $context ) )` around its tags (as in `single.php`, so the own line and tags print only while the module prints none) and `do_action( 'creationell_wp_theme_after_entry_terms', $context )` after them |
| `sidebar.php` | 3 hooks: the filters `creationell_wp_theme_blog_sidebar_force` and `creationell_wp_theme_sidebar_aria_label`, the action `creationell_wp_theme_before_sidebar_widgets` |
| `404.php` | 1 hook: `do_action( 'creationell_wp_theme_404_after_content', '404' )` after the widget area |

`$context` is the name of the template, for example `single` or `single-sidebar-left`. The hooks: [entry_header_open](reference/hooks-templates.md#creationell_wp_theme_entry_header_open), [after_entry_terms](reference/hooks-templates.md#creationell_wp_theme_after_entry_terms), [blog_sidebar_force](reference/hooks-templates.md#creationell_wp_theme_blog_sidebar_force), [sidebar_aria_label](reference/hooks-templates.md#creationell_wp_theme_sidebar_aria_label), [before_sidebar_widgets](reference/hooks-templates.md#creationell_wp_theme_before_sidebar_widgets), [404_after_content](reference/hooks-templates.md#creationell_wp_theme_404_after_content). While the module `blog` is active, `wp creationell-theme doctor` names in its section `blog` every overriding single template, `sidebar.php` and `404.php` of the child theme with the calls it must keep. It does not read the files, so the warning stays after a correct override: check the calls yourself. More in [blog.md](blog.md).

## Check

Run `wp creationell-theme child check` after every change of the child theme; it reads only. Without a path it checks the active child theme. It prints one row per finding and ends with exit code 1; a clean child theme gives `Success: 0 violations.`

## Appendix: minimal child theme

Use this only if the human asks you to create one; otherwise ask for the child theme package of the project. The folder is `creationell-wp-child-theme` next to the parent theme, with two files.

`style.css`:

```css
/*
Theme Name: Project Child Theme
Template: creationell-wp-theme
Description: Child theme of the project.
Version: 1.0.0
Requires at least: 7.1
Requires PHP: 8.3
License: GPL-2.0-or-later
Text Domain: creationell-wp-child-theme
Update URI: false
Bootstrap Lines: 5
*/
```

`functions.php`:

```php
<?php
/**
 * Functions of the child theme: project defaults, locks, capabilities and fonts.
 *
 * @package ProjectChildTheme
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'creationell_wp_theme_modules', 'creationell_wp_child_theme_modules' );
add_filter( 'creationell_wp_theme_settings', 'creationell_wp_child_theme_settings' );
add_filter( 'creationell_wp_theme_capabilities', 'creationell_wp_child_theme_capabilities' );
add_filter( 'creationell_wp_theme_font_catalog', 'creationell_wp_child_theme_font_catalog' );

/**
 * Sets the module defaults and locks of the project.
 *
 * @param mixed $modules Defaults and locks by module slug.
 * @return array<string, array<string, string>> Defaults and locks by module slug.
 */
function creationell_wp_child_theme_modules( $modules ) {
	return is_array( $modules ) ? $modules : array();
}

/**
 * Sets the setting defaults and locks of the project.
 *
 * @param mixed $settings Defaults and locks by setting key.
 * @return array<string, array<string, mixed>> Defaults and locks by setting key.
 */
function creationell_wp_child_theme_settings( $settings ) {
	return is_array( $settings ) ? $settings : array();
}

/**
 * Sets the rights of editors in the project.
 *
 * @param mixed $capabilities Switches and lists of the capability filter.
 * @return array<string, mixed> Switches and lists.
 */
function creationell_wp_child_theme_capabilities( $capabilities ) {
	return is_array( $capabilities ) ? $capabilities : array();
}

/**
 * Adds the fonts of the project to the font catalog.
 *
 * @param mixed $fonts Child fonts by slug.
 * @return array<string, mixed> Child fonts by slug.
 */
function creationell_wp_child_theme_font_catalog( $fonts ) {
	return is_array( $fonts ) ? $fonts : array();
}
```

Then fill the callbacks with the examples above and run `wp creationell-theme child check`.
