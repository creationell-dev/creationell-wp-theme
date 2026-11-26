# Architecture

How the theme loads, where its parts live, how its names are built and which functions and types are stable. The READMEs of the folders hold the details; read them instead of guessing.

## Loading

1. WordPress loads the `functions.php` of the child theme first, then the `functions.php` of the parent theme.
2. The `functions.php` of the parent theme requires `src/loader.php` and nothing else.
3. `src/loader.php` loads every file by its path, in a fixed order: `src/constants.php`, `src/functions-api.php`, the classes of the theme core in `src/`, then the template helpers of `inc/`. The core always comes before `inc/`.
4. `Core\Theme::boot()` registers the hooks of the core. With PHP or WordPress too old it registers only an admin notice.
5. On `after_setup_theme` (priority 20) the module gate loads the modules whose state is `active` or `hidden`, required modules first. A module that is `off` loads nothing.

The theme has no autoloader. Because the child theme loads first, its filters are in place before the theme reads module states, settings and capabilities.

## Folders

| Folder | Content | Read more |
|---|---|---|
| `src/` | Theme core: settings, modules, consent, capabilities, compiler, updater, the WP-CLI commands and the public functions; always loaded | [src/README.md](../src/README.md) |
| `inc/` | Template helpers that the templates call: navigation, columns, meta data of posts, icons, block classes | [inc/README.md](../inc/README.md) |
| `modules/` | Optional functions, one folder per module | [modules/README.md](../modules/README.md) |
| `assets/` | Stylesheets, scripts, logos and bundled libraries | [assets/README.md](../assets/README.md) |
| root, `template-parts/`, `page-templates/`, `single-templates/`, `parts/` | Templates and template parts; a child theme overrides them at the same path | [reference/templates.md](reference/templates.md) |
| `patterns/` | Block patterns of the theme | [reference/hooks-templates.md](reference/hooks-templates.md) |
| `languages/` | Translations of the theme | |
| `lib/`, `assets/vendor/` | Bundled libraries; never edit them | |

## Names

- PHP namespace `Creationell\WpTheme\` plus the area, for example `Creationell\WpTheme\Settings`; class names with underscores, for example `Child_Rules`.
- Functions, hooks, options, user meta and capabilities of the theme start with `creationell_wp_theme_`; constants start with `CREATIONELL_WP_THEME_`.
- `creationell-wp-theme` is the folder, the slug and the text domain of the parent theme; the child theme uses `creationell-wp-child-theme`.
- One class per file: `class-` for classes, `enum-` for enums, for example `src/child/class-child-rules.php`.
- Give the code of the project a prefix of its own. The prefixes of the theme belong to the theme.

## Public API

Stable for child themes and plugins. The functions live in `src/functions-api.php`; they are not pluggable, so a function of the same name defined earlier ends with a fatal error.

- `creationell_wp_theme_module_state()`: the effective state of a module: `active`, `hidden` or `off`.
- `creationell_wp_theme_setting()`: the value of a theme setting.
- `creationell_wp_theme_bootstrap_line()`: the active Bootstrap line (major version).
- `creationell_wp_theme_consent_allowed()`: whether the visitor allowed a consent category.
- `creationell_wp_theme_consent_categories()`: the consent categories of the site.
- `creationell_wp_theme_consent_inline_script()`: an inline script that runs only after consent for its category.
- `creationell_wp_theme_language_switcher()`: the markup of the language switcher.
- `creationell_wp_theme_render_template_part()`: prints the header or footer part of the Site Editor while the module `header-footer` is active; false tells the template to print its own markup.
- `creationell_wp_theme_blog_url()`: the address of the blog page.
- `creationell_wp_theme_blog_title()`: the title of the blog page as plain text.
- `creationell_wp_theme_has_blog_posts()`: whether the site has a published post.
- `creationell_wp_theme_blog_reading_time()`: the reading time of a post in minutes.
- `creationell_wp_theme_is_blog_view()`: whether the request is a blog view.

The types marked `@api` in their DocBlock, 10 classes and 2 enums:

- `Child_Rules`: checks a child theme folder against the child theme rules.
- `Child_Theme`: reads the header of the active child theme, including `Bootstrap Lines`.
- `Doctor_Command`: the sections of `wp creationell-theme doctor`; other code adds its own section.
- `Custom_Stylesheet`: builds the stylesheet of the design settings and the SCSS of the child theme.
- `Definition`: describes one setting: type, default and accepted values.
- `Setter`: checks and writes setting values.
- `Write_Context`: who writes a setting, through which channel and in which language.
- `Set_Result`: the result of one write.
- `Set_Status`: enum of the outcomes of a write.
- `Manifest`: the checked values of the update manifest.
- `Theme_Updater`: offers and verifies updates of the parent theme.
- `Update_Error`: enum of the reasons why an update is refused.

Everything else, the helpers of `inc/` included, can change between versions.

## Template switches

- `home.php` falls back to `index.php` and `author.php` falls back to `archive.php` while the module `blog` is not active. While the module is active and the setting `blog_author_pages` is off, every author address answers with the 404 page instead.
- Both also fall back while the child theme has its own `index.php` or `archive.php` but not its own `home.php` or `author.php`: an older override of the child keeps winning.
- Every template with its parts, hooks and contexts: [reference/templates.md](reference/templates.md).

## Pluggable helpers

Many helpers of `inc/` are wrapped in `if ( ! function_exists( ... ) )`. Never define one of them in the child theme: the replacement would miss every later fix of the original. Change their output through their filters and actions ([inc/README.md](../inc/README.md), [reference/hooks-templates.md](reference/hooks-templates.md)).
