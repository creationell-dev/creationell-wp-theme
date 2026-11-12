# creationell Theme

**Version:** 0.5.0

**Requires at least:** 7.1

**Tested up to:** 7.1

**Requires PHP:** 8.3

**Update URI:** https://creationell-dev.github.io/creationell-wp-theme/

Classic WordPress theme of creationell with Bootstrap 5, based on
[Bootscore](https://bootscore.me/) (MIT). PHP templates render the pages; the
block editor and the Bootstrap blocks of creationell work in the content area.
Projects keep their changes in a child theme, so the theme itself can be
updated.

## Requirements

- WordPress 7.1 or later
- PHP 8.3 or later
- Recommended: the plugin CreaBootstrapBlocks for the Bootstrap blocks in the
  content area (see [CreaBootstrapBlocks](#creabootstrapblocks))

## Installation

1. Upload the theme folder `creationell-wp-theme` to `wp-content/themes/`.
2. Upload a child theme next to it. The child theme template
   `creationell-wp-child-theme` ships separately. Copy it and
   keep the folder `creationell-wp-child-theme` in every project; write the
   project name into its `Theme Name` only.
3. Activate the child theme under Appearance > Themes. WordPress then uses this
   theme as the parent theme.

The folder name `creationell-wp-theme` must not change: it is the slug that
child themes and updates refer to. Activating the theme stores no options;
every function starts with its default.

## Child theme

A child theme holds everything that belongs to one project: logos, project
styles, template overrides and the defaults and locks of the theme functions.
Its `style.css` keeps `Template: creationell-wp-theme`, `Update URI: false` and
the header `Bootstrap Lines`, which names the Bootstrap major versions its
project styles support. The README of the child theme template lists what a
child theme may and may not change; `wp creationell-theme child check` checks a
child theme against these rules.

A child theme sets defaults and locks through two filters in its
`functions.php`:

- `creationell_wp_theme_modules`: the state of each module, as `default` (the
  site may change it) or `locked` (the site cannot).
- `creationell_wp_theme_settings`: defaults and locks of the theme settings in
  the same form.

```php
add_filter(
	'creationell_wp_theme_modules',
	static function ( mixed $modules ): mixed {
		$modules = is_array( $modules ) ? $modules : array();
		$modules['woocommerce'] = array( 'locked' => 'off' );
		return $modules;
	}
);
```

The logos in `assets/img/logo/` and `screenshot.png` are placeholders. A
child theme replaces the logos with files of the same name in its
`assets/img/logo/` and shows its own `screenshot.png`; `assets/README.md`
names the filters for another logo URL and size.

To override a template, copy it from this theme to the same path in the child
theme, for example `template-parts/header/main-menu.php`, and change the copy.
Prefer a filter or an action of the theme where one exists: a copied template
does not receive later fixes of the original.

## Modules

Optional functions of the theme are modules: blocks, integrations with plugins,
the cookie banner. Every module is `off` until a site or its child theme
switches it on. `wp creationell-theme module list` shows the state of every
module and where it comes from. The order of precedence, lowest first: the
default `off`, the default of the child theme, the state switched in the
backend, the lock of the child theme and the constant
`CREATIONELL_WP_THEME_MODULE_<SLUG>` in `wp-config.php`, for example:

```php
define( 'CREATIONELL_WP_THEME_MODULE_CONTACT_FORM_7', 'active' );
```

`modules/README.md` lists the modules, their states and the folder convention.

## Where to set what

One place per setting; editors change texts and, with the module
`header-footer`, header and footer, administrators the rest.

| What | Where | Who |
|---|---|---|
| Header and footer (layout, blocks, order) | Module `header-footer` active: **Header & Footer** in the admin menu, which opens the parts in the Site Editor; module `off`: the PHP header and footer of the theme, changed only in a child theme | editors and administrators; a child theme can keep them with the administrators |
| Footer text, contact data, social media links | Theme settings > Texts, per language with WPML | editors and administrators; a child theme can keep them with the administrators (`revoke['basic']`) |
| Menus of header and footer | Appearance > Menus (with the module: Header & Footer > Menus) | administrators; editors only with the module active and the menus unlocked by the child theme, and then only these two menus |
| Colors and fonts | Theme settings > Design; never in the Site Editor (its styles have no effect) | administrators; editors when the child theme unlocks the design |
| Modules | Theme settings > Modules | administrators; editors when the child theme unlocks the modules |
| Consent services and cookies | Theme settings > Cookies and services | administrators; editors when the child theme unlocks the consent area (`unlock['consent']`) |

## Updates

The theme updates itself from the update manifest named by its `Update URI`
header, not from wordpress.org. WordPress shows a new version on the updates
screen like any other theme update; the theme checks the package (https,
checksum, requirements, Bootstrap line) before WordPress installs it.
Automatic updates of the theme are locked: an administrator installs every
update by hand, after a backup and a look at the changelog.

`wp creationell-theme update status` shows the installed and the offered
version and why no update is offered. A test site may predefine
`CREATIONELL_WP_THEME_MANIFEST_URL` in `wp-config.php` with another https URL;
administrators then see a notice that the site uses a test manifest.

The theme uses the Bootstrap line 5 (Bootstrap 5). The constant
`CREATIONELL_WP_THEME_BOOTSTRAP_LINE` holds a line for a site; a line that this
theme version does not include keeps line 5, and `wp creationell-theme doctor`
names the reason.

## Translations

The texts of the theme are English, with the text domain
`creationell-wp-theme`. The folder `languages/` holds the template
`creationell-wp-theme.pot` and German translations in two forms of address:
`de_DE` (informal) and `de_DE_formal` (formal). WordPress loads them when the
site language is one of these. Further languages are translated per project
with WPML; `wpml-config.xml` tells WPML which theme data to translate.

Texts of the theme in further languages go through WPML String Translation
(domain `creationell-wp-theme`); texts the consent banner shows come from PHP
as well. From 0.3.0 on, each changelog entry lists under "New or changed
texts" what was added or changed, so these languages can follow.

## Accessibility

The templates aim at WCAG 2.2 level AA: labelled forms, named landmarks and
dialogs, skip links, menu items with a submenu as a link plus a toggle button,
visible focus rings that the sticky header does not hide, colors with a
contrast of at least 4.5:1, and inline icons that assistive technology skips
or names. The design settings refuse color pairs below the required contrast.
In the dark color mode (`data-bs-theme="dark"` on the page or on a part of it)
the focus ring uses the primary color, lightened where needed to reach 3:1 on
the dark page and on its tertiary background; a child theme can set
`$focus-ring-color-dark` in `_project-variables.scss`.

## CreaBootstrapBlocks

The theme loads Bootstrap for the Bootstrap blocks of the plugin
CreaBootstrapBlocks. While the theme is active it defines the constants
`CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_CSS`, `CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_JS` and
`CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_ICONS` as `false`, so the plugin loads no second
Bootstrap. A constant that `wp-config.php` sets to `true` stays, and
administrators see a notice; so does a Bootstrap version of the plugin that
differs from the one of the theme. The theme stylesheet and the Bootstrap bundle
load before the assets of the plugin. The Bootstrap Icons font loads with the
plugin active or when the filter `creationell_wp_theme_load_icon_font` returns
`true`.

### Leaving the theme

After switching to another theme, switch the Bootstrap stylesheet, script and
icons on again in the settings of CreaBootstrapBlocks, unless the new theme
loads Bootstrap itself.

## Import and export of settings

The page *Theme settings > Import/Export* exports the theme settings and the
module states as a JSON file, per language on WPML sites, and imports such a
file after a preview that lists every value with its result. Before every
import and restore the theme backs up the current settings; it keeps the last
five backups, and each one can be restored the same way. A log keeps who
exported, imported, restored or backed up which sections for 90 days, without
values.

- Opening the page needs the capability to view the advanced settings; export,
  import, restore and "Back up now" need `creationell_wp_theme_import_export`.
  Other users see the backups only.
- Sensitive values are never exported. The Bootstrap line, capabilities and
  unlocks are never imported. Every value goes through the same checks as the
  settings pages (type, locks, contrast); rejected values stay as they are and
  the result lists them.
- Switching a module that published contents use needs an explicit
  confirmation in the preview.
- Files have at most 1 MB and end in `.json`; nothing is stored in the media
  library.

### Languages of the file

The texts of a file language can be imported as another language of the site,
for example English texts that were translated into Persian. In the preview,
the fieldset "Languages of the file" has one select per file language, named
with the language name and code (for example "Texts in English (en) of the
file"): choose an active language of the site ("Persian (fa)"), "as in the
file" or "do not import", then "Update preview". With WP-CLI: `wp creationell-theme settings import <file>
--map-lang=en:fa` (comma-separated pairs `source:target`). `--lang` filters by
target language. The mapping wins over the file's own texts of the target
language (those rows are skipped as `language_replaced`). A language that is not
in the file, not active, or used as target twice stops the import with
`lang_map_invalid`; nothing is written. A restore of a backup never maps. The
file format stays v1. Steps: `docs/golden-paths/import-texts-as-other-language.md`.

## Command line

The theme adds the WP-CLI command `wp creationell-theme`. The commands below
only read; each one has `--format=json` for scripts.

| Command | Shows |
|---|---|
| `wp creationell-theme doctor` | Health of the theme: version and requirements, update manifest, Bootstrap line, CreaBootstrapBlocks, child theme, consent, WPML; problems as warnings |
| `wp creationell-theme module list` | Every module with its state and the reason |
| `wp creationell-theme module status <slug>` | One module in detail |
| `wp creationell-theme child check [<path>]` | Findings of a child theme against the child theme rules |
| `wp creationell-theme update status` | Installed and offered version, manifest state |
| `wp creationell-theme wpml status` | State of the WPML integration |

`wp creationell-theme consent services add-preset|delete` write the services
of the cookie banner (`list` and `presets` read them); all four need
`--user=<login>` of a person with `creationell_wp_theme_manage_consent`. Details
in `modules/consent/README.md`.

## For developers

- `src/README.md`: the theme core in `src/` (loading, naming, public
  functions such as `creationell_wp_theme_module_state()` and
  `creationell_wp_theme_setting()`).
- `inc/README.md`: the template helpers in `inc/` and their filters.
- `assets/README.md`: stylesheets, scripts, images and vendored libraries.
- `modules/README.md`: the module catalog and the folder convention.

Every function, class, method and hook of the theme has a PHPDoc block in
English; `phpdoc.xml` builds the API documentation with phpDocumentor 3.

## Files

- `style.css`: theme header; `Version` is the only version source.
- `functions.php`: loads `src/loader.php`, which loads the theme core in `src/`
  and the template helpers in `inc/`.
- `theme.json`: editor settings; the color palette refers to the Bootstrap
  custom properties.
- PHP templates in the theme root, `template-parts/`, `page-templates/` and
  `single-templates/`; block patterns in `patterns/`.
- `assets/css/bootstrap-5/theme.min.css`: compiled stylesheet (Bootstrap 5 and
  the theme styles from `assets/scss/`); `root-vars.min.css`: its root custom
  properties for the editor screens; `build.json`: Bootstrap line, fingerprint
  and component versions of the build.
- `assets/js/theme.js`: theme script; `assets/vendor/`: vendored Bootstrap,
  Bootstrap Icons and further libraries with their licenses.
- `assets/img/logo/`: placeholder logos for the light and the dark color
  scheme; `screenshot.png`: placeholder preview for Appearance > Themes.
- `lib/`: PHP libraries of the stylesheet compiler, in their own namespace.
- `languages/`: translation template and German translations.
- `modules/`: the theme modules.

## License

GPL-2.0-or-later, see `LICENSE`. Licenses of the included third-party code:
`THIRD-PARTY-NOTICES`.

## Changelog

### 0.5.0

- Cookies and services: the new page *Theme settings > Cookies and services*
  keeps the services of the cookie banner and their cookies in the backend,
  without ACF. It needs the capability `creationell_wp_theme_manage_consent`
  (area `consent`: administrators; editors when the child theme unlocks it) and
  exists while the module `consent` is installed. 11 presets (Matomo, Google
  Analytics 4, Google Tag Manager, YouTube, Vimeo, Google Maps, Meta Pixel,
  LinkedIn Insight Tag, Google reCAPTCHA, WordPress comments, WPML) fill in a
  service from the documents of the provider with their check date; they are
  "not legally reviewed". Purposes, durations and category texts are kept per
  language and fall back to the default language. Cookie names are exact names,
  prefixes or regular expressions of a safe subset; cookies of WordPress, the
  consent banner, the shop and WPML are refused outside the category Necessary.
  A button raises the consent revision. Details in the module README.
- Cookie banner: each category lists its services with provider, purpose and
  privacy policy; the cookie table gets the column "Service". A withdrawal
  deletes the cookies the site sets itself, on request also on the parent
  domain; cookies of other domains are listed, not deleted. The new filters
  `creationell_wp_theme_consent_services` and
  `creationell_wp_theme_consent_service_presets` and the action
  `creationell_wp_theme_consent_services_saved` join the module; cookies of the
  filter `creationell_wp_theme_consent_category_details` may set `service`,
  `first_party` and `domain`. Without backend entries the banner output is the
  same as in 0.4.0.
- Import and export: the new section `consent_services` carries the services
  and their texts per language. The language map (admin select and
  `--map-lang`) also offers languages that only this section has. The preview
  checks the services against the merged list, so it shows what the import
  would refuse. A restore of `consent_services` replaces the services and the
  texts of the backup's languages that are active on the site; the preview
  lists every text it deletes in them, and rejected values stay as they are.
  Texts of other languages stay, except those of a service the restore
  removes: they go in every language, without a row of their own.
- WP-CLI: `wp creationell-theme consent services list|presets|add-preset|delete`;
  `wp creationell-theme doctor` counts the services per category under the key
  `services` of the section `consent` and warns about a list above 64 KB,
  newer presets and child theme cookies a backend cookie replaces.

#### Notes for the update

- A child theme that sets every key of `unlock` to `true` now also opens
  `unlock['consent']`: editors may then change the cookies and services. Set
  `unlock['consent']` to `false` (or use the rights page) to keep the area
  with the administrators.
- Theme 0.4.0 imports files of 0.5.0 but skips the section `consent_services`
  as `section_unknown`; on 0.4.0 `--map-lang` only knows the languages of the
  section `settings`.
- With WPML String Translation active, a site whose WPML language `de` uses the
  locale `de_DE` cannot show the formal address (`de_DE_formal`) through the
  theme: String Translation switches the locale. Set the locale of the WPML
  language `de` to `de_DE_formal` for the formal address. This is a setting of
  the site, not a change of the theme.

#### New or changed texts

- Cookie banner: `Service`, `(opens in a new tab)`,
  `of %s (opens in a new tab)`, `Also sets cookies on domains of the provider;
  the website cannot delete them.`
- Rights page: `Cookies and services`,
  `Consent services and their cookies in the cookie banner.` and the changed
  `Administrators marked here cannot use the areas of the agency (design,
  modules, import and export, menus, cookies and services) unless they are open
  to editors; they never see this page and never switch the Bootstrap line.
  They keep the other rights of an administrator in WordPress; the restriction
  holds firmly only when the child theme locks it.` (adds "cookies and
  services")
- Page *Cookies and services*, labels: `Services`, `Provider`,
  `Cookies`, `Actions`, `Delete`, `Backend`, `Preset`, `Sources:`,
  `Category`, `Purpose`, `Cookie name`, `Kind of name`, `Set on this website`,
  `Also delete on the parent domain`, `Remove this cookie`, `Exact name`,
  `Starts with`, `Regular expression`, `None`, `Operator of this website`,
  `Category texts`, `Choose a category`, `More cookies`, `New cookie %d`,
  `Cookie %1$d: %2$s`, `Cookie %1$d, %2$s`, `%1$s: %2$s`, `Error: %s`,
  `Texts for: %1$s (%2$s)`, `Standard language: %s`,
  `Standard language: no text.`, `%s (already applied)`,
  `Preset %1$s, as of %2$s`, `Preset newer: as of %s.`,
  `Child theme – replaced by %s`, `Child theme – read only`
- Page *Cookies and services*, buttons, links and headings: `Add service`,
  `Edit service: %s`, `Delete service`, `Save service`, `Save texts`,
  `Save category texts`, `Add a preset`, `Add preset`, `About the presets`,
  `Back to the overview`, `Go to the theme modules`,
  `Ask visitors again (raise revision)`
- Page *Cookies and services*, notes:
  `The cookie consent module is off. The entries take effect as soon as it is
  switched on.`
- `The services listed here appear in the cookie settings dialog. Their cookies,
  except those of the category Necessary, are deleted when a visitor withdraws
  the consent.`
- `Visitors who already made a choice are not asked about the changed services
  until the revision is raised.`
- `Information from the provider's documents, as of %s. Not legally reviewed;
  check before going live.`
- `This service comes from a preset in category %s. Cookies in Necessary are
  never deleted.`
- `An empty field uses the text of the child theme or of the theme. The title
  has at most 120 characters, the description at most 2000; the description
  may contain links, bold and italic text and line breaks.`
- `Empty: the operator of this website.`
- `Address of the privacy policy of the provider, starting with https://.`
- `A group with an empty name, purpose and duration is ignored.`
- `At most %d cookies per service.`
- `No services in this category.`
- `Change these fields in the default language.`
- `Create new services in the default language.`
- `Choose an active language to edit the texts.`
- `Choose a language in the admin bar to edit the category texts.`
- `Choose a language in the admin bar to edit the service.`
- `The entered values are no longer available; the stored values are shown.`
- `Delete the service %1$s with %2$d cookie? Its texts in all languages are
  deleted too.` (plural: `Delete the service %1$s with %2$d cookies? Its texts
  in all languages are deleted too.`)
- Page *Cookies and services*, results: `Nothing changed.`, `Deleted: %s.`,
  `Preset added: %s.`, `The category texts are saved.`,
  `The revision is raised: every visitor is asked again.`,
  `Error: this preset is already in the list of services.`,
  `Error: the request is not valid; nothing was changed.`,
  `Error: this service does not exist.`,
  `Error: check the texts of this category.`,
  `Error: the category texts were not saved; check the texts of %s.`,
  `Error: the category texts were not saved; check the marked fields.`,
  `Error: the revision has changed in the meantime; it was not raised again.`,
  `Error: the revision cannot be raised; it is at most 9999.`,
  `Error: the child theme or a constant locks the revision.`,
  `The form arrived incomplete; check the server limit max_input_vars.`,
  `The service was changed in the meantime. Please reload.`,
  `The service was not saved. Check the marked fields.`,
  `You are not allowed to raise the consent revision.`
- Saving, presets and checks: `You are not allowed to change the cookies and
  services.`, `The cookies and services were not saved; correct the marked
  fields.`, `The list of services is too large; it may hold at most %d KB.`,
  `This preset does not exist.`,
  `This preset is already in the list of services.`, `Fill in this field.`,
  `This value is already in use.`, `This consent category does not exist.`,
  `This value has an invalid format.`, `Use at most %d entries.`,
  `Enter an address that starts with http:// or https://.`,
  `Enter the date as YYYY-MM-DD.`, `Choose exact, prefix or regex.`,
  `This cookie belongs to WordPress, the consent banner, the shop or WPML and
  must not be deleted.`
- `Start each alternative with ^ and two letters; use only letters, digits, _,
  -, \., \d, ., [ ], one level of ( ) without a quantifier after it, |, $, and
  per alternative at most one group with |, two ? and one * or +.`
- Import and export: `Category texts: %s`, `on this site`, `parent domain`,
  `Import the default language as well: this service is not on this site.`,
  `The service itself is not imported: %s`
- The texts of the presets (purposes, durations and notes) in
  `src/consent/data/service-presets.php`.

### 0.4.0

- Import of settings with a language map: the texts of a file language can be
  imported as another language of the site (admin select per file language,
  WP-CLI `--map-lang=en:fa`). Mapping, `language_replaced`, the warning
  `lang_mapped:<s>-><t>` (only for pairs whose source differs from the target
  and whose target is imported) and the log field `lang_map` (a JSON object in
  `log --format=json`, also when empty) are described under
  "Languages of the file" above. Format v1 and existing calls are unchanged.
- The preview and the result of an import collapse rows that stay unchanged
  into a "N settings stay unchanged" disclosure; modules are counted apart.
- The updater keeps its manifest cache, the marker of a failed fetch and its
  lock in site transients instead of transients. On multisite the manifest is
  fetched once for the network instead of once per site. A cache entry written
  by 0.3.0 is no longer read and expires within a day.
- Build: setup-php 2.40.0 in all workflows.

#### New or changed texts

- `Languages of the file`
- `Texts in %1$s (%2$s) of the file`
- `Texts in %s of the file` (file language that is not active on the site)
- `%1$s (%2$s)` (context `language name and code`)
- `%1$s (%2$s, as in the file)`
- `%1$s (%2$s, default language)`
- `%s (as in the file)` (language without a name from WPML)
- `do not import`
- `Update preview`
- `Replaced by another language of the file`
- `Replaced by the texts of language %s from the file.`
- `From language %s of the file.`
- `The texts of language %1$s of the file are imported as %2$s.`
- `The language assignment does not fit: a language is not in the file, is not
  active on this site, or gets the texts of two languages of the file.`
- `%d setting stays unchanged` (plural: `%d settings stay unchanged`)
- `%d module stays unchanged` (plural: `%d modules stay unchanged`)
- `unchanged`
- `%s: nothing changes.`

### 0.3.0

- Fonts and languages: the design page warns when a font of the design settings
  does not cover a language of the site, with the scripts or the characters that
  are missing. The check uses the character lists of Unicode CLDR for 14
  languages, among them Arabic, Persian, Russian, Ukrainian, Chinese
  (simplified and traditional) and Japanese; the section `settings` of
  `wp creationell-theme doctor` lists the same gaps. The font catalog knows the
  script `cjk`. Font stacks whose first name would break the font faces and
  unicode ranges that browsers drop as a whole are reported.
- Pages with a search block no longer scroll sideways. WordPress loads a width
  for the search label after the theme stylesheet; the hidden label became as
  wide as the page (2216 px at a 1280 px window). Visually hidden texts now keep
  the Bootstrap rules with `!important` while they have no focus, so focus rules
  of child themes and plugins (`.screen-reader-text:focus`) still show them.
- The "read more" link of the post cards is at least 24 px high (WCAG 2.5.8).
  Under cards without an excerpt it takes a few pixels more height.
- Translations: the texts of the theme can be translated in WPML String
  Translation; see "Translations" above. Right to left was checked in Arabic and
  Persian with all blocks of CreaBootstrapBlocks: use CreaBootstrapBlocks 1.0.3
  or later, it mirrors the left and right spacing of its blocks.

#### New or changed texts

- `The font "%1$s" (%2$s) does not cover %3$s: the script %4$s is missing.`
  (plural: `… the scripts %4$s are missing.`)
- `The font "%1$s" (%2$s) does not cover %3$s: these characters are missing: %4$s`
- `(and %d more)`
- `Latin`, `Latin Extended`, `Vietnamese`, `Cyrillic`, `Arabic`,
  `CJK (Chinese and Japanese)`

### 0.2.2

- Fonts of the child theme load now. A font the child adds to the font
  catalog (filter `creationell_wp_theme_font_catalog`) with files in
  `assets/fonts/` gets its font-face rules, with `font-display: swap` and the
  unicode range of the entry. Before, the stylesheet asked for the font but
  WordPress printed no font-face rule, so browsers showed the system font.
  Nothing to do after the update.

### 0.2.1

- After a theme update the site keeps its own colours, fonts and texts. Before,
  the front end showed the defaults and the scheduled build switched to the
  package stylesheet until the stylesheet was rebuilt by hand. Sites that
  updated to 0.2.0 and still show the default colours need one
  `wp creationell-theme css build` or the button of the admin notice.

### 0.2.0

- Rights in the backend: the new page Theme settings > Rights lets an
  administrator choose per area whether editors may use it (design, modules,
  import and export, menus of header and footer; texts and header and footer
  the other way round) and which administrators are restricted. The values of
  the filter `creationell_wp_theme_capabilities` are defaults now; the new key
  `locked` keeps an entry as the child theme sets it. Import and export never
  carry rights.
- The theme settings menu starts with Texts, then Design, Modules,
  Import/Export and Rights; the empty entry of the main page is gone.
- Dark colour mode (`data-bs-theme="dark"`): outline buttons and field borders
  take colours derived from the primary colour that keep 4.5:1 and 3:1 on the
  dark surfaces.
- Cookie banner: the arrows of the preferences dialog and the locked switch of
  "necessary" reach the contrast for controls; the help text of the imprint page
  names where the privacy policy page comes from.
- Bootstrap carousel: the control icons sit on a dark disc and show the focus
  ring of the theme.
- Search block: the button keeps the Bootstrap size (it took the global button
  styles of WordPress before).
- Widget editor: blocks show the fonts and the look of the front end, also in
  the widgets of the Customizer.
- Categories and latest posts blocks: the list group classes go on single tags;
  category classes, names with parentheses and the class of the post title stay
  whole, and the title is the one stretched link of an item.
- The block editor loads no blocks and no patterns from the WordPress.org
  directories.
- WPML: `wp creationell-theme wpml status` reports when WPML has not loaded the
  configuration of the theme; the editor hint of header and footer tells stored
  and file parts apart.

### 0.1.1

- Right to left: the package ships `theme-rtl.min.css`, which WordPress loads
  for languages written right to left (Arabic, Hebrew, Farsi, ...). While such
  a language is active, the stylesheet of the design settings gets a
  right-to-left copy as well; the new fingerprint makes installed sites build
  their individual stylesheet once. After a right-to-left language is switched
  on, it gets the package stylesheet without the design colours until the
  stylesheet is rebuilt (saving the design settings or
  `wp creationell-theme css build`; `doctor` warns). An editor whose profile
  language is written right to left sees the package stylesheet without the
  design colours in the editor of a left-to-right site.
- Saving the design settings replaces the left-to-right and the right-to-left
  stylesheet as one change: when one of them cannot be written, neither is
  replaced.

### 0.1.0

- First development version: installable theme with the compiled Bootstrap 5
  stylesheet, the Bootstrap bundle and the theme script.
- CreaBootstrapBlocks runs on the Bootstrap of the theme; Bootstrap Icons font,
  child theme stylesheet and editor canvas styles.
- Accessibility: labelled forms, named landmarks and dialogs, menu items with
  submenu as link plus toggle button, visible focus rings, colours with at
  least 4.5:1, a real top button, focus kept clear of the sticky header;
  inline icons from Bootstrap Icons; patterns without external images.
- Hardening: core HTML filters for term and author descriptions stay, links
  in comments are no longer rewritten, no shortcodes in menu titles.
- Stylesheet rebuilt after the update of the CSS parser to 9.5.0: same rules,
  shorter output; the new fingerprint makes installed sites build their
  individual stylesheet once. Child theme SCSS: the parser now accepts
  `@container`, `@layer` and the modern colour syntax; an IE hack such as
  `width: 100px\9` is no longer rejected but compiles to a tab character.
