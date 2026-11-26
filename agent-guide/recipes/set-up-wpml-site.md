# Set up a site with WPML

## Goal

Run the site in several languages with WPML: the theme then shows its own language switcher in the header and keeps its text settings per language.

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)).
- The plugins WPML Multilingual CMS, WPML String Translation and ACF Multilingual need a license and are not in the theme package: ask the human for the plugin packages and the versions the project uses. Do not download them yourself.
- The theme works without WPML; what it expects of WPML: [integrations.md](../integrations.md#wpml).

## Steps

1. Upload the three plugin packages under Plugins > Add New Plugin > Upload Plugin in WP-Admin and activate them.
2. Run the WPML setup wizard: the default language (for example German, `de_DE`), the further languages, language directories (`/en/`) and translation by yourself. The theme is tested with language directories only.
3. Open any admin page once: WPML and ACF Multilingual finish their installation there. Then open Appearance > Themes once: WPML reads the `wpml-config.xml` of the theme only there, also after a theme switch with WP-CLI.
4. Under WPML > Languages, turn off the language switcher in the footer: the theme brings its own switcher. Sort the languages under the language switcher options; the switcher of the theme follows this order.
5. Under ACF > Tools, run "Translate ACF Local JSON and PHP-Registered Fields" with "Sync once now", so the translatable settings (footer text, address) get their translation preference. Repeat it after theme updates with new settings.
6. Keep the texts of Theme settings > Texts per language: switch the language in the admin bar and save. Texts of the theme and of the child theme are translated in WPML String Translation ([translate-texts.md](translate-texts.md)).

## Check

- `wp plugin is-active sitepress-multilingual-cms` ends with exit code 0.
- `wp creationell-theme wpml status --format=json` shows `active` as `true`.
- `wp creationell-theme wpml status --strict` ends with exit code 0: no finding of the level warning. Both together prove the setup; without WPML, `--strict` alone also ends with 0.
- `wp creationell-theme doctor` shows the same state in its section `wpml`.

## Common mistakes

- Another URL mode than language directories: the status shows the finding `wpml.url_mode_untested`.
- Skipping the visit of Appearance > Themes: the status shows `wpml.config_not_loaded`, and template parts are not shown as translated.
- Editing `wpml-config.xml` or copying it into the child theme: it is generated and a theme update replaces it.
- Leaving the footer switcher of WPML on: the site shows two switchers.
