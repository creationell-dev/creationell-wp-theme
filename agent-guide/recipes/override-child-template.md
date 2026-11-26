# Override a template in the child theme

## Goal

Change the markup of a page through a copy of a template of the parent theme in the child theme. WordPress looks in the child theme first and uses the copy; the parent theme stays untouched.

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)). Copying a file into the child theme changes the site.
- Run the commands below in the root folder of the WordPress site, the folder that holds `wp-config.php`.
- Templates are the PHP files in the theme root (except `functions.php`), in `template-parts/`, in `page-templates/` and in `single-templates/`. Every template with its hooks and contexts: [reference/templates.md](../reference/templates.md).

## Steps

1. Look for a filter or an action first ([reference/hooks.md](../reference/hooks.md)). A filter in the `functions.php` of the child theme keeps the template of the parent theme, so later fixes still arrive. Copy a template only when the markup itself must change.
2. Find the path the parent theme uses. Posts use `single.php` (or a template of `single-templates/`); the parent theme has no `single-post.php`, so the child theme must not have one either.
3. Copy the template of the installed parent theme to the same path in the child theme:

   ```bash
   mkdir -p wp-content/themes/creationell-wp-child-theme/template-parts/header
   cp wp-content/themes/creationell-wp-theme/template-parts/header/main-menu.php \
      wp-content/themes/creationell-wp-child-theme/template-parts/header/main-menu.php
   ```

4. Change the copy. Keep every escaping call (`esc_html()`, `esc_attr()`, `esc_url()`), the labels of forms and buttons, the landmarks and headings, and the text domain `creationell-wp-theme` for texts copied unchanged from the parent theme. A new or changed text uses `creationell-wp-child-theme` ([i18n.md](../i18n.md)).
5. With the module `blog` on, an own `single.php`, `sidebar.php` or `404.php` keeps the calls of the blog parts at the same places: the list is in [child-theme.md](../child-theme.md#overrides).
6. After each update of the parent theme, compare the copy with the new template and carry the changes over. A raised `Template Version` in the header of a template marks a change:

   ```bash
   diff wp-content/themes/creationell-wp-theme/template-parts/header/main-menu.php \
        wp-content/themes/creationell-wp-child-theme/template-parts/header/main-menu.php
   ```

## Check

- `wp creationell-theme child check` gives `Success: 0 violations.` A path that is no template of the parent theme shows the rule `allowed-paths`.
- `wp creationell-theme doctor` reports no new problem. With the module `blog` on, its section `blog` has a reminder for every `single.php`, template of `single-templates/`, `sidebar.php` and `404.php` of the child theme: the doctor sees only that the file exists, not its calls. Such a reminder is expected when the copy keeps the calls listed in [child-theme.md](../child-theme.md#overrides).
- Open the page on the site and check the changed markup with the quick check of [accessibility.md](../accessibility.md#quick-check-before-finishing).

## Common mistakes

- A `single-post.php` in the child theme: the parent theme has none, so `child check` rejects it. Override `single.php`.
- An own `index.php` without `home.php`, or an own `archive.php` without `author.php`: the blog page or the author pages of the module stay off ([architecture.md](../architecture.md#template-switches)).
- Copying a template from another version of the parent theme: copy the one of the installed version.
- Redefining a helper of `inc/` instead of overriding a template or using its filter ([architecture.md](../architecture.md#pluggable-helpers)).
- Editing the template in the parent theme: a theme update overwrites it.
