# Module post-lists

Post lists as blocks: a grid, a list or large image cards, and an accordion or
tabs. The blocks replace the shortcodes and patterns of the plugin bs Grid. The
query and display layer lives in `src/posts/` (see `src/posts/README.md`); the
modules `post-slider` and `related-posts` use the same card templates.

| | |
|---|---|
| States | `active`, `hidden`, `off` (default `off`) |
| Needs | Blockstudio 7.6 or later |
| Replaces | the plugin bs Grid (`bs-grid/main.php`); while `active` the block `creabb/post-grid` of CreaBootstrapBlocks leaves the inserter |
| Blocks | `creationell-theme/post-list`, `creationell-theme/post-accordion` |
| Settings | `post_lists_max_posts` (design, advanced): most posts per list, 1 to 50, default 24 |
| Outposts | `template-parts/post-lists/` |

Switch it on with the constant `CREATIONELL_WP_THEME_MODULE_POST_LISTS` in
`wp-config.php`, with the filter `creationell_wp_theme_modules` of a child theme
or on the page **Theme settings > Modules** (see `modules/README.md`).

## Blocks

**Post list** (`creationell-theme/post-list`): layout `grid` (1 column, 2 from
768 px, the chosen 1 to 4 columns from 992 px), `list` (image on the start side
from 768 px) or `hero` (large image with the text on a dark box at 75 %
opacity, so the contrast does not depend on the image). With **Pagination** the
list gets page links; each paginated list on a page reads its own query
variable `creationell-list-<n>-page`, so the main query and other lists keep
their page. The navigation of the first paginated list is named "Post list
navigation", later ones on the same page "Post list navigation 2" and so on, so
every landmark has its own name; the main pagination of archive, blog and
search pages keeps "Posts navigation".

**Post accordion** (`creationell-theme/post-accordion`): display `accordion`
or `tabs`; the panel shows the excerpt or the full content of each post. The
full content of the page that holds the block, and any content inside such
content, falls back to the excerpt, so a post never prints itself.

Both blocks choose the posts the same way:

| Field | Meaning |
|---|---|
| Post type | any viewable post type except media |
| Taxonomy and Term IDs | only posts with one of the terms; the term IDs are a comma list, because Blockstudio 7.6.14 cannot fill a term list from the chosen taxonomy |
| Selected posts | only these posts; order "Order of the selected posts" keeps the chosen order |
| Parent page | only the child pages of this page |
| Order by, Direction | date, title, menu order, last modified, random |
| Number of posts | 1 to 50 (accordion 1 to 20), capped by the setting `post_lists_max_posts` |
| Leave out the current post | on by default |

**Selected posts** and **Parent page** list the 20 newest entries when they
open; typing searches all posts or pages (Blockstudio `fetch`). The inserter
preview shows three posts (`example` in `block.json`). **Leave out the current
post** means the post of a query loop around the block, else the post of the
page; in the editor the edited post (`Query_Args::current_post()`).

Only published posts show. With WPML each ID maps to the language of the
request (`wpml_object_id`). Display fields switch the image, categories, date
and author, excerpt, "Read more" and tags; the heading level of the titles
(2 to 6) fits the block into the headings of the page.

Additional CSS classes, the HTML anchor and the alignment of the block
editor go to the root element of the block (Blockstudio passes them in the
block data, not among the attributes).

A card has one link to the post: the title, stretched over the card. The image
has an empty `alt` and no link; "Read more" names the post for screen readers.
Password protected posts show no excerpt. On the page a block without posts
prints nothing; the editor shows "No posts found.".

## Template parts

A child theme overrides any of them under the same path:

| Part | Content |
|---|---|
| `list.php` | root element and list of the post list, then `pagination.php` |
| `card-grid.php`, `card-list.php`, `card-hero.php` | one card |
| `accordion.php`, `tabs.php` | accordion or tabs of the post accordion |
| `panel.php` | the panel of one post in the accordion or the tabs |
| `pagination.php` | page links of a paginated list |
| `empty.php` | empty state in the editor |

## Assets

`assets/post-lists.css` (handle `creationell-wp-theme-post-lists`, after
`creationell-wp-theme-main`) loads early on a singular page that holds one of
the blocks, otherwise when a block renders, and in the editor canvas.
`blocks/post-accordion/accordion.editor.css` opens every panel in the editor
canvas. Accordion and tabs use the Bootstrap script of the theme; the module
brings no script.

## Icon font

The block templates use inline SVG icons and need no icon font. The theme
loads the Bootstrap Icons font (`creationell-wp-theme-icons`) only for
CreaBootstrapBlocks or through the filter `creationell_wp_theme_load_icon_font`.
Measured on 2026-09-29 on the reference page of the end-to-end tests (all
layouts of the three post modules, CreaBootstrapBlocks active, headless
Chromium): 204,512 bytes transferred in 23 requests with the font stylesheet,
190,591 bytes in 22 requests without it. The difference is the stylesheet
`bootstrap-icons.min.css` (13,990 bytes transferred, 87,008 bytes unpacked);
the font file itself (`bootstrap-icons.woff2`, 134,044 bytes) loads only when
an element on the page uses an icon glyph, which none of the post blocks does.
A site without CreaBootstrapBlocks icons can switch the font off:

```php
add_filter( 'creationell_wp_theme_load_icon_font', '__return_false' );
```

## Tests

Unit tests: `composer test:unit -- --group=post-lists` (and `posts` for
`src/posts/`). Integration and end-to-end tests of the three post modules are
described in `tests/README.md` of the development repository.

## Hooks

| Hook | Arguments |
|---|---|
| `creationell_wp_theme_post_query_args` | `array $args`, `string $context` (`post-list`, `post-accordion`), `array $attributes` |
| `creationell_wp_theme_post_card_classes` | `array<string,string> $classes`, `string $part`, `string $context` |
| `creationell_wp_theme_hide_cbb_post_grid` | `bool $hide` (default `true`); `false` keeps `creabb/post-grid` in the inserter |

```php
add_filter( 'creationell_wp_theme_hide_cbb_post_grid', '__return_false' );
```

## States

- `active`: blocks in the inserter, `creabb/post-grid` hidden (unless the
  filter says otherwise).
- `hidden`: existing blocks keep rendering, the inserter offers them no more;
  `creabb/post-grid` stays in the inserter.
- `off`: nothing loads; existing blocks of the module render nothing.

The module stores nothing and has no lifecycle callback.
