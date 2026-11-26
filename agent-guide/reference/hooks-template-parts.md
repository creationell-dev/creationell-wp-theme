<!-- Generated from the source. Do not edit. -->

# Hooks: template parts

Hooks whose first path lies in `template-parts/`; a hook fired in several folders is listed by its first path.

## `creationell_wp_theme_after_loop_item`

Action. Fires after a loop item.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay-term.php`, `template-parts/loop/cards-overlay.php`, `template-parts/loop/custom.php`

## `creationell_wp_theme_after_loop_tags`

Action. Fires after the tags of a loop item.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay-term.php`, `template-parts/loop/cards-overlay.php`

## `creationell_wp_theme_after_loop_thumbnail`

Action. Fires after the thumbnail of a loop item.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay-term.php`, `template-parts/loop/cards-overlay.php`

## `creationell_wp_theme_after_loop_title`

Action. Fires after the title of a loop item.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay-term.php`, `template-parts/loop/cards-overlay.php`

## `creationell_wp_theme_after_nav_toggler`

Action. Fires after the button that opens the main menu.

No parameters.

- Fired in: `template-parts/header/navbar.php`

## `creationell_wp_theme_after_navbar_brand`

Action. Fires after the logo link in the navbar.

No parameters.

- Fired in: `template-parts/header/navbar.php`

## `creationell_wp_theme_before_loop_item`

Action. Fires before a loop item.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay-term.php`, `template-parts/loop/cards-overlay.php`, `template-parts/loop/custom.php`

## `creationell_wp_theme_before_loop_thumbnail`

Action. Fires before the thumbnail of a loop item.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay-term.php`, `template-parts/loop/cards-overlay.php`

## `creationell_wp_theme_before_loop_title`

Action. Fires before the title of a loop item.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay-term.php`, `template-parts/loop/cards-overlay.php`

## `creationell_wp_theme_before_navbar_brand`

Action. Fires before the logo link in the navbar.

No parameters.

- Fired in: `template-parts/header/navbar.php`

## `creationell_wp_theme_bs_loop_term_count_label`

Filter. Filters the label after the number of posts in a term card.

| Parameter | Type | Description |
|---|---|---|
| `$label` | `string` | Translated label, singular or plural. |
| `$term` | `WP_Term` | Term of the card. |
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Returns: the filtered `$label`.
- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-overlay-term.php`

## `creationell_wp_theme_custom_loop_item`

Action. Fires to render a loop item of a custom layout.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Fired in: `template-parts/loop/custom.php`

## `creationell_wp_theme_footer_meta`

Action. Fires after the footer text or the copyright line in the footer info.

Prints further links of the footer info, for example the link that opens the cookie settings again.

No parameters.

- Fired in: `template-parts/footer/footer-info.php`

## `creationell_wp_theme_loop_category`

Filter. Filters whether a loop item shows its categories.

| Parameter | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether to show the categories. |
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Returns: the filtered `$show`.
- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay.php`

## `creationell_wp_theme_loop_excerpt`

Filter. Filters whether a loop item shows the excerpt.

| Parameter | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether to show the excerpt. |
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Returns: the filtered `$show`.
- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay-term.php`, `template-parts/loop/cards-overlay.php`

## `creationell_wp_theme_loop_item_after_card_body`

Action. Fires after the card body of a loop item.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Contexts: `cards-grid`, `cards-horizontal`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-horizontal.php`

## `creationell_wp_theme_loop_item_after_card_img_overlay`

Action. Fires after the image overlay of a loop item card.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Contexts: `cards-overlay`
- Fired in: `template-parts/loop/cards-overlay-term.php`, `template-parts/loop/cards-overlay.php`

## `creationell_wp_theme_loop_meta`

Filter. Filters whether a loop item shows the post meta.

| Parameter | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether to show the post meta. |
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Returns: the filtered `$show`.
- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay-term.php`, `template-parts/loop/cards-overlay.php`

## `creationell_wp_theme_loop_read_more`

Filter. Filters whether a loop item shows the read more link.

| Parameter | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether to show the read more link. |
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Returns: the filtered `$show`.
- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay-term.php`, `template-parts/loop/cards-overlay.php`

## `creationell_wp_theme_loop_read_more_text`

Filter. Filters the text of the read more link of a loop item.

| Parameter | Type | Description |
|---|---|---|
| `$text` | `string` | Link text, may contain HTML. |
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Returns: the filtered `$text`.
- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid-term.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal-term.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay-term.php`, `template-parts/loop/cards-overlay.php`

## `creationell_wp_theme_loop_tags`

Filter. Filters whether a loop item shows its tags.

| Parameter | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether to show the tags. |
| `$context` | `string` | Template part of the loop item, for example cards-grid. |

- Returns: the filtered `$show`.
- Contexts: `cards-grid`, `cards-horizontal`, `cards-overlay`
- Fired in: `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay.php`

## `creationell_wp_theme_offcanvas_navbar_title`

Filter. Filters the title of the offcanvas main menu.

| Parameter | Type | Description |
|---|---|---|
| `$title` | `string` | Title. |

- Returns: the filtered `$title`.
- Fired in: `template-parts/header/navbar.php`
