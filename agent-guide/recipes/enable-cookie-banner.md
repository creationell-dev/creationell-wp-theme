# Enable the cookie banner

## Goal

Show the cookie banner and the preferences dialog of the module `consent`, list the services of the site with their cookies, and let marked scripts wait for the consent of the visitor.

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)).
- Not legally reviewed: the module is a technical tool. Texts, categories and the cookie list are the responsibility of the site; the human has them checked.
- The services the site uses and their cookies are known, for example from the documents of each provider.

## Steps

1. Deactivate the plugin bs Cookie Settings under Plugins if it is active; while it runs, the module stays `off` with the reason `legacy_plugin:bs-cookie-settings/main.php`.
2. Switch the module on: `wp creationell-theme module enable consent --user=<login>`, the row Cookie consent on Theme settings > Modules, or a default in the child theme with `$modules['consent'] = array( 'default' => 'active' );` ([preset-module.md](preset-module.md)).
3. Fill in the texts on the tab of the module on the settings pages Texts (banner title, banner text, dialog introduction, imprint page) and Design (revision, lifetime of the choice). Empty texts show the translated defaults. The privacy policy link comes from Settings > Privacy.
4. Open Theme settings > Cookies and services, add a preset or a service of your own, check purpose, cookies and durations against the documents of the provider, and save. `wp creationell-theme consent services presets --user=<login>` lists the presets; `wp creationell-theme consent services add-preset <preset> --user=<login>` adds one.
5. Texts and cookies per category can also come from code: the filter [`creationell_wp_theme_consent_category_details`](../reference/hooks-modules.md#creationell_wp_theme_consent_category_details) in the `functions.php` of the child theme. A cookie of the same name from the backend wins.
6. Mark the script of each service with its category in a project plugin, so it runs only after consent ([load-script-with-consent.md](load-script-with-consent.md)):

   ```php
   wp_enqueue_script( 'project-matomo', 'https://stats.example.org/matomo.js', array(), '5.0', array( 'strategy' => 'defer' ) );
   wp_script_add_data( 'project-matomo', 'creationell-wp-theme-consent', 'analytics' );
   ```

7. After the services or their cookies changed, raise the revision on the page Cookies and services ("Ask visitors again"): every visitor is asked again.

## Check

- `wp creationell-theme module status consent` shows `effective` `active`.
- `wp creationell-theme doctor --format=json` names the provider `cookieconsent` and the services per category in the section `consent`.
- `wp creationell-theme consent services list --user=<login>` lists the services with their cookies.
- Open the site in a private window: the banner appears, the footer link "Cookie settings" opens the dialog, and a marked script does not load before the consent.

## Common mistakes

- Promising legal compliance: the presets and the texts are not legally reviewed.
- A script of a third party printed directly in a template or without its category: it runs before the consent ([security.md](../security.md#scripts-of-third-parties)).
- A changed cookie list without a raised revision: earlier visitors are not asked again.
- More filters, the block for the cookie settings link and the withdrawal rules: [README of the module](../../modules/consent/README.md).
