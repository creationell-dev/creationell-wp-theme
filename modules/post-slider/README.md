# Module post-slider

A slider of posts as a block: cards in columns or large image cards, based on
[Swiper](https://swiperjs.com/) 14 (vendored in `assets/vendor/swiper/`). The
block replaces the post sliders of the plugin bs Swiper. The query and display
layer lives in `src/posts/` (see `src/posts/README.md`); the slides use the card
templates of `template-parts/post-lists/`, which ship with the theme whether or
not the module `post-lists` is on.

| | |
|---|---|
| States | `active`, `hidden`, `off` (default `off`) |
| Needs | Blockstudio 7.6 or later |
| Replaces | the plugin bs Swiper (`bs-swiper/main.php`) |
| Block | `creationell-theme/post-slider` |
| Settings | `post_slider_autoplay_delay` (design, advanced): time per slide of a playing slider, 3000 to 15000 ms, default 6000 |
| Outposts | `template-parts/post-slider/` |

Switch it on with `wp creationell-theme module enable post-slider`, with the
constant `CREATIONELL_WP_THEME_MODULE_POST_SLIDER` in `wp-config.php`, with the
filter `creationell_wp_theme_modules` of a child theme or on the module page of
the theme settings (see `modules/README.md`). While the plugin bs Swiper is
active the module stays `off`.

## Block

**Post slider** (`creationell-theme/post-slider`):

| Field | Meaning |
|---|---|
| Layout | `columns`: cards side by side, 1 from 0 px, up to 2 from 768 px, up to 3 from 992 px, the chosen 1 to 4 from 1400 px; `heroes`: one large image card per slide, the text on a dark box at 75 % opacity |
| Transition | `slide`, or `fade` for large image cards |
| Loop | after the last slide the first follows |
| Play automatically | the slides move on by themselves after the delay of the setting; a pause button with visible text comes first in the slider |
| Show arrows, Show dots | previous and next buttons, one button per slide |

The posts are chosen like in the post list: post type, taxonomy and term IDs
(a comma list), selected posts, parent page, order, number of posts (1 to 50,
capped by `post_lists_max_posts` once the module `post-lists` registers it,
else 24) and "Leave out the current post". Display fields switch the image,
categories, date and author, excerpt, "Read more" and tags; the heading level
(2 to 6) fits the titles into the headings of the page.

On the page a block without posts prints nothing; the editor shows "No posts
found.". In the editor canvas the block shows a static grid of its first posts
(one large image card for `heroes`) without the slider script.

## Accessibility

- The slider is a `section` named "Post slider"; a second slider on the same
  page is "Post slider 2" and so on, so every landmark has its own name.
  Inside the related posts the slider is a `div` without name, because the
  section of the related posts names it. Swiper adds the role description
  "carousel", names the slides "1 / 5" and the dots "Go to slide 1". All
  texts come translated from `Slider_Config`; the script has none.
- Arrows and dots are buttons of at least 24 x 24 px; the arrow icons swap in
  right-to-left languages, and `div.swiper` carries `dir`.
- The keyboard module of Swiper stays off, so arrow keys keep scrolling the
  page; Tab reaches the links of all slides, the arrows and the dots.
- A playing slider pauses while focus is inside it (except on the pause
  button) or the pointer is over its slides, and goes on afterwards unless the
  pause button stopped it. The button changes its visible name between "Pause
  slideshow" and "Play slideshow" (no `aria-pressed`). The slides announce
  their change only while the slideshow stands still.
- Such a hold is not a pause: the button keeps "Pause slideshow". After an
  arrow or a dot the focus stays on it, so the slideshow stands until the
  focus leaves the slider; on touch screens a tap on the slides counts as
  pointer over them until the next tap elsewhere. The pause button stops the
  slideshow for good.
- With **Loop** Swiper 14 moves the slides round instead of copying them, so
  no link appears twice; the empty filler slides it may add hold no content.
- With `prefers-reduced-motion: reduce` slides change without animation and a
  slider does not play by itself; its button offers "Play slideshow".
- Without the script or without Swiper every post stays visible as a grid,
  and the buttons stay hidden.
- Swiper 14 supports Chrome and Edge 110, Firefox 110 and Safari 16.4 (iOS
  16.4) or later. Older browsers are not tested: Swiper 14 dropped their
  feature checks, and the grid above only shows when Swiper does not load.

## Template part

`template-parts/post-slider/slider.php` prints the slider, or the static grid
in the editor. A child theme overrides it under the same path. Arguments:
`query`, `display`, `options`, `layout`, `columns`, `editor`, `root`, `label`
(an empty label prints a `div` without name instead of the `section`).

CSS classes of the markup:

| Class | Element |
|---|---|
| `creationell-theme-post-slider` | root element, with `--columns` or `--heroes`, in the editor also `--canvas` |
| `creationell-theme-post-slider__pause` | pause button, first in the slider |
| `creationell-theme-post-slider__controls` | bar below the slides with arrows and dots |
| `creationell-theme-post-slider__prev`, `__next` | arrow buttons |
| `creationell-theme-post-slider__dots` | container of the dots (`swiper-pagination`) |

Other code shows its own query as a slider with
`Post_Slider_Module::render_query( $query, $attributes, $editor, $context )`
while the module loads (context `post-slider` or `related-posts`).

## Assets

`assets/post-slider.js` and `assets/post-slider.css` (handle
`creationell-wp-theme-post-slider`, both after `creationell-wp-theme-swiper`;
the stylesheet also after `creationell-wp-theme-main`, the script deferred in
the footer) load early on a singular page that holds the block, otherwise when
a slider renders. The editor canvas gets the stylesheet only.

## Hooks

| Hook | Arguments |
|---|---|
| `creationell_wp_theme_swiper_options` | `array $options`, `string $context` (`post-slider`, `related-posts`) |
| `creationell_wp_theme_post_query_args` | `array $args`, `string $context` (`post-slider`), `array $attributes` |
| `creationell_wp_theme_post_card_classes` | `array<string,string> $classes`, `string $part` (`card-grid`, `card-hero`), `string $context` |

```php
add_filter(
	'creationell_wp_theme_swiper_options',
	static function ( array $options ): array {
		$options['speed'] = 600;
		return $options;
	}
);
```

## States

- `active`: the block is in the inserter.
- `hidden`: existing sliders keep working, the inserter offers the block no more.
- `off`: nothing loads; existing blocks of the module render nothing.

The module stores nothing and has no lifecycle callback.
