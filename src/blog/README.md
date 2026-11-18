# Blog helpers (`src/blog/`)

The calculations of the blog kit. They belong to the core and always load,
also while the module `blog` is off, so templates, child themes and plugins
can call them on every site. The classes register no hooks. Namespace
`Creationell\WpTheme\Blog`.

| Class | Purpose |
|---|---|
| `Blog_Url` | URL of the blog page: with a static front page the posts page of the reading options (`page_for_posts`, translated with `wpml_object_id`), with posts on the front page `home_url( '/' )`, else `''`. `title()` gives the title of the posts page as plain text (tags removed, character references decoded). `has_posts()` asks once per request whether a published post exists. |
| `Reading_Time` | Minutes to read a post, at least 1: words (letters and digits, joined by an apostrophe or hyphen) per minute plus Han, Hiragana and Katakana characters at 500 per minute, counted in the text of every block (lists, captions and containers of other plugins too) after `strip_shortcodes()` and `wp_strip_all_tags()` (script and style elements go with their content), with a space for every tag and block comment. Stored in the post meta `_creationell_wp_theme_reading_time` with the modification time and the words per minute; reused while both match. A preview neither reads nor stores it. |
| `Blog_Context` | Blog view: the blog page, a single post, a category, tag or date archive, and an author archive while the main query asks for posts. Search results are none. |
| `Blog_Templates` | Switch of `home.php` and `author.php`: `fallback( 'home' )` gives the path of `index.php` and `fallback( 'author' )` the path of `archive.php` (as `locate_template()` finds them) while the module `blog` is not active, for `author` also while the setting `blog_author_pages` is off, and while `child_overrides()` is true; `''` renders the new template. `child_overrides( $template )` is true while a child theme has its own `index.php` (home) or `archive.php` (author) but not its own `home.php` or `author.php`. |

The public functions in `functions-api.php` wrap them:
`creationell_wp_theme_blog_url()`, `creationell_wp_theme_blog_title()`,
`creationell_wp_theme_has_blog_posts()`,
`creationell_wp_theme_blog_reading_time( $post )` (words per minute from the
setting `blog_reading_wpm`, 200 while it is not registered) and
`creationell_wp_theme_is_blog_view()`.

## Hooks

| Hook | Arguments |
|---|---|
| `creationell_wp_theme_blog_url` | `string $url` |
| `creationell_wp_theme_reading_time_wpm` | `int $wpm`, `WP_Post $post`; the result is kept between 100 and 1000 |
| `creationell_wp_theme_is_blog_view` | `bool $is_blog_view`; only `true` counts |

```php
add_filter(
	'creationell_wp_theme_is_blog_view',
	static fn( bool $is_blog_view ): bool => $is_blog_view || is_post_type_archive( 'podcast' )
);
```
