# Module header-footer

Header and footer as template parts of the Site Editor. Editors change the
parts `header` and `footer` of the active theme in the Site Editor; the texts
of the footer stay in the theme settings. The module is `off` until a site
or its child theme switches it on (the child theme template
`creationell-wp-child-theme` starts it `active`), and it needs the plugin
Blockstudio (7.6 or newer). Without
Blockstudio, or while the module is `off`, the PHP header and footer of the
theme show and the Site Editor lists no header or footer part.

## Blocks

| Block | Content |
|---|---|
| `creationell-theme/navbar` | Main menu with logo, search and the menu that opens from the side; one per page |
| `creationell-theme/logo` | Logo files of the theme, the site name without them |
| `creationell-theme/search` | Search form of the theme |
| `creationell-theme/footer-menu` | Menu of the location "Footer menu" |
| `creationell-theme/footer-text` | Footer text of the theme settings, or the copyright line; one per page |
| `creationell-theme/contact` | Address, phone and email of the theme settings, with a heading (level 2 to 6) |
| `creationell-theme/social-links` | Links to the social media profiles of the theme settings |
| `creationell-theme/language-switcher` | Language switcher of the theme (WPML), as a list or a dropdown |

The blocks print the partials of the theme (`template-parts/header/navbar.php`,
`template-parts/footer/footer-menu.php`, `template-parts/footer/footer-info.php`,
`template-parts/header-footer/contact.php`,
`template-parts/header-footer/social-links.php`), so a child theme that
overrides a partial changes the PHP header or footer and the block alike.

The text blocks hold no text of their own: footer text, contact data and
social links come from the page "Texts" of the theme settings, in the language
of the request. A text block without values shows nothing on the site; in the
editor it shows the note "Set in Theme settings → Texts", a link for users who
may change the texts. The language switcher shows nothing without WPML or with
one language.

In the header part the language switcher is a block of its own. The navbar
block therefore leaves out the switcher of the header actions, which the PHP
header keeps; a page shows one switcher either way.

## Outposts

- `parts/header.html`, `parts/footer.html`: one pattern reference each.
- `patterns/header-footer/`: the patterns `creationell-wp-theme/header-default`
  and `creationell-wp-theme/footer-default`, used only by the parts.
- `template-parts/header-footer/`: the partials `contact.php` and
  `social-links.php` of the text blocks.

## Rights

Editors (capability `edit_pages`) edit the parts `header` and `footer` of the
active theme; authors get no access. The theme grants no role anything:
WordPress asks for `edit_theme_options` in the Site Editor, and the module
answers yes only inside the part editor, the REST routes of these two parts
and, with the menu capability, the menu screen. Administrators keep what
WordPress gives them.

- The admin menu shows **Header & Footer** with the entries Header, Footer
  and, with the menu capability, Menus. The other entries below Appearance
  (Themes, Customize, Widgets, Editor) stay hidden from editors.
- The Site Editor leads editors from every other screen to the list of the
  two parts. They cannot create parts, edit templates, open styles or fonts,
  lock blocks, or insert the blocks Custom HTML, Classic, Shortcode,
  Navigation, Template Part, Site Logo, Site Title and Site Tagline; the filter
  `creationell_wp_theme_header_footer_denied_blocks` changes this list.
- For everyone, the styles screen of the Site Editor leads to the design page
  of the theme settings (or to the part list), the font library is closed and
  styles saved in the Site Editor have no effect: colors and fonts come from
  Theme settings > Design only.
- A child theme takes the parts back to the administrators with
  `$caps['revoke']['template_parts'] = true` in the filter
  `creationell_wp_theme_capabilities`; the menus of header and footer close
  with them.

## Menus

The menus of the navbar and the footer are the classic menus of the locations
`main-menu` and `footer-menu`; an administrator creates them and assigns them
to their locations. By default only administrators edit menus. A child theme
lets editors edit these two menus with `$caps['unlock']['menus'] = true`:

- Editors edit the menus assigned to `main-menu` and `footer-menu` and, with
  WPML, their translations, on the menu screen (Header & Footer > Menus).
- They cannot create or delete menus, edit other menus or change the menu
  locations; without an assigned menu they see a notice.

`wp creationell-theme doctor --format=json` reports `menus_scope: limited`
while the module keeps editors on these two menus.

## Languages (WPML)

With WPML, header and footer are translated with WPML only: translate the
parts in WPML (Translation Management), not by switching the language in the
Site Editor. The theme adds no language fallback of its own; `wpml-config.xml`
of the theme shows a part without translation in the default language, and
the block texts of the parts (contact heading, name of the social media list)
are translatable there. Texts of the text blocks come from Theme settings >
Texts in the language of the page.

A part stored in the database needs a translation linked by WPML, under the
same slug, in every active language. While one is missing:

- the dashboard shows a notice to users who may edit the parts;
- `wp creationell-theme doctor --format=json` lists the languages under
  `header_footer.parts.<header|footer>.missing_languages` and prints a
  warning.

Parts that exist only as theme files need no translation: their texts come
from the theme settings and the translations of the theme. In a language
other than the default language, the Site Editor warns while a part has no
linked translation there, also a part that is still the theme file. For a
stored part the Site Editor edits the part of the default language there,
and saving overwrites it; no unlinked copy appears (checked with WordPress
7.1 and WPML 5.0).

## Going back

- **One part:** the Site Editor keeps the revisions of a part and offers to
  reset a changed part to the theme file; the part file then shows again.
  `wp creationell-theme doctor --format=json` shows under
  `header_footer.parts.<header|footer>.source` whether a part comes from the
  theme file (`file`) or from the database (`db`).
- **The whole module:** switch it `off` on the module page or with
  `wp creationell-theme module disable header-footer --state=off --yes --user=<admin>`.
  The module asks for a confirmation first. The PHP header and footer of the
  theme show again with the next request, and the Site Editor lists no header
  or footer part. Parts changed in the Site Editor stay in the database but no
  page shows them; switching the module on again brings them back.
- **Without Blockstudio** the module stays `off` in the same way.
