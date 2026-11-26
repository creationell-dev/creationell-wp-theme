# Set up the blog

## Goal

Give the site a blog: a posts page, the module `blog` with its sidebar, meta line, back link, share links and 404 page, and the settings the project wants.

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)). Every `settings set`, `module enable` and `wp option update` below writes.
- Writing commands need `--user=<login>` of a user with the capability of the module or the setting.
- Tasks and traps of the blog: [blog.md](../blog.md). Every setting, template part and hook of the module: [README of the module](../../modules/blog/README.md).

## Steps

1. Choose the posts page. `wp option get show_on_front`, `wp option get page_on_front` and `wp option get page_for_posts` show the current state; `wp post list --post_type=page --fields=ID,post_title` lists the pages. Use the page the task names. If there is none, create it under Pages > Add New Page in WP-Admin or with `wp post create --post_type=page --post_title=<title> --post_status=publish --porcelain --user=<login>`, which prints the ID; without a name in the task, call it "Blog".
2. Choose the front page. A posts page needs a static front page (`page_on_front`). If `page_on_front` already names a page, keep it. If the front page shows the latest posts and the task names the posts page, the task includes the switch to a static front page: take the page meant as the home page (an existing page titled Home or Start), or create a page "Home" as in step 1 if there is none, and name the front page in your report. Ask the human first only on a site that is not local, or when the task does not say which page shows the posts.
3. Set both under Settings > Reading ("Your homepage displays": a static page, "Homepage": the front page, "Posts page": the blog page), or with WP-CLI:

   ```bash
   wp option update show_on_front page --user=<login>
   wp option update page_on_front <home-id> --user=<login>
   wp option update page_for_posts <blog-id> --user=<login>
   ```

4. Switch the module on, in one of two ways:
   - for every site of the project, a default in the `functions.php` of the child theme: `$modules['blog'] = array( 'default' => 'active' );` in the filter `creationell_wp_theme_modules` ([preset-module.md](preset-module.md));
   - on this site only: `wp creationell-theme module enable blog --user=<login>` or the switch on Theme settings > Modules.
5. Set what the project wants, on the settings pages Design and Texts or with WP-CLI, for example:

   ```bash
   wp creationell-theme settings set blog_sidebar_terms categories --user=<login>
   wp creationell-theme settings set blog_reading_wpm 230 --user=<login>
   wp creationell-theme settings set blog_share links --user=<login>
   wp creationell-theme settings set blog_author_pages 1 --user=<login>
   ```

   Author pages are off by default; switch them on only if the project wants public author pages.
6. Optional: the filter bar of the plugin JPKCom Post Filter in the blog sidebar. It needs the plugin (1.7.2 or later) and the module `blog`: `wp creationell-theme module enable post-filter --user=<login>` ([blog.md](../blog.md#post-filter)).
7. An own `single.php`, `sidebar.php` or `404.php` in the child theme keeps the calls of the module ([child-theme.md](../child-theme.md#overrides)).

## Check

- `wp creationell-theme module status blog --format=json` shows `effective` `active`.
- `wp option get show_on_front` prints `page`, `wp option get page_on_front` prints the ID of the front page and `wp option get page_for_posts` the ID of the posts page.
- `wp creationell-theme doctor --format=json` has no `warnings` in the section `blog` other than the author, override, SEO and Shariff warnings that Common mistakes describes, and its `blog_url` is the address of the posts page.
- `wp creationell-theme module list` shows `post-filter` with the intended state, if you switched it on.
- Open the blog page and a single post on the site: the meta line and the back link show. The sidebar shows once categories or tags have published posts, the post filter shows its bar or the sidebar has widgets; a lone default category does not count. Without any of these the blog view has no sidebar.

## Common mistakes

- No posts page: the doctor warns in its section `blog`, and the module has no blog page to show.
- A posts page without a static front page: `show_on_front` stays `posts`, and `page_for_posts` has no effect.
- Author warnings: once the module is on, the doctor warns in its section `blog` about each author with published posts whose display name or nicename is the login, the default for the first user of a fresh site. They concern user profiles, not the blog setup. Report them to the human; change a profile only when the task covers it, under Users > All Users > Edit in WP-Admin (Users > Profile for your own user) or with `wp user update <id> --display_name=<name> --user=<login>`. A new nicename (`--user_nicename=<slug>`) changes the address of the author page.
- Override reminders: for an own `single.php`, template of `single-templates/`, `sidebar.php` or `404.php` in the child theme, the doctor names in its section `blog` the calls the copy must keep. It sees only that the file exists, so the reminder is expected when the copy keeps the calls listed in [child-theme.md](../child-theme.md#overrides).
- SEO warnings: while the author pages are off, the default, Rank Math or Yoast SEO with their default settings make the doctor warn in its section `blog` that they keep the author archives or list the authors in their sitemap. Report them to the human; change the setting of the SEO plugin only when the task covers it.
- Shariff warnings: while the plugin Shariff is active, the doctor names in its section `blog` where Shariff inserts its own buttons, share counts and Mastodon. Report them to the human; change the settings of Shariff only when the task covers it.
- A child theme with its own `index.php` but no `home.php`: the blog page of the module stays off ([blog.md](../blog.md#blog-page-and-author-pages)).
- A `single-post.php` in the child theme: `child check` rejects it; posts use `single.php`.
- A setting key or value that does not exist: `settings set` ends with exit code 1. The keys and values: [reference/settings.md](../reference/settings.md).
- Promising that Shariff buttons show or hide exactly as the plugin would ([blog.md](../blog.md#share-links)).
