# Integrations

The theme works with a few plugins. For each one this file says what the theme expects, which module belongs to it, which hooks and constants count and where the documentation lives. Module states and reasons: [child-theme.md](child-theme.md#module-states); every module in one table: [reference/modules.md](reference/modules.md).

Check which plugins run with `wp plugin list` or `wp plugin is-active <plugin>`; both only read. Installing, activating or updating a plugin writes: on a site that is not local, ask the human first.

## CreaBootstrapBlocks

- Page content comes from the Bootstrap blocks of the plugin CreaBootstrapBlocks. No module belongs to it; the theme core handles it.
- The theme brings Bootstrap. While the theme is active it defines `CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_CSS`, `CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_JS` and `CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_ICONS` as `false`, so the plugin loads no second Bootstrap. Do not set them to `true` in `wp-config.php`: the constant stays, and administrators see a notice.
- The Bootstrap Icons font loads while the plugin is active, or when the filter [`creationell_wp_theme_load_icon_font`](reference/hooks-core.md#creationell_wp_theme_load_icon_font) returns `true`.
- With the module `post-lists` active, the block post grid of the plugin leaves the inserter; [`creationell_wp_theme_hide_cbb_post_grid`](reference/hooks-modules.md#creationell_wp_theme_hide_cbb_post_grid) returns `false` to keep it.
- Write the block markup as the plugin describes it. Its folder `wp-content/plugins/crea-bootstrap-blocks/llm/` describes every block, its attributes and its markup; read it on the site before you write or change page content.
- `wp creationell-theme doctor` shows the plugin and the constants in its section `cbb`.

## Blockstudio

- The block modules need the plugin Blockstudio, version 7.6 or newer: `header-footer`, `post-lists`, `post-slider` and `related-posts`; `related-posts` also needs the module `post-lists`. Without Blockstudio they stay off with the reason `missing_blockstudio`. The theme does not check the version: with an older Blockstudio they show as active but may not work.
- The module `consent` runs without Blockstudio; only its block for the cookie settings link is missing then.
- The module `header-footer` moves header and footer into the Site Editor; the parts `parts/header.html` and `parts/footer.html` of the child theme replace those of the parent theme. More in the [README of the module](../modules/header-footer/README.md).
- Blocks of your own belong in a project plugin, not in the child theme (rule `no-blocks`).

## WPML

- The theme reads its languages from WPML; no module belongs to it. Without WPML the site has one language.
- `wpml-config.xml` tells WPML which data of the theme to translate. It is generated: do not edit it, and do not copy it into the child theme.
- Texts of the theme in a language without a shipped translation are translated in WPML String Translation, domain `creationell-wp-theme`; texts of the child theme use the domain `creationell-wp-child-theme` ([i18n.md](i18n.md)).
- The texts of Theme settings > Texts are kept per language.
- `creationell_wp_theme_language_switcher()` returns the language switcher of the theme as escaped HTML; the filters [`creationell_wp_theme_language_switcher_args`](reference/hooks-core.md#creationell_wp_theme_language_switcher_args) and [`creationell_wp_theme_language_switcher_languages`](reference/hooks-core.md#creationell_wp_theme_language_switcher_languages) change it.
- `wp creationell-theme wpml status` shows versions, languages, the URL mode, the state of `wpml-config.xml` and findings. It only reads.
- Step by step: [recipes/set-up-wpml-site.md](recipes/set-up-wpml-site.md).

## Contact Form 7

- The module `contact-form-7` styles the forms of the plugin Contact Form 7 with Bootstrap and moves the focus to the first invalid field. It needs Contact Form 7 6.0 or later, `contact-form-7/wp-contact-form-7.php`. Without the plugin the module stays off with the reason `missing_plugin:<path>`; with a version before 6.0 it shows as active but changes nothing on the forms.
- The module brings no form templates and no settings. Accessible form templates come from the plugin "Contact Form 7: Accessible Defaults".
- Filters: [`creationell_wp_theme_cf7_classes`](reference/hooks-modules.md#creationell_wp_theme_cf7_classes) for the classes of the fields, [`creationell_wp_theme_cf7_autop`](reference/hooks-modules.md#creationell_wp_theme_cf7_autop) for paragraphs in the form markup, [`creationell_wp_theme_cf7_inline_choices`](reference/hooks-modules.md#creationell_wp_theme_cf7_inline_choices) for checkboxes and radios side by side.
- More in the [README of the module](../modules/contact-form-7/README.md). Step by step: [recipes/create-cf7-form.md](recipes/create-cf7-form.md).

## Consent

- The module `consent` shows the cookie banner and the preferences dialog. It needs no plugin. It is not legally reviewed: texts, categories and cookie lists are the responsibility of the site.
- Services and their cookies are kept on Theme settings > Cookies and services; `wp creationell-theme consent services list --user=<login>` shows them.
- A script of the project that needs consent waits for its category: mark its handle with `wp_script_add_data( $handle, 'creationell-wp-theme-consent', 'analytics' )`, or print inline code with `creationell_wp_theme_consent_inline_script()`. Rules and examples: [security.md](security.md#scripts-of-third-parties).
- In the browser, `window.creationellWpTheme.consent.allowed( 'analytics' )` tells whether a category is allowed, and the event `creationell-wp-theme:consent-change` on `document` reports every change with `detail.accepted`.
- In PHP, `creationell_wp_theme_consent_allowed()` allows only `necessary`: PHP never knows the choice of the visitor.
- Filters: [`creationell_wp_theme_consent_config`](reference/hooks-modules.md#creationell_wp_theme_consent_config), [`creationell_wp_theme_consent_category_details`](reference/hooks-modules.md#creationell_wp_theme_consent_category_details), [`creationell_wp_theme_consent_services`](reference/hooks-modules.md#creationell_wp_theme_consent_services), [`creationell_wp_theme_consent_categories`](reference/hooks-core.md#creationell_wp_theme_consent_categories).
- More in the [README of the module](../modules/consent/README.md).

## Shariff Wrapper

- With the module `blog` on and the setting `blog_share` on `auto` or `shariff`, the share section shows the buttons of the plugin Shariff Wrapper while it is active ([blog.md](blog.md#share-links)).
- [`creationell_wp_theme_shariff_atts`](reference/hooks-modules.md#creationell_wp_theme_shariff_atts) changes the attributes the theme passes to Shariff.
- The theme follows the Shariff setting for posts with a password and the word `hideshariff` in the content, but checks the word in the raw content of the post. Do not promise that the buttons show or hide exactly as Shariff would; check the post on the site.

## JPKCom Post Filter

- The module `post-filter` shows the filter bar of the plugin JPKCom Post Filter in the blog sidebar. It needs JPKCom Post Filter 1.7.2 or newer and the module `blog`; without them it stays off with the reason `missing_plugin:<path>` or `missing_module:<slug>`. With a version before 1.7.2 it shows as active, but the sidebar keeps the term lists and shows no filter bar.
- Switch it on with `wp creationell-theme module enable post-filter --user=<login>`, or give it the default `active` in the child theme.
- The template part `template-parts/post-filter/filter.php` prints the markup of the plugin; override it at the same path.
- More in the [README of the module](../modules/post-filter/README.md) and in [blog.md](blog.md#post-filter).
