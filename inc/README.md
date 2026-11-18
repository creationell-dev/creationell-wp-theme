# Template helpers (`inc/`)

Procedural helpers that the templates call, taken over from the base theme
Bootscore and renamed. They stay close to the base, so that its fixes can be
ported; changes here are limited to accessibility, hardening and logical CSS
properties. The theme core lives in `src/`.

`src/loader.php` loads every file below; nothing here runs on its own.

| File | Content |
|---|---|
| `body.php` | Classes of the `body` element |
| `breadcrumb.php` | Breadcrumb navigation |
| `class-creationell-wp-theme-nav-walker.php` | Walker of the Bootstrap navigation: menu items with a submenu become a link plus a toggle button |
| `columns.php` | Widths and breakpoints of the main and sidebar columns |
| `comments.php` | Comment list and comment form |
| `excerpt.php` | Excerpts for pages |
| `icons.php` | Inline SVG icons from Bootstrap Icons and the HTML they may contain |
| `navmenu.php` | Menu locations |
| `pagination.php` | Pagination of archives and single posts |
| `password-protected-form.php` | Form of password protected posts |
| `template-functions.php` | Hooks that adapt WordPress output to the templates |
| `template-tags.php` | Meta data of posts: author, dates, categories, tags, comments; the spots of the blog kit |
| `tinymce-editor.php` | Styles of the classic editor |
| `widgets.php` | Widget areas |
| `blocks/` | Bootstrap classes for core blocks and widget blocks, the pattern category; `disable-unsupported-blocks.php` |

## Changing the output

Change the output through the filters and actions of the helpers, from a
child theme or a plugin:

- Class filters `creationell_wp_theme_class_<place>` return the CSS classes of
  a place, e.g. `creationell_wp_theme_class_container` or
  `creationell_wp_theme_class_main_col`; most pass the location as a second
  argument, e.g. `header`, `footer-top` or `page`.
- Actions such as `creationell_wp_theme_before_title` and
  `creationell_wp_theme_after_loop_item` add markup at fixed places.
- `creationell_wp_theme_disable_unsupported_blocks` returns `true` to hide the
  blocks and patterns that have no theme styles.

```php
add_filter(
	'creationell_wp_theme_class_container',
	static function ( string $classes, string $location ): string {
		return 'header' === $location ? 'container-xxl' : $classes;
	},
	10,
	2
);
```

## Spots of the blog kit

`template-tags.php` holds the places where the module `blog` adds its parts to
a single post. The four single templates pass their context (`single`,
`single-sidebar-left`, `single-sidebar-none`, `single-full-width-image`):

- `creationell_wp_theme_category_badge( $context )` prints the category
  badges; the filter `creationell_wp_theme_show_category_badge` (`$show`,
  `$context`) hides them. Cards and post lists call it without a context.
- `creationell_wp_theme_entry_meta( $context )` prints the markup of the filter
  `creationell_wp_theme_entry_meta_html` and returns `true`; with an empty
  filter result it prints nothing and returns `false`, and the template prints
  its own meta line.
- `creationell_wp_theme_entry_terms( $context )` does the same with the filter
  `creationell_wp_theme_entry_terms_html`; with `false` the template prints its
  tags with `creationell_wp_theme_tags()`.
- `creationell_wp_theme_author()` asks the filter
  `creationell_wp_theme_meta_author_link` (`$link`, `$user_id`) whether the
  name links to the author archive.

Both markup filters pass `wp_kses()` with the post tags and the SVG icons of
the theme. `columns.php` and `template-functions.php` read the filter
`creationell_wp_theme_blog_sidebar_force`, so a sidebar without widgets still
gets the column `col-lg-9` and no body class `no-sidebar`. See
`modules/blog/README.md`.

```php
<?php
if ( ! creationell_wp_theme_entry_terms( 'single' ) ) {
	creationell_wp_theme_tags();
}
?>
```

Some helpers are wrapped in `function_exists()` checks, as in the base theme.
Do not redefine them in a child theme: a child theme changes output through the
filters above or by overriding a template.
