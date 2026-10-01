# Theme modules

A module is an optional function of the theme that a site switches on or off:
blocks, integrations with plugins, the cookie banner. Modules ship with the theme
only; there is no companion plugin. Every module is `off` until a site switches it on.

## Catalog

| Slug | Content | States | Status |
|---|---|---|---|
| `post-lists` | Post list blocks (grid, list, hero), accordion and tabs, query layer, card partials | `active`, `hidden`, `off` | available |
| `post-slider` | Post slider block (columns, heroes) with Swiper and a pause button | `active`, `hidden`, `off` | available |
| `related-posts` | Related posts below a single post, optional block; uses the partials of `post-lists` | `active`, `hidden`, `off` | available |
| `contact-form-7` | Styles and behavior for Contact Form 7 forms, no block | `active`, `off` | available |
| `consent` | Cookie banner and preferences dialog (CookieConsent 3.1.0) as consent provider for the core consent interface, footer link, block `consent-link`; not legally reviewed | `active`, `off` | available |
| `header-footer` | Header and footer as template parts of the Site Editor, edited by editors; blocks for navbar, logo, search, footer menu, footer text, contact, social links and language switcher | `active`, `off` | available |
| `woocommerce` | Shop templates, shop styles, product slider | `active`, `off` | not installed |

`wp creationell-theme module list` shows the state of every module; a module
without its folder is `off` with the reason `not_installed`.

States:

- `active`: the module runs.
- `hidden`: blocks still render existing content but are missing from the block
  inserter; the settings page and the assets of the module stay off.
- `off`: `bootstrap.php` is not loaded, the module registers nothing.

## Folder convention

Each module lives in `modules/<slug>/`:

| Path | Content |
|---|---|
| `module.php` | Manifest: returns an array with `slug`, `title` (closure with a literal `__()` call), `type` (`block` or `integration`), `states`, `default` (`off`), `requires` (`plugins`, `modules`, `blockstudio`), `blocks`, `outposts`, `since`; optional `legacy_plugins`, `warn_before_off`, `off_warning`, `on_change` |
| `settings.php` | Returns a list of `Definition` objects for the settings registry; loaded on `init` for every installed module, whatever its state. Sections `design` or `texts`; `modules` holds only the switch `module_<slug>`, which the theme adds itself. Declares no functions or classes |
| `bootstrap.php` | Hooks of the module; not loaded while the module is `off` |
| `blocks/` | Blocks of the module, one folder per block, named `creationell-theme/<block>` |
| `assets/` | Built CSS and JavaScript of the module |
| `README.md` | Purpose, settings and outposts of the module |

Outside its folder a module may use only these outposts, listed in its manifest:

- `patterns/<slug>/` for block patterns.
- `template-parts/<slug>/` for template parts, which a child theme overrides
  under the same path.
- `parts/<part>.html` for a template part file of the Site Editor, which
  WordPress reads from the `parts/` folder of the theme only.

Module files carry no plugin header (`Plugin Name:`); a module is never a plugin.

### Manifest keys

The theme validates `module.php` when it reads the catalog; an invalid manifest
keeps the module `off` with the reason `invalid_manifest`.

| Key | Form | Meaning |
|---|---|---|
| `slug` | string, the folder name | Identifies the module in the option name, the constant and WP-CLI |
| `title` | `static fn(): string => __( '...', 'creationell-wp-theme' )` | Shown on the module page and in notices; a closure, so the text is translated when it is shown |
| `description` | `static fn(): string => __( '...', 'creationell-wp-theme' )`, optional | One or two sentences on what the module does, shown in the column *Description* of the module page; a closure like `title` |
| `type` | `block` or `integration` | `block` for a module with `blocks`, `integration` for one without; only blocks are counted before a module goes `off` |
| `states` | list of `active`, `hidden`, `off` | `hidden` only for modules whose blocks may stay in existing content |
| `default` | `off` | Every module starts `off`; a child theme sets another default with the filter |
| `requires.plugins` | list of plugin files, e.g. `contact-form-7/wp-contact-form-7.php` | The module stays `off` while one of them is not active |
| `requires.modules` | list of slugs | The module stays `off` while one of them is `off` |
| `requires.blockstudio` | version string, or `false` | `false`: the module runs without Blockstudio |
| `blocks` | list of full block names, `creationell-theme/<block>` | The blocks the content scanner looks for before the module goes `off` |
| `outposts` | list of paths | The files outside the module folder (see above) |
| `since` | version string `X.Y.Z`, e.g. `1.0.0` | Theme version that added the module (required) |
| `legacy_plugins` | list of plugin files | Older plugins the module replaces; the module stays `off` while one is active |
| `warn_before_off` | `bool`, default `false` | Asks for a confirmation before `off` even when no content uses the blocks |
| `off_warning` | `static fn(): string => __( '...' )` | What stops working; shown on the confirmation page |
| `on_change` | `static function ( string $from, string $to ): void` | Runs after each switch; an exception does not undo the switch, the module page shows a warning and the PHP error log names the class |

`on_change` lives in the manifest because `bootstrap.php` is not loaded while
the module is `off`.

### Test modules

On a site with the environment type `local`, the constant
`CREATIONELL_WP_THEME_TEST_MODULES_DIR` may name a second folder with modules;
the test suites point it at `tests/fixtures/modules/` of the repository. A slug
in both folders comes from the theme. On other environment types the constant
is ignored, so a release never reads test modules.

## Switching a module

The configured state follows this order, the highest layer that is set wins:

1. Default: `off`.
2. Child default: `'default'` in the filter `creationell_wp_theme_modules`.
3. Backend: the state switched in the backend, stored in the option
   `creationell_wp_theme_global_module_<slug>` (slug with underscores, not
   autoloaded) only where it differs from the child default.
4. Child lock: `'locked'` in the same filter.
5. Constant `CREATIONELL_WP_THEME_MODULE_<SLUG>` in `wp-config.php`, the slug in
   upper case with underscores, e.g. `CREATIONELL_WP_THEME_MODULE_HEADER_FOOTER`.

A value the module does not know gives `off`; a backend value the module does
not know is skipped. A module configured `active` or `hidden` is still `off`
while one of these checks fails, in this order: an older plugin of its
`legacy_plugins` is active, a plugin of `requires.plugins` is not active,
Blockstudio is required but not loaded, a module of `requires.modules` is `off`,
the required modules lead back to the module. `wp creationell-theme module list`
names the reason, e.g. `missing_plugin:contact-form-7/wp-contact-form-7.php`.

Code asks for the effective state with
`creationell_wp_theme_module_state( '<slug>' )`, from `after_setup_theme` on.

Only `Creationell\WpTheme\Modules\Module_Switcher` writes the backend layer, for
the module page, WP-CLI and the settings import alike. It needs the capability
`creationell_wp_theme_manage_modules`, refuses locked modules and asks before a
module goes `off` when it has `warn_before_off` or live contents use its blocks
(`Core\Content_Scanner` counts them). A switch fires
`creationell_wp_theme_before_module_switch` and
`creationell_wp_theme_module_state_changed` with slug, old state, new state and
channel (`admin`, `cli`, `import`), clears the pattern caches, runs
`on_change` and takes effect with the next request.

```php
add_filter(
	'creationell_wp_theme_modules',
	static function ( mixed $modules ): mixed {
		$modules = is_array( $modules ) ? $modules : array();
		$modules['header-footer'] = array( 'default' => 'active' );
		return $modules;
	}
);
```

The page **Theme settings > Modules** (`admin.php?page=creationell-wp-theme-modules`)
lists every module with its effective state and the reason. Users with
`creationell_wp_theme_view_advanced` see it; only users with
`creationell_wp_theme_manage_modules` get the forms. A module that is `active`
and knows `hidden` goes to `hidden` first; switching a module with blocks or
with `warn_before_off` off shows the contents that use its blocks and, when
needed, asks for a confirmation.

WP-CLI switches a module with the same checks; writing needs `--user`:

```
wp creationell-theme module enable contact-form-7 --user=admin
wp creationell-theme module disable post-lists --user=admin                     # to hidden
wp creationell-theme module disable post-lists --state=off --yes --user=admin   # confirms the consequences
wp creationell-theme module scan post-lists --format=json                       # counts the contents, reads only
```

Without `--yes`, switching off a module whose blocks live contents use stops
with exit code 1 and changes nothing; `--dry-run` shows what would change.
`scan` counts contents, not block instances: a post with three blocks of the
module counts once. Live contents are published, draft, pending, private and
scheduled posts of every post type, synced patterns, template parts, templates,
navigation menus and block widgets; revisions, trashed posts and auto drafts
count as stored. `--format=json` prints
`{"slug","needles","rows","total_live","total_stored"}`.

Nothing is deleted when a module goes `off`: its blocks stay in the content and
show again once the module is on. Before a module goes `off`, the module page
and WP-CLI recommend exporting the settings; a backup feature that saves them
itself returns `true` from the filter
`creationell_wp_theme_module_backup_available` and the hint disappears.

A switch stores the state in `creationell_wp_theme_global_module_<slug>`.
That option is a row of the theme settings, so the settings snapshot
`creationell_wp_theme_settings_snapshot` (the copy the settings getter reads)
is rebuilt at the end of the request. The settings transfer of the theme saves
a backup of the modules section before every switch over the module page or
WP-CLI, so `creationell_wp_theme_settings_backups` and
`creationell_wp_theme_settings_transfer_log` appear as well. The module itself
writes no option when it is switched.

On **Appearance > Themes**, users who may switch themes see a notice while a
module with blocks or the consent module is on: another theme would drop those
blocks, the cookie banner and the consent lock.

## Loading a module

The module gate loads a module only while its effective state is `active` or
`hidden`:

- On `after_setup_theme` (priority 20) it requires `bootstrap.php` once per
  request, required modules first. The file sees `$slug`, `$state` and
  `$manifest`. A module that is `off` loads nothing; only its `settings.php`
  is read. A switch takes effect with the next request.
- On `init` (priority 10) it registers the `blocks/` folder of each loaded
  module as its own Blockstudio instance, when Blockstudio is loaded.
- The block patterns below `patterns/<slug>/` count only while the module is
  `active`, in the parent and in the child theme.
- An active older plugin of `legacy_plugins` keeps the module `off`; an admin
  notice names the plugin.
- A `bootstrap.php` that throws stops only its own module; an admin notice
  shows the error.

`hidden` keeps existing content working and offers nothing new: the gate hides
the blocks of the module from the block inserter, the module shows no settings
page and loads its assets only on pages with one of its blocks. The hidden
block names are part of the identity of the Blockstudio runtime cache, so a
cache built while a module was hidden never serves it once it is active again.
That identity entry carries a format mark and is there also when no block is
hidden, so a cache built by an older theme version is not used either.

Helpers for modules, all to be called from `bootstrap.php`, before `init`:

- `Creationell\WpTheme\Modules\Inserter_Visibility::hide( 'creabb/post-grid' )`
  hides another block from the inserter, e.g. one the module replaces.
- `Creationell\WpTheme\Modules\Block_I18n::register_namespace( '<namespace>' )`
  translates title, description, keywords, field labels, help texts and option
  labels of Blockstudio blocks with the text domain `creationell-wp-theme`. The
  gate registers `creationell-theme` for every module with blocks. The texts come
  from `block.json` at run time; list each of them once as a literal
  `__( '...', 'creationell-wp-theme' )` in `modules/<slug>/i18n-strings.php`, a
  file the theme never loads, so the extraction finds them. Blockstudio keeps the
  translated field texts in its runtime cache; while a namespace is registered
  the locale of the request is part of that cache identity, so Blockstudio keeps
  one cache per locale.
- `Creationell\WpTheme\Assets\Vendor_Scripts` registers the shared libraries of
  `assets/vendor/` on `init` (priority 20) under their handle, the script in the
  footer with `defer`, never enqueued. A module enqueues the handle only where it
  needs the library.

## Core, never a module

These parts of the theme always run and cannot be switched off:

- Updater
- Settings
- Import and export
- Compiler
- Locks
- Module gate
- Consent interface: `creationell_wp_theme_consent_allowed()` allows only
  `necessary` in PHP, also with a consent provider. A script waits for consent
  when its handle is marked with
  `wp_script_add_data( $handle, 'creationell-wp-theme-consent', 'analytics' )`:
  the theme prints it as `text/plain` until the consent provider runs it.
  In the browser `window.creationellWpTheme.consent.allowed( 'analytics' )`
  asks the provider. Without a provider marked scripts never run; an admin
  notice and `wp creationell-theme doctor` name them.

### Consent interface

The interface belongs to the theme core, so a script can wait for consent
whether or not a consent module is on.

- Categories: `necessary`, `functional`, `analytics`, `marketing`; the filter
  `creationell_wp_theme_consent_categories` adds or removes categories
  (`necessary` always stays; names must match `^[a-z][a-z0-9_-]*$`).
  `creationell_wp_theme_consent_categories()` returns the list.
- Marking: the marked handle, its inline code (`before`, `after`), its
  localized data and its translations print as `type="text/plain"` with
  `data-category`; `src` stays, `async`, `defer` and the loading strategy go.
  A handle marked `necessary` prints as usual. A category the theme does not
  know keeps the script blocked. For inline code without a handle,
  `creationell_wp_theme_consent_inline_script( 'analytics', $javascript )`
  returns a blocked script tag.
- Provider: a consent module returns an object that implements
  `Creationell\WpTheme\Consent\Consent_Provider_Interface` (`id()`,
  `categories()`) from the filter `creationell_wp_theme_consent_provider`.
  Anything else counts as no provider.
- Browser: the theme prints `window.creationellWpTheme.consent` first in the
  head of every page: `categories`, `allowed( category )`, `hasProvider()`,
  `registerProvider( { id, allowed } )` and `notify()`. Only the provider named
  by the filter may register its adapter, once. `notify()` sends the event
  `creationell-wp-theme:consent-change` on `document` with
  `detail.accepted`, the allowed categories. The interface itself runs no
  script; the consent provider does that.

```js
document.addEventListener( 'creationell-wp-theme:consent-change', ( event ) => {
	if ( event.detail.accepted.includes( 'analytics' ) ) {
		// Start the analytics code.
	}
} );
```

`wp creationell-theme doctor --format=json` reports the sections `modules`
(configured and effective state, origin, reason, older plugins) and `consent`
(provider, categories, marked scripts without a provider).

The public functions `creationell_wp_theme_module_state()`,
`creationell_wp_theme_setting()`, `creationell_wp_theme_bootstrap_line()`,
`creationell_wp_theme_consent_allowed()`,
`creationell_wp_theme_consent_categories()` and
`creationell_wp_theme_consent_inline_script()` are not pluggable. A child theme or
plugin that defines one of them first stops with "Cannot redeclare".
