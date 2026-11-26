# Preset or lock a module from the child theme

## Goal

Decide in the child theme which modules every site of the project starts with (a default) and which ones a site cannot change (a lock).

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)). A change of the `functions.php` of the child theme changes every site that runs it.
- Every module starts `off`. The slugs and the states of each module: [reference/modules.md](../reference/modules.md). To switch a module on one site only, use [switch-module.md](switch-module.md) instead.

## Steps

1. Open the `functions.php` of the child theme and find the callback of the filter [`creationell_wp_theme_modules`](../reference/hooks-core.md#creationell_wp_theme_modules).
2. Set a default with `'default'`: the site starts with this state, and an administrator may switch it in the backend.
3. Set a lock with `'locked'`: the module keeps this state, and the backend cannot switch it.

   ```php
   add_filter(
   	'creationell_wp_theme_modules',
   	static function ( $modules ) {
   		$modules = is_array( $modules ) ? $modules : array();
   		// Contact Form 7 styling on; the site may switch it off.
   		$modules['contact-form-7'] = array( 'default' => 'active' );
   		// This project has no shop.
   		$modules['woocommerce'] = array( 'locked' => 'off' );
   		return $modules;
   	}
   );
   ```

4. Use the states `active` and `off`, and `hidden` for the modules that know it (post lists, post slider, related posts): existing content still renders, and the blocks leave the inserter.
5. Keep the precedence in mind, from low to high: the default `off`, the child default, the value saved in the backend, the child lock, the constant `CREATIONELL_WP_THEME_MODULE_<SLUG>` in `wp-config.php`. The same order as in [child-theme.md](../child-theme.md#precedence).

## Check

- `wp creationell-theme module list` shows every module with its intended state. The column `origin` names the winning layer: `child`, `locked`, `backend`, `constant` or `default`.
- `wp creationell-theme module status contact-form-7` shows `configured`, `effective`, `origin` and `reason` of one module.
- `wp creationell-theme child check` gives `Success: 0 violations.`

## Common mistakes

- A default that seems to have no effect: a value saved in the backend wins over a child default. Use a lock if the site must not change it.
- A module that stays `off` although preset: its plugin or a required module is missing; `reason` says which, for example `missing_plugin:contact-form-7/wp-contact-form-7.php` ([child-theme.md](../child-theme.md#module-states)).
- `hidden` for a module that does not know it: the module turns off with the reason `invalid_state`.
- A callback that does not return the array it got: every other default is lost.
