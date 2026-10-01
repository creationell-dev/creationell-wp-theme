# Post lists (`src/posts/`)

The query and display layer of the post blocks: the modules `post-lists`,
`post-slider` and `related-posts` share it, and so will a product slider. The
classes register no hooks; a module that is off leaves them unused. Namespace
`Creationell\WpTheme\Posts`.

| Class | Purpose |
|---|---|
| `Query_Args` | Block attributes to `WP_Query` arguments: viewable post type, taxonomy of that type, term, post and parent IDs through `absint()` and `wpml_object_id`, order from a positive list, at most `post_lists_max_posts` posts (24 until the setting exists), only published posts, rows counted only for a paginated post list. Helpers for the value shapes of Blockstudio: `ids()`, `choice()`, `flag()`; `current_post()` names the post that holds a block (block context, then the loop post, in a REST render first the post of the Blockstudio block data). |
| `Related_Query` | Arguments for the posts related to a post: same type, a shared term of `category`, `post_tag` or both (OR), newest first, without the post; `null` without terms or for a type outside `related_posts_post_types`. |
| `Display_Options` | The `show*` attributes and the heading level (2 to 6) as card options; `normalize()` repairs the options a card template receives, `card_classes()` runs the class filter. |
| `Pagination` | One query variable per paginated list and request (`creationell-list-<n>-page`), the requested page (digits only), page links that keep the other GET parameters. |
| `Content_Guard` | Lets the accordion print the full content of one other post at a time; refuses the queried post, an open post and a second level. |
| `Slider_Config` | Swiper options for `data-creationell-slider`: breakpoints 0/768/992/1400 px, fade only for heroes, autoplay delay 3000 to 15000 ms, the translated a11y texts of Swiper and the labels of the pause button, as plain text without markup, also none written as character references (Swiper writes some of them with innerHTML); `JSON_FLAGS` for `wp_json_encode()` in the attribute. |

The card templates live in `template-parts/post-lists/`: `card-grid.php`,
`card-list.php`, `card-hero.php` and `empty.php`. A card has one link to the
post, the title, stretched over the card; the image has an empty `alt` and no
link.

## Hooks

| Hook | Arguments |
|---|---|
| `creationell_wp_theme_post_query_args` | `array $args`, `string $context` (`post-list`, `post-accordion`, `post-slider`, `related-posts`), `array $attributes` (settings for `related-posts`) |
| `creationell_wp_theme_post_card_classes` | `array<string,string> $classes`, `string $part` (`card-grid`, `card-list`, `card-hero`), `string $context` |
| `creationell_wp_theme_swiper_options` | `array $options`, `string $context` (`post-slider`, `related-posts`) |

```php
add_filter(
	'creationell_wp_theme_post_query_args',
	static function ( array $args, string $context ): array {
		if ( 'related-posts' === $context ) {
			$args['posts_per_page'] = 4;
		}
		return $args;
	},
	10,
	2
);
```

## Term selection

Blockstudio 7.6.14 does not resolve `{attributes.taxonomy}` in the block
editor (spike SP-9.1, `tests/spikes/tp9-blockstudio/README.md`). The blocks
therefore take the terms as a text field with a comma list of term IDs;
`Query_Args::ids()` reads it, as well as the `{value, label}` objects that
fields with `populate` and `fetch` store.
