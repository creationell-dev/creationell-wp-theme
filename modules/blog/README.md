# Module blog

Blog parts for the posts of the site: a sidebar with categories and tags, a
meta line with reading time, a link back to the blog page, categories and tags
below a post, share links, a richer 404 page and optional author pages. The
helpers that work whether the module is on or off (blog URL, reading time,
blog context) live in `src/blog/`.

| | |
|---|---|
| States | `active`, `off` (default `off`) |
| Needs | nothing: no plugin, no other module, no Blockstudio |
| Replaces | nothing |
| Blocks | none |
| Settings | see below |
| Outposts | `template-parts/blog/` |

Switch it on in *Theme settings > Modules*, with
`wp creationell-theme module enable blog --user=<admin>`, with the constant
`CREATIONELL_WP_THEME_MODULE_BLOG` in `wp-config.php` or with the filter
`creationell_wp_theme_modules` of a child theme (see `modules/README.md`).
The module `post-filter` needs this module.

## Settings

The settings are registered for the installed module whatever its state; the
module reads them when its hooks run, from `init` on.

| Key | Section | Value |
|---|---|---|
| `blog_sidebar_terms` | design, advanced | term lists in the blog sidebar: `both` (default), `categories`, `tags` or `off` |
| `blog_sidebar_counts` | design, advanced | number of posts in the term lists, default on |
| `blog_sidebar_mobile` | design, advanced | blog sidebar below `lg`: `below` the content (default) or in the `offcanvas` panel |
| `blog_category_badge` | design, advanced | category badge above the title of a single post, default on |
| `blog_meta_date` | design, advanced | date in the meta line, default on |
| `blog_meta_reading_time` | design, advanced | reading time in the meta line, default on |
| `blog_meta_author` | design, advanced | author in the meta line, default on |
| `blog_meta_comments` | design, advanced | comments in the meta line, default on |
| `blog_reading_wpm` | design, advanced | words per minute for the reading time, 100 to 400, default 200 |
| `blog_back_link` | design, advanced | link back to the blog page above a single post, default on |
| `blog_entry_terms` | design, advanced | terms below a single post: `both` (default), `categories`, `tags` or `off` |
| `blog_share` | design, advanced | share links: `auto` (default; Shariff while it is active, else the own links), `shariff`, `links` or `off` |
| `blog_share_networks` | design, advanced | comma list of the own share links, default `linkedin,facebook,whatsapp,email,copy`; also `x`, `bluesky`, `threads` |
| `blog_404_posts` | design, advanced | latest posts on the 404 page, 0 to 6, default 3; 0 shows none |
| `blog_404_search` | design, advanced | search form on the 404 page, default on |
| `blog_author_pages` | design, advanced | public author pages, default off; while off, author addresses show the 404 page |
| `blog_label_categories` | texts, basic, per language | up to 120 characters; empty shows "Categories" |
| `blog_label_tags` | texts, basic, per language | up to 120 characters; empty shows "Tags" |
| `blog_back_link_text` | texts, basic, per language | up to 120 characters; empty shows "Back to {title}", where `{title}` is the title of the blog page |
| `blog_share_title` | texts, basic, per language | up to 120 characters; empty shows "Share this post" |

The settings travel with the settings import and export. An export of an
older theme version without these keys leaves them as they are.

## What it adds

- **Blog page** (`home.php`): header with the title and the excerpt of the
  posts page (the tagline with posts on the front page), the post loop of
  `index.php` and, while the site has no published post, a note with a search
  form. When the site has posts but the page lists none, for example after a
  filter that matches nothing, it shows "No posts match these filters."
  instead (filter `creationell_wp_theme_blog_home_empty`). While the module is
  off, or while a child theme has its own `index.php` but no `home.php`,
  WordPress gets `index.php` as before.
- **Blog sidebar**: categories and tags at the top of the sidebar
  (`blog_sidebar_terms`), shown also without widgets, with the accessible name
  "Blog sidebar"; below `lg` under the content or in the offcanvas panel
  (`blog_sidebar_mobile`). The blog view is the blog page, a single post, a
  category, tag or date archive and an author archive of posts; search results
  are none.
- **Single post**: a link back to the blog page above the title, the category
  badge (`blog_category_badge`), a meta line with date, reading time (not on
  a post behind a password), author and comments, the categories and tags
  below the content (`blog_entry_terms`) and a share section.
- **Share section**: the buttons of the plugin Shariff while it is active, or
  the own links of the theme (`blog_share_networks`). The own links load
  nothing from a third party before a click; "Copy link" and the share dialog
  of the system appear only where the browser offers them. A draft or preview
  shows no share section, and while Shariff inserts its buttons itself the
  theme prints none. The per-post switches of the Shariff meta box count only
  while Shariff is active; left behind after Shariff is removed, they hide
  nothing.
- **404 page**: after the widget area a search form (`blog_404_search`), the
  menu of the location "404 page links" and the latest posts
  (`blog_404_posts`) with a link "To the blog".
- **Author pages** (`author.php`, setting `blog_author_pages`): a header with
  profile image, name, job title, biography and links, then the posts of the
  author. Only authors with a published post have a page; any other author
  address, its feed and its later pages answer with the 404 page and never
  redirect to the address with the login. The check runs on `wp`, before SEO
  plugins pick their page type there, so their title and meta tags describe
  the 404 page and not the user. While the setting is off every
  author address gives the 404 page, the byline names the author without a
  link and the sitemap lists no users.
- **Profile fields** (*Users > Profile*, for users who may write posts while
  the author pages are on): profile image from the media library, job title
  and external profiles, stored in the user meta
  `creationell_wp_theme_author_image`, `creationell_wp_theme_author_job_title`
  and `creationell_wp_theme_author_profiles`. The profile image replaces
  Gravatar as avatar; without one the author page shows no image and the
  avatar follows the discussion settings (Gravatar or the default avatar).
  An image someone else uploaded is stored only when the current user may
  edit it and it is public already; otherwise the old value stays and the
  profile screen says so once. Users who may not edit the posts of others
  see only their own uploads in the media library.

The reading time is stored in the post meta `_creationell_wp_theme_reading_time`
(see `src/blog/README.md`).

## Template parts

A child theme overrides a part at the same path. Each part gets its data as
raw values in `$args` and escapes them itself. A part with one heading takes
its ID from `heading_id` (string), a part with several headings from
`heading_ids` (array by section); a missing ID comes from `wp_unique_id()`.

| Part | Printed on | Arguments |
|---|---|---|
| `template-parts/blog/back-link.php` | `creationell_wp_theme_entry_header_open` | `url`, `text`, `context` |
| `template-parts/blog/entry-meta.php` | filter `creationell_wp_theme_entry_meta_html` | `parts` (`date`, `reading_time`, `author`, `comments` in order), `minutes`, `comments_number`, `context` |
| `template-parts/blog/entry-terms.php` | filter `creationell_wp_theme_entry_terms_html` | `categories`, `tags` (name and url), `label_categories`, `label_tags`, `heading_ids` (`categories`, `tags`), `context` |
| `template-parts/blog/share.php` | `creationell_wp_theme_after_entry_terms` | `heading`, `heading_id`, `shariff` (markup of Shariff), `items` (network, name, url, icon), `url`, `title`, `context` |
| `template-parts/blog/sidebar-terms.php` | `creationell_wp_theme_before_sidebar_widgets` | `categories`, `tags` (term, url, count, current, children), `counts`, `label_categories`, `label_tags`, `heading_ids` (`categories`, `tags`) |
| `template-parts/blog/404-content.php` | `creationell_wp_theme_404_after_content` | `search`, `menu`, `posts` (title, url, datetime, date), `blog_url`, `heading_ids` (`menu`, `posts`), `context` |
| `template-parts/blog/home-header.php` | `home.php` | `title`, `excerpt`, `context` (`home`) |
| `template-parts/blog/home-none.php` | `home.php` while the site has no published post | `context` (`home`) |
| `template-parts/blog/home-no-match.php` | `home.php` with an empty list on a site with posts, while `creationell_wp_theme_blog_home_empty` gives `true` | `context` (`home`) |
| `template-parts/blog/author-header.php` | `author.php` | `name`, `image_id`, `image_size`, `job_title`, `bio`, `links` (url and text), `context` (`author`) |

The context `home` is passed only to the new parts; the hooks of `home.php`
keep the context `index` of `index.php`, those of `author.php` the context
`archive` of `archive.php`.

## Hooks

The helpers of `src/blog/` and the templates offer these hooks whether the
module is on or off; the module answers them while it is `active`.

| Hook | Type | Arguments | Purpose |
|---|---|---|---|
| `creationell_wp_theme_blog_url` | filter | `string $url` | URL of the blog page, for the back link and "To the blog" |
| `creationell_wp_theme_reading_time_wpm` | filter | `int $wpm`, `WP_Post $post` | words per minute of the reading time; kept between 100 and 1000 |
| `creationell_wp_theme_is_blog_view` | filter | `bool $is_blog_view` | whether the request is a blog view; only `true` counts |
| `creationell_wp_theme_entry_header_open` | action | `string $context` | in the header of a single post, before the category badge |
| `creationell_wp_theme_show_category_badge` | filter | `bool $show`, `string $context` | whether the category badge shows; the context is empty in cards and post lists |
| `creationell_wp_theme_entry_meta_html` | filter | `string $html`, `string $context` | markup of the meta line; empty keeps the meta line of the template |
| `creationell_wp_theme_entry_terms_html` | filter | `string $html`, `string $context` | markup of the terms below a post; empty keeps the tags of the template |
| `creationell_wp_theme_after_entry_terms` | action | `string $context` | after the terms, before related posts, post navigation and comments |
| `creationell_wp_theme_404_after_content` | action | `string $context` (`404`) | on the 404 page after the widget area, before the link home |
| `creationell_wp_theme_sidebar_aria_label` | filter | `string $label` | accessible name of `aside#secondary`; empty prints no attribute |
| `creationell_wp_theme_blog_sidebar_force` | filter | `bool $force` | shows the sidebar without widgets (columns, body class and `sidebar.php`) |
| `creationell_wp_theme_meta_author_link` | filter | `bool $link`, `int $user_id` | whether the byline links to the author archive |
| `creationell_wp_theme_blog_sidebar_terms` | filter | `bool $show` | whether the blog sidebar shows the term lists; only `true` counts |
| `creationell_wp_theme_blog_sidebar_has_filter` | filter | `bool $has_filter` | whether the sidebar shows the filter bar of the module `post-filter`; only `true` counts |
| `creationell_wp_theme_shariff_atts` | filter | `array $atts`, `WP_Post $post` | attributes for `shariff3uu_render()`: `url`, `title`, `headline`, `theme` |
| `creationell_wp_theme_author_is_public` | filter | `bool $is_public`, `WP_User $user` | whether a user has a public author page; default: a published post |
| `creationell_wp_theme_author_image_size` | filter | `string\|array $size`, `WP_User $user` | image size of the profile image on the author page; default `medium` |
| `creationell_wp_theme_blog_home_empty` | filter | `bool $show` | whether the blog page prints "No posts match these filters." when its list is empty although the site has posts; only `true` counts. The module `post-filter` answers `false` while the plugin prints its own zero results zone |

```php
add_filter(
	'creationell_wp_theme_blog_sidebar_terms',
	static fn( bool $show ): bool => $show && ! is_singular( 'post' )
);
```

## Child themes

A child theme with its own `single.php`, `single-post.php` or
`single-templates/*.php` keeps these calls at the same places, so the blog
parts still show:
`do_action( 'creationell_wp_theme_entry_header_open', $context )`,
`creationell_wp_theme_category_badge( $context )`,
`creationell_wp_theme_entry_meta( $context )`,
`creationell_wp_theme_entry_terms( $context )` and
`do_action( 'creationell_wp_theme_after_entry_terms', $context )`. An own
`sidebar.php` applies `creationell_wp_theme_blog_sidebar_force` and
`creationell_wp_theme_sidebar_aria_label` and fires
`creationell_wp_theme_before_sidebar_widgets`; an own `404.php` fires
`creationell_wp_theme_404_after_content`. A child `index.php` without
`home.php` keeps the blog page of the module off, a child `archive.php`
without `author.php` the author pages.

## Command line

`wp creationell-theme doctor` has the section `blog`: `state`, `blog_url`,
`author_pages`, `share_mode` (`off`, `shariff` or `links`) and `post_filter`,
and with findings `warnings`: no blog page, child templates that win over the
module or lack its calls (also single templates in the root of the child
theme), Shariff inserting its own buttons or using a third party, the module
`post-filter` without a filter bar, authors whose name or address shows the
login (also a nicename WordPress derived from the login, such as
`anna-schmidt` from `anna.schmidt`), author pages off while an SEO plugin
lists authors.

```sh
wp creationell-theme doctor --format=json | jq '.blog'
```

## States

- `active`: the blog parts show on the posts of the site.
- `off`: nothing loads; the blog page, the single posts, the archives and the
  404 page look as without the module.

The module writes no option and has no lifecycle callback; it stores only the
profile fields in the user meta.
