<!-- Generated from the source. Do not edit. -->

# Hooks

The theme fires 182 own hooks: 134 filters and 48 actions. Each hook is listed once: the filters `creationell_wp_theme_class_<place>` in hooks-classes.md, every other hook in the file of the first path that fires it.

| File | Hooks | Covers |
|---|---|---|
| [hooks-classes.md](hooks-classes.md) | 59 | filters `creationell_wp_theme_class_<place>` for the CSS classes of one place |
| [hooks-templates.md](hooks-templates.md) | 53 | root templates, `inc/`, `patterns/`, `page-templates/`, `single-templates/` |
| [hooks-template-parts.md](hooks-template-parts.md) | 22 | `template-parts/` |
| [hooks-modules.md](hooks-modules.md) | 19 | `modules/` |
| [hooks-core.md](hooks-core.md) | 29 | `src/` |

## Dynamic names

- `creationell_wp_theme_icon_<name>`: Filters the SVG markup of an icon; the hook name ends with the icon name.

## JavaScript events

| Event | Description | Defined in |
|---|---|---|
| `creationell-wp-theme:consent-change` | Event on document after a consent change; detail: { accepted: string[] }. | `src/consent/class-consent-bridge.php` |

## Hooks of WordPress and plugins

The theme also fires or calls these hooks; their owners document them.

- `comment_form_default_fields`: `comments.php`
- `nav_menu_css_class`: `inc/class-creationell-wp-theme-nav-walker.php`
- `nav_menu_item_id`: `inc/class-creationell-wp-theme-nav-walker.php`
- `nav_menu_item_title`: `inc/class-creationell-wp-theme-nav-walker.php`
- `nav_menu_link_attributes`: `inc/class-creationell-wp-theme-nav-walker.php`
- `term_links-post_tag`: `template-parts/blog/entry-terms.php`
- `the_title`: `inc/class-creationell-wp-theme-nav-walker.php`
- `walker_nav_menu_start_el`: `inc/class-creationell-wp-theme-nav-walker.php`
- `wpml_active_languages`: `src/wpml/class-language-switcher.php`
- `wpml_default_language`: `src/wpml/class-wpml-integration.php`
- `wpml_object_id`: `modules/consent/inc/class-links.php`, `modules/header-footer/inc/class-menu-scope.php`, `modules/header-footer/inc/class-part-translations.php`, `src/blog/class-blog-url.php`, `src/posts/class-query-args.php`, `src/settings/transfer/class-reference-codec.php`
- `wpml_setting`: `src/wpml/class-snapshot-sync.php`, `src/wpml/class-wpml-integration.php`
