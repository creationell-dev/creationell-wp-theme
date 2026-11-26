# Load a script only with consent

## Goal

Load a script that needs the consent of the visitor (statistics, marketing, external media) so that it runs only after the visitor allowed its category.

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)).
- The script goes into a project plugin: the child theme holds no scripts of its own.
- A consent provider is active, normally the module `consent` ([enable-cookie-banner.md](enable-cookie-banner.md)). Without one, a marked script never runs.
- The categories are `necessary`, `functional`, `analytics` and `marketing`. A script marked `necessary` runs as usual.

## Steps

1. Register the script in the project plugin as usual and mark its handle with its category. The theme prints it blocked, as `type="text/plain"`, and the consent provider runs it once the visitor allows the category. The marking also covers the inline code of the handle.

   ```php
   add_action(
   	'wp_enqueue_scripts',
   	static function () {
   		wp_enqueue_script( 'project-statistics', 'https://stats.example.com/s.js', array(), '1.0.0', array( 'in_footer' => true ) );
   		wp_add_inline_script( 'project-statistics', 'projectStats.init();' );
   		wp_script_add_data( 'project-statistics', 'creationell-wp-theme-consent', 'analytics' );
   	}
   );
   ```

2. For inline code without a handle, print the script tag that `creationell_wp_theme_consent_inline_script()` returns, for example in a `wp_footer` callback of the project plugin. The function escapes the code itself; print its result unchanged:

   ```php
   echo creationell_wp_theme_consent_inline_script( 'analytics', 'projectStats.init();' );
   ```

3. Code in the browser that reacts to consent itself asks the theme, and listens to the event `creationell-wp-theme:consent-change` with `addEventListener`:

   ```js
   if ( window.creationellWpTheme.consent.allowed( 'analytics' ) ) {
   	// Consent given.
   }
   document.addEventListener( 'creationell-wp-theme:consent-change', ( event ) => {
   	// event.detail.accepted lists the allowed categories, for example [ 'necessary', 'analytics' ].
   } );
   ```

4. Enter the service with its cookies on Theme settings > Cookies and services, so the dialog lists it.

## Check

- `wp creationell-theme doctor --format=json` names the provider in the section `consent` (`null` without one), the categories and, without a provider, the marked scripts that never run.
- Open the site in a private window: the script does not load before the consent and loads after you allow its category.

## Common mistakes

- Deciding in PHP: `creationell_wp_theme_consent_allowed()` allows only `necessary`, because PHP never knows the choice of the visitor.
- `allowed()` in the browser is `false` for everything but `necessary` until a consent provider answers.
- A script tag of a third party written into an override of `header.php` or `footer.php`: it runs without consent ([security.md](../security.md#scripts-of-third-parties)).
- A wrong category name: the script waits for a category the dialog never offers.
