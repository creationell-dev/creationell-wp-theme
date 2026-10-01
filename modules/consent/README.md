# Module consent

Cookie banner and preferences dialog based on
[CookieConsent](https://github.com/orestbida/cookieconsent) 3.1.0, styled with the
Bootstrap variables of the theme. **Not legally reviewed**: the module is a
technical tool; texts, categories and cookie lists are the responsibility of the
site.

| | |
|---|---|
| States | `active`, `off` (default `off`) |
| Needs | nothing; without Blockstudio only the block is missing |
| Replaces | the plugin bs Cookie Settings (`bs-cookie-settings/main.php`) |
| Settings | texts: `consent_banner_title`, `consent_banner_text`, `consent_dialog_text`, `consent_imprint_page`; design: `consent_revision`, `consent_cookie_days` |
| Block | `creationell-theme/consent-link` |
| Outposts | none |

Switch it on on the module page of the theme settings, with
`wp creationell-theme module enable consent`, with the constant in
`wp-config.php` or with the filter `creationell_wp_theme_modules` of a child
theme (see `modules/README.md`); the golden path "Enable the cookie banner"
(`docs/golden-paths/enable-cookie-banner.md` of the repository) walks through it. While the plugin bs Cookie Settings is active the module
stays `off`. Switching it off asks for a confirmation: banner, dialog and
withdrawal link disappear at once, and scripts marked for consent stay blocked.

## What the module does

While the module is `active`:

- CookieConsent becomes the consent provider of the theme core (provider ID
  `cookieconsent`), unless another plugin set a provider first. Scripts marked
  with `wp_script_add_data( $handle, 'creationell-wp-theme-consent', '<category>' )`
  run only after the visitor allowed their category.
- Every front-end page (not the admin, not embeds) loads the library
  (handle `creationell-wp-theme-cookieconsent`) and the module script and
  stylesheet (handle `creationell-wp-theme-consent-banner`). The configuration is
  printed inline as `window.creationellWpThemeConsentConfig`.
- Visitors opt in: a bar at the bottom offers "Accept all", "Reject optional"
  and "Settings" with the same weight. The dialog lists the categories of the
  core (`necessary` always on, the others off until chosen).
- The choice is stored in the cookie `creationell_consent` for all languages of
  the site (`SameSite=Lax`, 182 days by default). A withdrawal deletes the
  listed cookies of the category and reloads the page. A higher revision asks
  every visitor again.
- The footer shows a "Cookie settings" link on the action
  `creationell_wp_theme_footer_meta`; the block `creationell-theme/consent-link`
  places the same button anywhere. Both stay hidden until CookieConsent runs.
- The banner links the privacy policy page of the site (Settings > Privacy) and
  the imprint page of the setting `consent_imprint_page`, where published.

## Filters

- `creationell_wp_theme_consent_config` (array): the configuration of
  CookieConsent. Protection rules restore opt-in, the root element, script
  management, cookie deletion, the cookie name, buttons of equal weight, the
  "Reject optional" button, `necessary` locked on and all other categories off,
  and remove categories the core does not know; a notice names the restored keys.
- `creationell_wp_theme_consent_category_details` (array, default empty): title,
  description and cookies per category. Cookies of a category other than
  `necessary` are deleted on withdrawal; `is_regex` marks a regular expression.
- `creationell_wp_theme_consent_footer_link` (bool, default `true`): `false`
  removes the footer link.
- The categories themselves come from the core filter
  `creationell_wp_theme_consent_categories`.

```php
add_filter(
	'creationell_wp_theme_consent_category_details',
	static function ( array $details ): array {
		$details['analytics'] = array(
			'description' => 'Matomo counts visits without sharing data.',
			'cookies'     => array(
				array( 'name' => '^_pk_', 'is_regex' => true, 'description' => 'Matomo', 'duration' => '13 months' ),
			),
		);
		return $details;
	}
);
```

## Limits

- No consent log, no placeholders for embeds, no Google Consent Mode, no
  consent per service, no cookie scanner.
- PHP never knows the choice of the visitor:
  `creationell_wp_theme_consent_allowed()` allows only `necessary`.
- The library stays unchanged; `assets/js/consent.js` carries named corrections
  for keyboard and screen reader use of CookieConsent 3.1.0.
- Search engine bots see no banner (`hideFromBots`); a browser driven by test
  software reports itself as a bot, so end-to-end tests switch this off with a
  test plugin.
- The block `creationell-theme/consent-link` needs Blockstudio; without it the
  footer link and links with `data-cc="show-preferencesModal"` still open the
  dialog.
- Screen reader, zoom and keyboard checks by hand are listed under "Cookie
  banner" in `docs/testing/manual-a11y-checklist.md` of the repository.

## Tests

- Unit: `composer test -- --group=consent-banner`.
- WordPress: `composer test:integration -- --profile=consent` (wp-env, module
  on by constant, `de_DE_formal`).
- Browser: seed the development site with
  `wp eval-file tests/e2e/theme/seed-consent.php`, then
  `npm run test:e2e -- tests/e2e/theme/consent-banner.spec.ts`; remove the seed
  with `wp eval-file tests/e2e/theme/seed-consent.php remove`.
