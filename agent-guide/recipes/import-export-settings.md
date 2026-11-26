# Export and import settings

## Goal

Move the theme settings and the module states of a site as one JSON file: to back up a site, to copy a design to another site or to bring back an earlier state.

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)). An import, a restore, a backup and an export all write: the export writes a file and the log.
- Run the commands with `--user=<login>` of a user with the capability `creationell_wp_theme_import_export`. Opening Theme settings > Import/Export needs the capability to view the advanced settings.
- An export file holds site data. Keep it outside the web root, for example in `/tmp`, never commit it and delete it after use.

## Steps

1. Export, in WP-Admin under Theme settings > Import/Export > Export (choose sections and languages, then Download file), or with WP-CLI:

   ```bash
   wp creationell-theme settings export --file=/tmp/settings.json --user=<login>
   ```

   Sensitive settings are left out and named in the header of the file.
2. Copy the file to the target site, again outside its web root.
3. Preview the import and read every row: Changes, Skipped or Rejected with the reason, and warnings such as another Bootstrap line or a newer theme.

   ```bash
   wp creationell-theme settings import /tmp/settings.json --dry-run --user=<login>
   ```

4. Import only after the preview, and on a site that is not local only after the human agreed. `--yes` also confirms module switches of the file:

   ```bash
   wp creationell-theme settings import /tmp/settings.json --yes --user=<login>
   ```

   In WP-Admin: choose the file, Show preview, tick "I understand the consequences of the module changes" when modules change, then Import.
5. Delete the file on both sites: `rm /tmp/settings.json`.
6. To undo an import, restore the backup the theme made before it: `wp creationell-theme settings backup list --user=<login>`, then `wp creationell-theme settings backup restore <id> --dry-run --user=<login>` and the same with `--yes`.

Texts of one file language for another language of the site: [import-texts-as-other-language.md](import-texts-as-other-language.md). All options: [reference/cli.md](../reference/cli.md#wp-creationell-theme-settings-import).

## Check

- `wp creationell-theme settings backup list --user=<login>` shows the backup made before the import.
- `wp creationell-theme settings log --user=<login>` shows the export and the import, without values.
- `wp creationell-theme settings get <key>` shows an imported value, and `wp creationell-theme module list` the module states of the file.

## Common mistakes

- A relative `--file` lands in the root of the WordPress site, where anyone can download it.
- Importing without the preview, or with `--yes` on a site that is not local without asking.
- Expecting rights to travel: the Bootstrap line, capabilities and unlocks are never imported. Values of languages that are not active are skipped.
- An exit code 1 means nothing was written: read the message, fix the cause and run the preview again.
- Settings that point to pages, media or terms find them by their slug on the target site, not by their ID.
