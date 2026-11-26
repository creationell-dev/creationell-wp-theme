# Import the texts of a file language as another language

## Goal

Put the translatable texts of one language of a settings file into another language of the site, without editing the file: for example English texts that a translator turned into Persian.

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)).
- The same rights and file rules as in [import-export-settings.md](import-export-settings.md): `--user=<login>` with the capability `creationell_wp_theme_import_export`, the file outside the web root and deleted after use.
- The target language is active on the site, for example in WPML.

## Steps

1. Get the file. For an external translation, export only the texts of one language and the section `settings`, and let the translator change the values under `translatable.en`:

   ```bash
   wp creationell-theme settings export --sections=settings --lang=en --file=/tmp/texts-en.json --user=<login>
   ```

2. Preview the import with the mapping `--map-lang=<source:target>`, several pairs comma-separated. `--lang` then filters by target language:

   ```bash
   wp creationell-theme settings import /tmp/texts-en.json --map-lang=en:fa --dry-run --user=<login>
   ```

3. Read the preview. Mapped rows say "From language en of the file."; rows of the file's own texts in the target language are skipped as `language_replaced`, because the mapping wins.
4. Import after the preview, on a site that is not local only after the human agreed:

   ```bash
   wp creationell-theme settings import /tmp/texts-en.json --map-lang=en:fa --yes --user=<login>
   ```

   In WP-Admin: Theme settings > Import/Export > Import, choose the file, Show preview, choose the target in the fieldset "Languages of the file" (for example "Persian (fa)" for "Texts in English (en) of the file"), Update preview, check the rows, Import.
5. Delete the file: `rm /tmp/texts-en.json`.

More about the transfer: the section "Import and export of settings" of the [README of the theme](../../README.md#import-and-export-of-settings).

## Check

- `wp creationell-theme settings get footer_text --lang=fa` shows the imported text.
- `wp creationell-theme settings log --format=json --user=<login>` lists the newest entry first; for the import its field `lang_map` is `{"en":"fa"}`. The default table has no column `lang_map`.
- The report of the import has the warning `lang_mapped:en->fa`.

## Common mistakes

- A source language that is not in the file, a target that is not active on the site, or a target used twice: the import stops with `lang_map_invalid` and writes nothing.
- Expecting `wp creationell-theme settings backup restore` to map: a restore of a backup never maps.
- Editing the file by hand to rename the language: the mapping does that without touching the file.
