# Switch a module on or off

## Goal

Switch a module on or off on one site. For every site of a project, preset the module in the child theme instead ([preset-module.md](preset-module.md)).

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)). Every `--yes` there needs the agreement of the human first.
- Switching needs the capability `creationell_wp_theme_manage_modules`: administrators, editors only when the child theme unlocks `modules`. Writing commands need `--user=<login>` of such a user.
- A lock of the child theme or a constant in `wp-config.php` cannot be switched. The slugs: [reference/modules.md](../reference/modules.md).

## Steps

1. Read the state first: `wp creationell-theme module status post-lists --format=json` shows `configured`, `effective`, `origin` and `reason`.
2. Switch it on: `wp creationell-theme module enable post-lists --user=<login>`. `--state=hidden` switches a module that knows it to `hidden`.
3. Before you switch a module off, count the contents that use its blocks: `wp creationell-theme module scan post-lists --format=json`.
4. Switch it off with a preview first:

   ```bash
   wp creationell-theme module disable post-lists --state=off --dry-run --user=<login>
   wp creationell-theme module disable post-lists --state=off --yes --user=<login>
   ```

   To hide it instead: `wp creationell-theme module disable post-lists --user=<login>`. Without `--state`, `disable` goes to `hidden` when the module knows it, otherwise to `off`. Without `--yes`, `disable --state=off` of a module whose blocks live contents use prints the contents and stops with exit code 1; nothing changes. `--dry-run` shows the result and changes nothing.
5. In WP-Admin, the page Theme settings > Modules lists each module with its state and reason: choose the state and press Save. Switching off a module with blocks shows the contents that use them; confirm with the checkbox.

Nothing is deleted: the blocks show again when the module is on. The switch takes effect with the next page load.

## Check

- `wp creationell-theme module status post-lists` shows the intended `effective` state.
- `wp creationell-theme module list` shows every module with its state, origin and reason.
- `wp creationell-theme doctor` reports no new problem in its section `modules`.

## Common mistakes

- `--yes` on a site that is not local without asking: it confirms the consequences for live contents ([AGENTS.md](../../AGENTS.md#ask-before-you-change-a-site)).
- A module that stays `off` although switched on: `reason` names why, for example `missing_plugin:contact-form-7/wp-contact-form-7.php`, or `missing_blockstudio` for `header-footer`, `post-lists`, `post-slider` and `related-posts` without the plugin Blockstudio.
- A switch on one site where the project needs it everywhere: preset it in the child theme.
