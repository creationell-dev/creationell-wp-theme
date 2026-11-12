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
| Admin page | Theme settings > Cookies and services (`creationell-wp-theme-consent-services`) |
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

## Services and cookies in the backend

The page *Theme settings > Cookies and services*
(`creationell-wp-theme-consent-services`) keeps the services of the dialog and
their cookies, without ACF. It needs the capability
`creationell_wp_theme_manage_consent` (area `consent`): administrators, and
editors when the child theme sets `unlock['consent']` or the rights page opens
the area; restricted administrators only when the area is open. The page exists
while the module is installed; while the module is `off` it says that the
entries take effect once the module is on.

- **Overview:** one table per category with the services, their cookies and
  their origin (preset with its check date, `Import` or `Backend`), plus the
  cookies of the detail filter of the child theme, read only.
- **Presets:** 11 ready-made services (Matomo, Google Analytics 4, Google Tag
  Manager, YouTube, Vimeo, Google Maps, Meta Pixel, LinkedIn Insight Tag,
  Google reCAPTCHA, WordPress comments, WPML) with the information from the
  documents of the provider, the date it was checked and the sources. They are
  **not legally reviewed**: check every entry before going live. Each preset is
  added once; a newer preset of the theme shows "Preset newer" and a `doctor`
  warning. A preset moved to the category Necessary gets a warning: cookies of
  Necessary are never deleted.
- **A service:** name, category, provider (empty: the operator of the website),
  privacy policy (`http` or `https`), purpose and up to 40 cookies, each with
  name, kind of name, "Set on this website" (`first_party`), "Also delete on
  the parent domain" (`parent_domain`, only with `first_party`), purpose and
  duration. The service ID comes from the name (`^[a-z][a-z0-9-]*$`, at most 40
  characters, `-2`, `-3` … on a collision); `new` is reserved for the form of a
  new service. Cookie keys `c1`, `c2` … are never reused within a service.
- **Languages:** name, category, provider, address and the cookie names belong
  to the default language; purposes, durations and the category texts are kept
  per language. Outside the default language the page edits only the texts of
  that language, with "All languages" nothing. Each text falls back to the
  default language, then stays empty. The title and the description of a
  category come from the backend text of the language, then from the detail
  filter, then from the backend text of the default language, then from the
  theme.
- **Kinds of name:** `exact` (the name), `prefix` (at least 2 characters, shown
  as `_pk_id.…`) and `regex`, a safe subset that PHP and JavaScript read the
  same way: every top-level alternative starts with `^` and two literal
  characters; allowed are letters, digits, `_`, `-`, `\.`, `\d`, `.`, classes
  `[…]` (not starting with `]`, `:`, `.` or `=`, no `[` inside), one level of
  groups `( )`, `|`, `$` and the quantifiers `*`, `+` and `?` after a
  character, an escape or a class. Per top-level alternative: no quantifier
  after a group, at most one `*` or `+`, at most two `?` and at most one group
  with `|`. Never `(?`, `{`, `}` or `/`; at most 200 characters; the pattern
  must compile in PHP.
- **Deny list:** outside Necessary no cookie may hit `creationell_consent`,
  `cc_cookie`, `wordpress_*`, `wp-settings*`, `comment_author*`,
  `wp-postpass*`, `PHPSESSID`, `woocommerce_*`, `wp_woocommerce_session_*`,
  `mtm_consent*`, `mtm_cookie_consent` or `wp-wpml_current_language`; prefixes
  and regular expressions that could reach one are refused as well.
- **Withdrawal:** the cookies of a category other than Necessary are deleted
  when the visitor withdraws, but only those the site sets itself
  (`first_party`); with `parent_domain` also on the parent domain of the host
  (not for an IP address, `localhost` or a public suffix such as `co.uk`).
  Cookies of other domains are listed and not deleted.
- **Revision:** after a change the page offers "Ask visitors again (raise
  revision)"; it raises `consent_revision` by one and needs
  `creationell_wp_theme_manage_design`. A lock of the child theme or a constant
  and the limit 9999 stop it.

The data lives in the option `creationell_wp_theme_consent_services` (`v`,
`services` without language, `texts.<lang>` with `categories` and `services`).
`Consent_Services_Store::save()` is the only write path: it checks the
capability, validates everything and writes all or nothing; unknown fields of
the stored option survive. WP-CLI: `wp creationell-theme consent services`
with `list`, `presets`, `add-preset <id>` and `delete <id>` (`--yes` skips the
question); every subcommand needs `--user=<login>` with
`creationell_wp_theme_manage_consent`, `list` and `presets` know `--format`,
`list` also `--lang`. `wp creationell-theme doctor` counts the services per
category under `consent.services`. Import and export carry the services in the
section `consent_services` (needs the same capability): structure rows when
the default language is imported, text rows per language. The language map
also offers languages that only this section has. The preview checks the
merged list once more, so it shows what the import would refuse, such as two
services with the same cookie; services only on the site stay, a restore
resets them. A restore also resets every text the backup lacks in a language
of the backup that is active on the site, each with its own row in the
preview; a rejected text keeps the text of the site. Texts of other languages
stay, except those of a service the restore removes: they go in every
language, without a row of their own.

## Filters

- `creationell_wp_theme_consent_config` (array): the configuration of
  CookieConsent. Protection rules restore opt-in, the root element, script
  management, cookie deletion, the cookie name, buttons of equal weight, the
  "Reject optional" button, `necessary` locked on and all other categories off,
  and remove categories the core does not know; a notice names the restored keys.
- `creationell_wp_theme_consent_category_details` (array, default empty): title,
  description and cookies per category. Cookies of a category other than
  `necessary` are deleted on withdrawal; `is_regex` marks a regular expression.
  A cookie may also set `service` (shown in the column "Service"),
  `first_party` (`false`: set by another domain, listed but not deleted) and
  `domain` (deleted on that domain when the site host lies in it; otherwise the
  domain is dropped and `doctor` warns). The backend wins on the same cookie
  name: a backend cookie pushes out the cookie of the same display name here,
  in every category.
- `creationell_wp_theme_consent_services` (list): the services of the backend,
  resolved for the language of the request (`id`, `category`, `name`,
  `provider`, `privacy_url`, `purpose`, `cookies` with `name`, `match`,
  `first_party`, `parent_domain`, `purpose`, `duration`). The result passes
  the same checks as the backend; a broken service falls away with a notice.
- `creationell_wp_theme_consent_service_presets` (array by preset ID): adds,
  replaces or removes presets; a broken entry is dropped and reported.
- Action `creationell_wp_theme_consent_services_saved` (stored data,
  `Write_Context`): after every write of the store.
- `merged_details()` of `Texts` returns the details with the backend merged in.
  Backend cookies come first in their category and carry `name` (display
  name), `is_regex`, `description`, `duration`, `service`, `first_party`,
  `match`, `service_id`, `parent_domain` and `pattern` (exact: the name,
  otherwise the regular expression); a cookie with the key `match` is a backend
  cookie. A category with backend services also has `services` (`id`, `name`,
  `provider`, `purpose`, `privacy_url`, `third_party`).
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
  consent per service (the visitor chooses categories; the services only
  describe them), no cookie scanner.
- Backend store: at most 60 services, 0 to 40 cookies per service (a service
  without cookies, such as Google Tag Manager, is valid), 128 KB of JSON in all
  (`doctor` warns above 64 KB). Service ID at most 40 characters; name and
  provider at most 120, privacy policy 500, cookie name 200, purpose of a
  service 500, purpose of a cookie 300, duration 60, category title 120,
  category description 2000 (links, bold, italic and line breaks).
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

- Unit: `composer test -- --group=consent-banner`; the backend:
  `composer test -- --group=consent-services`.
- WordPress: `composer test:integration -- --profile=consent` (wp-env, module
  on by constant, `de_DE_formal`).
- Browser: seed the development site with
  `wp eval-file tests/e2e/theme/seed-consent.php`, then
  `npm run test:e2e -- tests/e2e/theme/consent-banner.spec.ts`; remove the seed
  with `wp eval-file tests/e2e/theme/seed-consent.php remove`.
- Browser, backend: after `seed-consent.php` also
  `wp eval-file tests/e2e/theme/seed-consent-services.php`, then
  `npm run test:e2e -- tests/e2e/theme/consent-services.spec.ts`; `remove`
  restores the store.
