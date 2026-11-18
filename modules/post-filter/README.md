# Module post-filter

Shows the filters of the plugin JPKCom Post Filter in the blog sidebar instead
of the plain category and tag lists of the module `blog`.

| | |
|---|---|
| States | `active`, `off` (default `off`) |
| Needs | the plugin JPKCom Post Filter (`jpkcom-post-filter/jpkcom-post-filter.php`) and the module `blog` |
| Replaces | nothing |
| Blocks | none |
| Settings | none |
| Outposts | `template-parts/post-filter/` |

Switch it on in *Theme settings > Modules*, with
`wp creationell-theme module enable post-filter --user=<admin>`, with the
constant `CREATIONELL_WP_THEME_MODULE_POST_FILTER` in `wp-config.php` or with
the filter `creationell_wp_theme_modules` of a child theme (see
`modules/README.md`). The child theme template sets the default `active`, so
the module follows the plugin once the module `blog` is on: without the plugin
it stays `off` with the reason
`missing_plugin:jpkcom-post-filter/jpkcom-post-filter.php`, while the module
`blog` is off with the reason `missing_module:blog`.

## What it does

The module works with JPKCom Post Filter 1.7.2 or later; with an older
version, or without the functions of the plugin, it adds nothing. In the blog
view the filter bar of the plugin for posts (layout sidebar) takes the place of
the category and tag lists at the top of the blog sidebar. On the blog page the
plugin shows its own bar while its setting auto-inject is on; there the sidebar
shows only its widgets. A filter bar without a filter group for posts counts as
empty, and the term lists of the module `blog` stay. Before the post loop
(`creationell_wp_theme_before_loop`) the plugin prints its fallback for a
filter without results; while it prints that zone, or while it injects into
the blog page, the blog page leaves out its own note "No posts match these
filters.", so an empty result shows one message.

`Post_Filter_Module::available()` (namespace
`Creationell\WpTheme\Modules\PostFilter`) tells whether the filter bar has
content; it renders the bar once per request.

## Template part

`template-parts/post-filter/filter.php` prints the markup of the plugin
unchanged, from the argument `html`; the plugin escapes it and names its own
navigation landmark. A child theme overrides it at the same path.

## Hooks

The module answers three filters of the module `blog`:

- `creationell_wp_theme_blog_sidebar_has_filter`: `true` where it prints its
  filter bar, so the blog sidebar shows also without widgets.
- `creationell_wp_theme_blog_sidebar_terms`: `false` where the filter bar
  replaces the term lists.
- `creationell_wp_theme_blog_home_empty`: `false` while the plugin prints its
  zero results zone on the blog page or injects into it.

It prints the filter bar on `creationell_wp_theme_before_sidebar_widgets`
(priority 9). `wp creationell-theme doctor` warns in the section `blog` while
the module is active and the plugin has no filter bar for posts or its link
mode is `none`.

## States

- `active`: the filters of the plugin show in the blog sidebar.
- `off`: nothing loads; the blog sidebar shows the category and tag lists of
  the module `blog`.

The module stores nothing and has no lifecycle callback.
