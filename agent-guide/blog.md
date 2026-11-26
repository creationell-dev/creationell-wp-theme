# Blog

The module `blog` adds the parts of a blog to the posts of the site: a sidebar with categories and tags, a meta line with reading time, a back link, terms below a post, share links, a richer 404 page and optional author pages. This file covers the tasks and the traps; the full reference of settings, template parts and hooks is the [README of the module](../modules/blog/README.md).

Every `settings set` and `module enable` below writes: on a site that is not local, ask the human first. Settings commands need `--user=<login>` of a user with the capability of the setting.

## Switch it on

- `wp creationell-theme module enable blog --user=<login>`, the switch on Theme settings > Modules, or a default in the child theme: `$modules['blog'] = array( 'default' => 'active' );` in the filter `creationell_wp_theme_modules` ([child-theme.md](child-theme.md#the-four-filters)).
- The module needs no plugin and no other module. Check it with `wp creationell-theme module status blog`.
- Trap: the author pages (`blog_author_pages`) are off by default, so once the module is on every author address of the site answers with the 404 page, `?author=<id>` and the author feeds included. If the site keeps its author pages, switch them on ([Blog page and author pages](#blog-page-and-author-pages)).
- The blog page is the posts page of Settings > Reading (`wp option get page_for_posts`); with posts on the front page it is the front page. `wp creationell-theme doctor` warns in its section `blog` when there is none.

## Blog page and author pages

- `home.php` renders the blog page and `author.php` the author pages. They fall back to `index.php` and `archive.php` while the module is off.
- Trap: a child theme with its own `index.php` but no `home.php` keeps the blog page of the module off; one with its own `archive.php` but no `author.php` keeps the author pages off. Override `home.php` or `author.php` as well, or remove the old override ([architecture.md](architecture.md#template-switches)).
- Author pages are off by default: `wp creationell-theme settings set blog_author_pages 1 --user=<login>`. Only authors with a published post get a page; every other author address answers with the 404 page. While the setting is off, every author address answers with the 404 page, the byline names the author without a link and the sitemap lists no users.

## Sidebar

- The blog sidebar shows categories and tags at the top of the sidebar, also without widgets: `blog_sidebar_terms` (`both`, `categories`, `tags`, `off`), `blog_sidebar_counts`, and `blog_sidebar_mobile` (`below` the content or in the `offcanvas` panel on small screens).
- The lists show only terms with published posts and leave out the default category while it is the only one. With nothing left to show, no post filter and no widgets, the blog view gets no sidebar, so a fresh site needs categories or tags with published posts first.
- An own `sidebar.php` of the child theme keeps the filters [`creationell_wp_theme_blog_sidebar_force`](reference/hooks-templates.md#creationell_wp_theme_blog_sidebar_force) and [`creationell_wp_theme_sidebar_aria_label`](reference/hooks-templates.md#creationell_wp_theme_sidebar_aria_label) and the action [`creationell_wp_theme_before_sidebar_widgets`](reference/hooks-templates.md#creationell_wp_theme_before_sidebar_widgets), or the sidebar loses its blog parts.

## Meta line and reading time

- The meta line below the title shows date, reading time, author and comments: `blog_meta_date`, `blog_meta_reading_time`, `blog_meta_author`, `blog_meta_comments` (`1` or `0`).
- The reading time counts `blog_reading_wpm` words per minute (100 to 400, default 200): `wp creationell-theme settings set blog_reading_wpm 230 --user=<login>`. A post behind a password shows no reading time.
- Trap: the filter [`creationell_wp_theme_entry_meta_html`](reference/hooks-templates.md#creationell_wp_theme_entry_meta_html) works in two directions. A callback that returns `"\n"` prints no meta line at all; one that returns `''` gives the template back its old meta line. Keep a non-empty value of an earlier callback unless you mean to replace it.
- An own `single.php` keeps its own meta line inside `if ( ! creationell_wp_theme_entry_meta( $context ) )`, as the original does; otherwise both meta lines print. The other required calls are listed in [child-theme.md](child-theme.md#overrides).

## Labels and back link

- Headings and the back link are translatable texts of the settings page Texts, per language: `blog_label_categories`, `blog_label_tags`, `blog_back_link_text` (`{title}` stands for the title of the blog page) and `blog_share_title`. Empty shows the default text.
- `blog_back_link` switches the link back to the blog page above a single post; `blog_category_badge` the category badge above the title; `blog_entry_terms` the terms below a post.

## Share links

- `blog_share`: `auto` (the plugin Shariff while it is active, otherwise the own links of the theme), `shariff`, `links` or `off`. `blog_share_networks` lists the own links, for example `linkedin,email,copy`.
- The own links load nothing from a third party before a click. A draft or a preview shows no share section.
- Posts with a password get no own links; the filter [`creationell_wp_theme_share_protected`](reference/hooks-modules.md#creationell_wp_theme_share_protected) returns `true` to share them anyway. [`creationell_wp_theme_shariff_atts`](reference/hooks-modules.md#creationell_wp_theme_shariff_atts) changes the attributes for Shariff.
- Trap: an override of `template-parts/blog/share.php` keeps the attributes `data-creationell-share-*` of the original (`data-creationell-share-copy`, `data-creationell-share-native`, `data-creationell-share-url`, `data-creationell-share-status` and the others). The script of the module finds the buttons by them; without them "Copy link" and the share dialog stay hidden.
- Trap: with Shariff the theme follows its rules for the word `hideshariff` and for posts with a password, but checks the word in the raw content of the post. Do not promise that the theme hides or shows the buttons exactly as Shariff would in every case; check the post on the site.

## 404 page

- After the widget area the 404 page shows a search form (`blog_404_search`, only while the widget area holds no search), the menu of the location "404 page links" and the latest posts (`blog_404_posts`, 0 to 6).
- An own `404.php` keeps `do_action( 'creationell_wp_theme_404_after_content', '404' )` after the widget area ([`creationell_wp_theme_404_after_content`](reference/hooks-templates.md#creationell_wp_theme_404_after_content)).

## Post filter

- The module `post-filter` shows the filter bar of the plugin JPKCom Post Filter in the blog sidebar instead of the term lists. It needs the plugin (1.7.2 or later) and the module `blog`; without them it stays off with the reason `missing_plugin:<path>` or `missing_module:<slug>` ([child-theme.md](child-theme.md#module-states)). With an older version of the plugin it shows as active, but the sidebar keeps the term lists.
- Switch it on with `wp creationell-theme module enable post-filter --user=<login>`, or give it the default `active` in the child theme, so it follows the plugin once the blog is on.
- The template part `template-parts/post-filter/filter.php` prints the markup of the plugin; override it at the same path. More in the [README of the module](../modules/post-filter/README.md).

## Check

- `wp creationell-theme doctor`: the section `blog` shows the state, the blog address, the author pages, the share mode and the post filter, and names every single template, `sidebar.php` and `404.php` of the child theme with the calls of the module it must keep (without reading the file, so check the calls yourself), and warns about authors whose login is their display name or nicename.
- `wp creationell-theme module list`: `blog` and `post-filter` have the intended state.
