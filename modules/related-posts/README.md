# Module related-posts

Related posts below a single post and as a block: posts of the same type that
share a category or a tag with the current post, newest first. They replace the
related posts of the plugin bs Swiper. The query lives in `src/posts/`
(`Related_Query`, see `src/posts/README.md`); the cards come from the module
`post-lists`, the slider from the module `post-slider`.

| | |
|---|---|
| States | `active`, `hidden`, `off` (default `off`) |
| Needs | Blockstudio 7.6 or later, the module `post-lists` |
| Replaces | the related posts of the plugin bs Swiper (`bs-swiper/main.php`) |
| Blocks | `creationell-theme/related-posts` |
| Settings | see below |
| Outposts | `template-parts/related-posts/` |

Switch it on with the constant `CREATIONELL_WP_THEME_MODULE_RELATED_POSTS` in
`wp-config.php`, with the filter `creationell_wp_theme_modules` of a child theme
or on the page **Theme settings > Modules** (see `modules/README.md`). While
`post-lists` is `off`, this module stays `off` with the reason
`missing_module:post-lists`.

## Settings

| Key | Section | Value |
|---|---|---|
| `related_posts_post_types` | design, advanced | comma list of public post types, default `post`; media never |
| `related_posts_taxonomies` | design, advanced | `category` (default), `post_tag` or `both` |
| `related_posts_count` | design, advanced | 1 to 12, default 3 |
| `related_posts_display` | design, advanced | `grid` (default) or `slider` |
| `related_posts_heading` | texts, basic, per language | up to 120 characters; empty shows "Related posts" |

## Output below a single post

While the module is `active`, the single templates print the related posts
through the action `creationell_wp_theme_before_single_pagination`, before the
links to the previous and next post. That happens only on a singular request
for a post type of `related_posts_post_types`, when the content holds no
related posts block (the block wins, so the posts show once) and the filter
`creationell_wp_theme_related_posts_auto_insert` returns `true`. A post
without categories or tags, or without related posts, prints nothing.

While the module is `hidden`, the automatic output stops; existing blocks keep
rendering.

## Block

**Related posts** (`creationell-theme/related-posts`) shows the related posts
of the post that holds it. Fields: **Number of posts** (0 takes the setting),
**Display** (as in the settings, grid or slider), **Heading** (empty takes the
setting) and **Heading level** (2 to 6; the post titles follow one level
below). In the editor a block outside a post, or in a post without terms,
shows "Shows related posts on single posts.".

## Markup

A `section` named by its heading (`aria-labelledby`), then the grid cards of
`post-lists` in as many columns as posts, at most three. The slider display
uses the post slider (no autoplay, arrows and dots; a `div` inside the named
section, not a landmark of its own) and needs the module `post-slider`; while that module is `off`, the posts show as a grid. The
stylesheet of `post-lists` (and for the slider the assets of `post-slider`)
loads early on a single post with related posts, otherwise when they render.

A child theme overrides `template-parts/related-posts/related-posts.php` under
the same path.

The early assets follow each related posts block in the content: a block with
the display slider brings the assets of `post-slider` even when the setting
says grid. The automatic output below a single post carries the class
`creationell-theme-related-posts--auto`; its bottom margin of 3rem comes from
the stylesheet of `post-lists`, not from a Bootstrap utility.

## Hooks

| Hook | Arguments |
|---|---|
| `creationell_wp_theme_related_posts_auto_insert` | `bool $insert`, `int $post_id`; only `true` keeps the automatic output |
| `creationell_wp_theme_post_query_args` | `array $args`, `string $context` (`related-posts`), `array $settings` |
| `creationell_wp_theme_post_card_classes` | `array<string,string> $classes`, `string $part`, `string $context` (`related-posts`) |
| `creationell_wp_theme_swiper_options` | `array $options`, `string $context` (`related-posts`) |

```php
add_filter(
	'creationell_wp_theme_related_posts_auto_insert',
	static fn( bool $insert, int $post_id ): bool => $insert && ! has_term( 'press', 'category', $post_id ),
	10,
	2
);
```

## States

- `active`: related posts below single posts, block in the inserter.
- `hidden`: existing blocks keep rendering, the inserter offers the block no
  more, no automatic output.
- `off`: nothing loads; existing blocks render nothing.

The module stores nothing and has no lifecycle callback.
