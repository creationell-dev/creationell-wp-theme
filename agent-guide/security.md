# Security

Rules for overrides, the `functions.php` of the child theme and project plugins: escape output, check rights, load scripts of third parties only through consent, keep secrets out of files.

## Escape late

- Escape every value at the moment you print it, also values from the theme settings, from the database and from a filter of the theme: `esc_html()` for text, `esc_attr()` for attributes, `esc_url()` for addresses, `wp_kses_post()` for HTML that may hold the tags of a post.
- Use the escaping forms of the translation functions: `esc_html__()`, `esc_html_e()`, `esc_attr__()`, `esc_attr_e()` ([i18n.md](i18n.md)).
- An override keeps every escaping call of the original. Do not escape a value twice, and do not unescape a value that a function of the theme returns as escaped HTML.
- Never print a value from the request (`$_GET`, `$_POST`, `$_COOKIE`, `$_SERVER`) without sanitizing and escaping it.

## Input and nonces in a project plugin

- The child theme handles no input of its own: it registers no settings and no options pages (rule `no-new-settings` in [child-theme.md](child-theme.md#rules)). A form, an admin page, an AJAX or REST endpoint goes into a project plugin.
- In the project plugin, sanitize every input with the function for its type (`sanitize_text_field()`, `sanitize_key()`, `absint()`, `esc_url_raw()`) after `wp_unslash()`.
- Protect every action that writes with a nonce and a check of the right: `wp_nonce_field()` in the form, `check_admin_referer()` or `wp_verify_nonce()` before the work, and `current_user_can()` for the capability. A REST route gets a `permission_callback`.

## Rights

- The theme gives its areas their own capabilities, for example `creationell_wp_theme_manage_design` and `creationell_wp_theme_manage_consent`. It answers them on `map_meta_cap` from the capabilities of WordPress and stores nothing in a role.
- Ask for a right with `current_user_can()`; never check a role name.
- Open an area to editors or take one back only through the filter `creationell_wp_theme_capabilities` of the child theme, or let an administrator do it on Theme settings > Rights ([child-theme.md](child-theme.md#rights-of-editors)).
- Do not change roles: `add_role()`, `add_cap()` and `remove_cap()` are forbidden in the child theme (rule `no-roles`), because a role change stays in the database after a theme switch.

## Scripts of third parties

Load analytics, embeds, maps, pixels and every other script that sets cookies or sends data to a third party only through the consent interface of the theme. With the module `consent` the visitor decides per category; without a consent provider marked scripts never run.

- Enqueue the script and mark its handle with its category: `wp_script_add_data( $handle, 'creationell-wp-theme-consent', 'analytics' )`. By default the categories are `necessary`, `functional`, `analytics` and `marketing`.
- Print inline code with `creationell_wp_theme_consent_inline_script()`: it returns a blocked script tag that runs after consent to its category.
- In the browser, wait for the event `creationell-wp-theme:consent-change` and read `event.detail.accepted`, or ask `window.creationellWpTheme.consent.allowed( 'analytics' )`.
- Do not decide in PHP: `creationell_wp_theme_consent_allowed()` allows only `necessary`, because PHP never knows the choice of the visitor.
- Never put a script tag of a third party directly into an override of `header.php` or `footer.php`.
- Enter every service with its cookies on Theme settings > Cookies and services ([integrations.md](integrations.md#consent)).
- Step by step: [recipes/load-script-with-consent.md](recipes/load-script-with-consent.md) and [recipes/enable-cookie-banner.md](recipes/enable-cookie-banner.md).

A project plugin loads its script like this:

```php
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_script( 'project-analytics', plugins_url( 'assets/js/analytics.js', __FILE__ ), array(), '1.0.0', true );
		wp_script_add_data( 'project-analytics', 'creationell-wp-theme-consent', 'analytics' );
	}
);
```

## Secrets

- Never write keys, passwords or tokens into the child theme, a project plugin, a README or a note of the project. Keep them in the settings of the plugin that needs them or in constants in `wp-config.php`.
- Do not copy a secret from a site into a file, a log or an answer.

## Every PHP file

Start every PHP file of the child theme and of a project plugin, after its file comment, with:

```php
defined( 'ABSPATH' ) || exit;
```

The line stops the file when someone calls it directly instead of through WordPress.
