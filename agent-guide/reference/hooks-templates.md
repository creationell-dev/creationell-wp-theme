<!-- Generated from the source. Do not edit. -->

# Hooks: templates

Hooks whose first path is a root template or lies in `inc/`, `patterns/`, `page-templates/` or `single-templates/`.

## `creationell_wp_theme_404_after_content`

Action. Fires on the 404 page after the widget area and before the link to the home page.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template, here 404. |

- Contexts: `404`
- Fired in: `404.php`

## `creationell_wp_theme_after_entry_terms`

Action. Fires after the tags of a post, before related posts, the post navigation and the comments.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template, for example single or single-sidebar-left. |

- Contexts: `single`, `single-full-width-image`, `single-sidebar-left`, `single-sidebar-none`
- Fired in: `single-templates/single-full-width-image.php`, `single-templates/single-sidebar-left.php`, `single-templates/single-sidebar-none.php`, `single.php`

## `creationell_wp_theme_after_featured_image`

Action. Fires after the featured image of a page or post.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template, for example page or archive. |

- Contexts: `page`, `page-full-width-image`, `page-sidebar-left`, `page-sidebar-none`, `single`, `single-full-width-image`, `single-sidebar-left`, `single-sidebar-none`
- Fired in: `page-templates/page-full-width-image.php`, `page-templates/page-sidebar-left.php`, `page-templates/page-sidebar-none.php`, `page.php`, `single-templates/single-full-width-image.php`, `single-templates/single-sidebar-left.php`, `single-templates/single-sidebar-none.php`, `single.php`

## `creationell_wp_theme_after_loop`

Action. Fires after the post loop of an archive template.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template, for example page or archive. |

- Contexts: `archive`, `index`, `search`
- Fired in: `archive.php`, `author.php`, `home.php`, `index.php`, `search.php`

## `creationell_wp_theme_after_masthead`

Action. Fires after the site header.

No parameters.

- Fired in: `header.php`

## `creationell_wp_theme_after_masthead_open`

Action. Fires right after the opening tag of the site header.

No parameters.

- Fired in: `header.php`

## `creationell_wp_theme_after_primary_open`

Action. Fires right after the opening tag of the main content area.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template, for example page or archive. |

- Contexts: `404`, `archive`, `index`, `page`, `page-full-width-image`, `page-sidebar-left`, `page-sidebar-none`, `search`, `single`, `single-full-width-image`, `single-sidebar-left`, `single-sidebar-none`
- Fired in: `404.php`, `archive.php`, `author.php`, `home.php`, `index.php`, `page-templates/page-full-width-image.php`, `page-templates/page-sidebar-left.php`, `page-templates/page-sidebar-none.php`, `page.php`, `search.php`, `single-templates/single-full-width-image.php`, `single-templates/single-sidebar-left.php`, `single-templates/single-sidebar-none.php`, `single.php`

## `creationell_wp_theme_after_sidebar_widgets`

Action. Fires after the widgets of the sidebar.

No parameters.

- Fired in: `sidebar.php`

## `creationell_wp_theme_after_title`

Action. Fires after the page title.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template, for example page or archive. |

- Contexts: `404`, `archive`, `index`, `page`, `page-full-width-image`, `page-sidebar-left`, `page-sidebar-none`, `search`, `single`, `single-full-width-image`, `single-sidebar-left`, `single-sidebar-none`
- Fired in: `404.php`, `archive.php`, `author.php`, `home.php`, `index.php`, `page-templates/page-full-width-image.php`, `page-templates/page-sidebar-left.php`, `page-templates/page-sidebar-none.php`, `page.php`, `search.php`, `single-templates/single-full-width-image.php`, `single-templates/single-sidebar-left.php`, `single-templates/single-sidebar-none.php`, `single.php`

## `creationell_wp_theme_before_entry_footer`

Action. Fires before the footer of a page or post.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template, for example page or archive. |

- Contexts: `page`, `page-full-width-image`, `page-sidebar-left`, `page-sidebar-none`, `single`, `single-full-width-image`, `single-sidebar-left`, `single-sidebar-none`
- Fired in: `page-templates/page-full-width-image.php`, `page-templates/page-sidebar-left.php`, `page-templates/page-sidebar-none.php`, `page.php`, `single-templates/single-full-width-image.php`, `single-templates/single-sidebar-left.php`, `single-templates/single-sidebar-none.php`, `single.php`

## `creationell_wp_theme_before_footer`

Action. Fires before the site footer.

No parameters.

- Fired in: `footer.php`

## `creationell_wp_theme_before_loop`

Action. Fires before the post loop of an archive template.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template, for example page or archive. |

- Contexts: `archive`, `index`, `search`
- Fired in: `archive.php`, `author.php`, `home.php`, `index.php`, `search.php`

## `creationell_wp_theme_before_loop_pagination`

Action. Fires before the pagination of the post loop.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template, for example page or archive. |

- Contexts: `archive`, `index`, `search`
- Fired in: `archive.php`, `author.php`, `home.php`, `index.php`, `search.php`

## `creationell_wp_theme_before_masthead`

Action. Fires before the site header.

No parameters.

- Fired in: `header.php`

## `creationell_wp_theme_before_masthead_close`

Action. Fires right before the closing tag of the site header.

No parameters.

- Fired in: `header.php`

## `creationell_wp_theme_before_sidebar_widgets`

Action. Fires before the widgets of the sidebar.

No parameters.

- Fired in: `sidebar.php`

## `creationell_wp_theme_before_single_pagination`

Action. Fires before the links to the previous and next post.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template, for example page or archive. |

- Contexts: `single`, `single-full-width-image`, `single-sidebar-left`, `single-sidebar-none`
- Fired in: `single-templates/single-full-width-image.php`, `single-templates/single-sidebar-left.php`, `single-templates/single-sidebar-none.php`, `single.php`

## `creationell_wp_theme_before_title`

Action. Fires before the page title.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template, for example page or archive. |

- Contexts: `404`, `archive`, `index`, `page`, `page-full-width-image`, `page-sidebar-left`, `page-sidebar-none`, `search`, `single`, `single-full-width-image`, `single-sidebar-left`, `single-sidebar-none`
- Fired in: `404.php`, `archive.php`, `author.php`, `home.php`, `index.php`, `page-templates/page-full-width-image.php`, `page-templates/page-sidebar-left.php`, `page-templates/page-sidebar-none.php`, `page.php`, `search.php`, `single-templates/single-full-width-image.php`, `single-templates/single-sidebar-left.php`, `single-templates/single-sidebar-none.php`, `single.php`

## `creationell_wp_theme_block_archives_content`

Filter. Filters the HTML of the archives block after the theme added its Bootstrap classes.

| Parameter | Type | Description |
|---|---|---|
| `$block_content` | `string` | Block HTML. |
| `$block` | `array` | Parsed block with name and attributes. |

- Returns: the filtered `$block_content`.
- Fired in: `inc/blocks/block-widget-archives.php`

## `creationell_wp_theme_block_buttons_content`

Filter. Filters the HTML of the buttons block after the theme added its Bootstrap classes.

| Parameter | Type | Description |
|---|---|---|
| `$block_content` | `string` | Block HTML. |
| `$block` | `array` | Parsed block with name and attributes. |

- Returns: the filtered `$block_content`.
- Fired in: `inc/blocks/block-buttons.php`

## `creationell_wp_theme_block_calendar_content`

Filter. Filters the HTML of the calendar block after the theme added its Bootstrap classes.

| Parameter | Type | Description |
|---|---|---|
| `$block_content` | `string` | Block HTML. |
| `$block` | `array` | Parsed block with name and attributes. |

- Returns: the filtered `$block_content`.
- Fired in: `inc/blocks/block-widget-calendar.php`

## `creationell_wp_theme_block_categories_content`

Filter. Filters the HTML of the categories block after the theme added its Bootstrap classes.

| Parameter | Type | Description |
|---|---|---|
| `$block_content` | `string` | Block HTML. |
| `$block` | `array` | Parsed block with name and attributes. |

- Returns: the filtered `$block_content`.
- Fired in: `inc/blocks/block-widget-categories.php`

## `creationell_wp_theme_block_code_content`

Filter. Filters the HTML of the code block after the theme added its Bootstrap classes.

| Parameter | Type | Description |
|---|---|---|
| `$block_content` | `string` | Block HTML. |
| `$block` | `array` | Parsed block with name and attributes. |

- Returns: the filtered `$block_content`.
- Fired in: `inc/blocks/block-code.php`

## `creationell_wp_theme_block_latest_comments_content`

Filter. Filters the HTML of the latest comments block after the theme added its Bootstrap classes.

| Parameter | Type | Description |
|---|---|---|
| `$block_content` | `string` | Block HTML. |
| `$block` | `array` | Parsed block with name and attributes. |

- Returns: the filtered `$block_content`.
- Fired in: `inc/blocks/block-widget-latest-comments.php`

## `creationell_wp_theme_block_latest_posts_content`

Filter. Filters the HTML of the latest posts block after the theme added its Bootstrap classes.

| Parameter | Type | Description |
|---|---|---|
| `$block_content` | `string` | Block HTML. |
| `$block` | `array` | Parsed block with name and attributes. |

- Returns: the filtered `$block_content`.
- Fired in: `inc/blocks/block-widget-latest-posts.php`

## `creationell_wp_theme_block_quote_content`

Filter. Filters the HTML of the quote block after the theme added its Bootstrap classes.

| Parameter | Type | Description |
|---|---|---|
| `$block_content` | `string` | Block HTML. |
| `$block` | `array` | Parsed block with name and attributes. |

- Returns: the filtered `$block_content`.
- Fired in: `inc/blocks/block-quote.php`

## `creationell_wp_theme_block_search_content`

Filter. Filters the HTML of the search block after the theme added its Bootstrap classes.

| Parameter | Type | Description |
|---|---|---|
| `$block_content` | `string` | Block HTML. |
| `$block` | `array` | Parsed block with name and attributes. |

- Returns: the filtered `$block_content`.
- Fired in: `inc/blocks/block-widget-search.php`

## `creationell_wp_theme_block_table_content`

Filter. Filters the HTML of the table block after the theme added its Bootstrap classes.

| Parameter | Type | Description |
|---|---|---|
| `$block_content` | `string` | Block HTML. |
| `$block` | `array` | Parsed block with name and attributes. |

- Returns: the filtered `$block_content`.
- Fired in: `inc/blocks/block-table.php`

## `creationell_wp_theme_blog_home_empty`

Filter. Filters whether the blog page prints the note "No posts match these filters." when its main query is empty although the blog has posts.

The module post-filter answers false where the plugin JPKCom Post Filter prints its own zero results zone. Only true counts; any other answer prints nothing.

Example:

```php
add_filter( 'creationell_wp_theme_blog_home_empty', '__return_false' );
```

| Parameter | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether the note shows; true by default. |

- Returns: the filtered `$show`.
- Fired in: `home.php`

## `creationell_wp_theme_blog_sidebar_force`

Filter. Filters whether the sidebar shows without widgets, for example with the term lists of the blog.

| Parameter | Type | Description |
|---|---|---|
| `$force` | `bool` | Whether to show the sidebar without widgets. |

- Returns: the filtered `$force`.
- Fired in: `inc/columns.php`, `inc/template-functions.php`, `sidebar.php`

## `creationell_wp_theme_breadcrumb_handler`

Filter. Filters whether another plugin renders the breadcrumb instead of the theme.

| Parameter | Type | Description |
|---|---|---|
| `$handled` | `bool` | Whether the breadcrumb is already rendered. |

- Returns: the filtered `$handled`.
- Fired in: `inc/breadcrumb.php`

## `creationell_wp_theme_comments_closed_text`

Filter. Filters the notice that comments are closed.

| Parameter | Type | Description |
|---|---|---|
| `$text` | `string` | Escaped notice text. |

- Returns: the filtered `$text`.
- Fired in: `comments.php`

## `creationell_wp_theme_entry_header_open`

Action. Fires in the header of a post, after the post is set up and before the category badges.

| Parameter | Type | Description |
|---|---|---|
| `$context` | `string` | Template, for example single or single-sidebar-left. |

- Contexts: `single`, `single-full-width-image`, `single-sidebar-left`, `single-sidebar-none`
- Fired in: `single-templates/single-full-width-image.php`, `single-templates/single-sidebar-left.php`, `single-templates/single-sidebar-none.php`, `single.php`

## `creationell_wp_theme_entry_meta_html`

Filter. Filters the markup of the meta line of a post; empty keeps the meta line of the template.

| Parameter | Type | Description |
|---|---|---|
| `$html` | `string` | Markup of the meta line, empty for none. |
| `$context` | `string` | Template, for example single or single-sidebar-left. |

- Returns: the filtered `$html`.
- Fired in: `inc/template-tags.php`

## `creationell_wp_theme_entry_terms_html`

Filter. Filters the markup of the categories and tags below a post; empty keeps the tags of the template.

| Parameter | Type | Description |
|---|---|---|
| `$html` | `string` | Markup of the terms, empty for none. |
| `$context` | `string` | Template, for example single or single-sidebar-left. |

- Returns: the filtered `$html`.
- Fired in: `inc/template-tags.php`

## `creationell_wp_theme_footer_columns_after_container`

Action. Fires after the container of the footer columns.

No parameters.

- Fired in: `footer.php`

## `creationell_wp_theme_footer_columns_after_container_open`

Action. Fires right after the opening tag of the container of the footer columns.

No parameters.

- Fired in: `footer.php`

## `creationell_wp_theme_footer_columns_before_container`

Action. Fires before the container of the footer columns.

No parameters.

- Fired in: `footer.php`

## `creationell_wp_theme_footer_columns_before_container_close`

Action. Fires right before the closing tag of the container of the footer columns.

No parameters.

- Fired in: `footer.php`

## `creationell_wp_theme_footer_columns_before_footer_menu`

Action. Fires before the footer menu.

No parameters.

- Fired in: `footer.php`

## `creationell_wp_theme_footer_info_after_container_open`

Action. Fires right after the opening tag of the container of the footer info.

No parameters.

- Fired in: `footer.php`

## `creationell_wp_theme_icon_<name>`

Filter. Filters the SVG markup of an icon; the hook name ends with the icon name.

The markup should keep aria-hidden="true" and focusable="false": icons are decoration, the text next to them or a label names the control.

| Parameter | Type | Description |
|---|---|---|
| `$svg` | `string` | Default SVG markup of the icon. |

- Returns: the filtered `$svg`.
- Fired in: `inc/icons.php`

## `creationell_wp_theme_loop_layout`

Filter. Filters the layout of the post loop.

| Parameter | Type | Description |
|---|---|---|
| `$layout` | `string` | Layout, horizontal or grid. |
| `$context` | `string` | Template, for example page or archive. |

- Returns: the filtered `$layout`.
- Contexts: `archive`, `index`, `search`
- Fired in: `archive.php`, `author.php`, `home.php`, `index.php`, `search.php`

## `creationell_wp_theme_loop_pagination`

Action. Fires where the pagination of the post loop is rendered.

No parameters.

- Fired in: `archive.php`, `author.php`, `home.php`, `index.php`, `search.php`

## `creationell_wp_theme_meta_author`

Filter. Filters whether the post meta shows the author.

| Parameter | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether to show the author. |

- Returns: the filtered `$show`.
- Fired in: `inc/template-tags.php`, `modules/blog/inc/class-entry-parts.php`

## `creationell_wp_theme_meta_author_link`

Filter. Filters whether the author name in the post meta links to the author archive.

| Parameter | Type | Description |
|---|---|---|
| `$link` | `bool` | Whether to link the author name. |
| `$user_id` | `int` | ID of the author. |

- Returns: the filtered `$link`.
- Fired in: `inc/template-tags.php`

## `creationell_wp_theme_meta_time_updated`

Filter. Filters whether the post date shows the date of the last update.

| Parameter | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether to show the date of the last update. |

- Returns: the filtered `$show`.
- Fired in: `inc/template-tags.php`

## `creationell_wp_theme_offcanvas_sidebar_button_text`

Filter. Filters the text of the button that opens the sidebar.

| Parameter | Type | Description |
|---|---|---|
| `$text` | `string` | Button text. |

- Returns: the filtered `$text`.
- Fired in: `sidebar.php`

## `creationell_wp_theme_offcanvas_sidebar_title`

Filter. Filters the title of the offcanvas sidebar.

| Parameter | Type | Description |
|---|---|---|
| `$title` | `string` | Title. |

- Returns: the filtered `$title`.
- Fired in: `sidebar.php`

## `creationell_wp_theme_show_category_badge`

Filter. Filters whether the category badges of a post show.

| Parameter | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether to show the category badges. |
| `$context` | `string` | Template, for example single or single-sidebar-left; empty in cards and post lists. |

- Returns: the filtered `$show`.
- Fired in: `inc/template-tags.php`

## `creationell_wp_theme_show_tag_icon`

Filter. Filters whether tag badges show an icon.

| Parameter | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether to show the icon. |

- Returns: the filtered `$show`.
- Fired in: `inc/template-tags.php`, `template-parts/blog/sidebar-terms.php`

## `creationell_wp_theme_sidebar_aria_label`

Filter. Filters the accessible name of the sidebar landmark.

| Parameter | Type | Description |
|---|---|---|
| `$label` | `string` | Accessible name; empty for none, then the aside gets no aria-label. |

- Returns: the filtered `$label`.
- Fired in: `sidebar.php`

## `creationell_wp_theme_sidebar_has_widgets`

Filter. Filters whether a widget area counts as filled.

| Parameter | Type | Description |
|---|---|---|
| `$has_widgets` | `bool` | Whether the area has widgets. |
| `$sidebar` | `string` | Widget area ID. |

- Returns: the filtered `$has_widgets`.
- Fired in: `sidebar.php`, `template-parts/header/actions.php`, `template-parts/header/collapse-search.php`
