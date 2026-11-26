<!-- Generated from the source. Do not edit. -->

# WP-CLI commands

Every command of the theme below `wp creationell-theme`. **writes** marks a command that changes the site: settings, modules, files, the compiled stylesheet, backups or the log. On a site that is not local, ask the human before you run it; the rule is in [AGENTS.md](../../AGENTS.md). Commands that are not on the checked list of reading commands count as writing.

Global WP-CLI parameters such as `--user=<login>` work with every command; commands that check a capability need it.

| Command | Reads or writes |
|---|---|
| `wp creationell-theme child check` | reads |
| `wp creationell-theme consent services add-preset` | writes |
| `wp creationell-theme consent services delete` | writes |
| `wp creationell-theme consent services list` | reads |
| `wp creationell-theme consent services presets` | reads |
| `wp creationell-theme css build` | writes |
| `wp creationell-theme css status` | reads |
| `wp creationell-theme doctor` | reads |
| `wp creationell-theme module disable` | writes |
| `wp creationell-theme module enable` | writes |
| `wp creationell-theme module list` | reads |
| `wp creationell-theme module scan` | reads |
| `wp creationell-theme module status` | reads |
| `wp creationell-theme settings backup create` | writes |
| `wp creationell-theme settings backup list` | reads |
| `wp creationell-theme settings backup restore` | writes |
| `wp creationell-theme settings export` | writes |
| `wp creationell-theme settings get` | reads |
| `wp creationell-theme settings import` | writes |
| `wp creationell-theme settings list` | reads |
| `wp creationell-theme settings log` | reads |
| `wp creationell-theme settings rebuild` | writes |
| `wp creationell-theme settings reset` | writes |
| `wp creationell-theme settings set` | writes |
| `wp creationell-theme update status` | reads |
| `wp creationell-theme wpml status` | reads |

## wp creationell-theme child

Checks a child theme against the child theme rules; reads only.

Registering the command also adds the doctor section "child" with the declared Bootstrap lines of the active child theme.

### wp creationell-theme child check

**Reads only.**

Checks a child theme folder against the rules in agent-guide/child-theme.md of the theme.

Prints one row per finding and ends with exit code 1 when there is any.

```bash
wp creationell-theme child check [<path>] [--format=<format>]
```

Options:

- `[<path>]`: Folder of the child theme, absolute or relative to the working directory. Default: the active child theme.
- `[--format=<format>]`: Output format. Default: `table`. Values: `table`, `json`.

Examples:

```bash
wp creationell-theme child check
wp creationell-theme child check wp-content/themes/creationell-wp-child-theme --format=json
```

## wp creationell-theme consent services

Lists, adds from presets and deletes the services of the cookie dialog.

Every subcommand, "list" and "presets" included, needs `--user=<login>` of a user with the capability creationell_wp_theme_manage_consent; without it the command stops with exit code 1. Changes go through Consent_Services_Store (channel "cli"). Deleting asks for confirmation unless --yes is given.

### wp creationell-theme consent services add-preset

**Writes.** On a site that is not local, ask the human first.

Adds a service from a preset of the catalog.

```bash
wp creationell-theme consent services add-preset <preset> [--user=<login>]
```

Options:

- `<preset>`: ID of the preset, see "presets".
- `[--user=<login>]`: User with the capability creationell_wp_theme_manage_consent.

Examples:

```bash
wp creationell-theme consent services add-preset matomo --user=admin
```

### wp creationell-theme consent services delete

**Writes.** On a site that is not local, ask the human first.

Deletes a service with its texts; the category texts stay.

```bash
wp creationell-theme consent services delete <id> [--yes] [--user=<login>]
```

Options:

- `<id>`: ID of the service, see "list".
- `[--yes]`: Do not ask for confirmation.
- `[--user=<login>]`: User with the capability creationell_wp_theme_manage_consent.

`--yes` skips the confirmation; on a site that is not local only with the human's consent.

Examples:

```bash
wp creationell-theme consent services delete matomo --yes --user=admin
```

### wp creationell-theme consent services list

**Reads only.**

Lists the stored services.

```bash
wp creationell-theme consent services list [--lang=<code>] [--format=<format>] [--user=<login>]
```

Options:

- `[--lang=<code>]`: Language of the purpose texts; default: the default language.
- `[--format=<format>]`: Output format: table, json, csv, yaml or count. Default: table.
- `[--user=<login>]`: User with the capability creationell_wp_theme_manage_consent.

Examples:

```bash
wp creationell-theme consent services list --user=admin --lang=en --format=json
```

### wp creationell-theme consent services presets

**Reads only.**

Lists the presets of the catalog and whether they are applied.

```bash
wp creationell-theme consent services presets [--format=<format>] [--user=<login>]
```

Options:

- `[--format=<format>]`: Output format: table, json, csv, yaml or count. Default: table.
- `[--user=<login>]`: User with the capability creationell_wp_theme_manage_consent.

Examples:

```bash
wp creationell-theme consent services presets --user=admin
```

## wp creationell-theme css

Builds the individual stylesheet of the design settings and shows its state.

"css build" compiles when the fingerprint changed or with --force and ends with exit code 1 when the build failed; "css status" shows the stored state, whether it is stale and the right-to-left languages, for which the build adds a right-to-left file. Both change no setting and need no --user. The class also collects the section "css" of "wp creationell-theme doctor".

### wp creationell-theme css build

**Writes.** On a site that is not local, ask the human first.

Builds the individual stylesheet when the design settings or the SCSS files changed.

With every design setting at its default and no child SCSS the package stylesheet applies and the own files are deleted. A failed build keeps the previous stylesheet active and ends with exit code 1.

```bash
wp creationell-theme css build [--force]
```

Options:

- `[--force]`: Compile even when the fingerprint is unchanged.

Examples:

```bash
wp creationell-theme css build
```

### wp creationell-theme css status

**Reads only.**

Shows the state of the individual stylesheet.

Fields: the stored state (status ok, package or failed, line, fingerprint, files, theme version, time, seconds, peak memory, error), "stale" (a build would change the stylesheet) and "rtl_languages" (active languages written right to left; with them the build adds files.rtl). Computes the fingerprint; the front end never does.

```bash
wp creationell-theme css status [--format=<format>]
```

Options:

- `[--format=<format>]`: Output format. Default: `table`. Values: `table`, `json`.

Examples:

```bash
wp creationell-theme css status --format=json
```

## wp creationell-theme doctor

Reports the state of the theme in sections; reads only.

Built-in sections: "theme" (slug, version, requirements, update manifest URL) and "bootstrap_line" (active line, available lines, reasons, constant). Other parts of the theme add their own section with add_section(), so they never edit this class.

### wp creationell-theme doctor

**Reads only.**

Reports the state of the theme.

```bash
wp creationell-theme doctor [--format=<format>]
```

Options:

- `[--format=<format>]`: Output format. Default: `table`. Values: `table`, `json`.

Examples:

```bash
wp creationell-theme doctor --format=json
```

## wp creationell-theme module

Lists, shows, counts and switches the theme modules.

"list", "status" and "scan" only read. "enable" and "disable" switch through Module_Switcher (channel "cli") and need `--user=<login>` of a user with the capability creationell_wp_theme_manage_modules; without it they stop with exit code 1. Switching off a module whose blocks live contents use, or a module with warn_before_off, needs --yes; without it nothing changes and the command exits with 1. "disable" goes to "hidden" when the module knows it, to "off" otherwise; --state=off switches off directly, also from "active". The command also adds the section "modules" to "wp creationell-theme doctor".

### wp creationell-theme module disable

**Writes.** On a site that is not local, ask the human first.

Switches a module to hidden or off.

Without --state the module goes to hidden when it knows that state, to off otherwise. Switching off a module whose blocks live contents use, or a module that always asks before it goes off, needs --yes.

```bash
wp creationell-theme module disable <slug> [--state=<state>] [--yes] [--dry-run]
```

Options:

- `<slug>`: Module slug, e.g. post-lists.
- `[--state=<state>]`: Target state; default hidden when the module knows it, otherwise off. Values: `hidden`, `off`.
- `[--yes]`: Confirm the consequences of switching off.
- `[--dry-run]`: Show what would change; change nothing.

Preview without changes: `--dry-run`.

`--yes` skips the confirmation; on a site that is not local only with the human's consent.

Examples:

```bash
wp creationell-theme module disable post-lists --user=admin
wp creationell-theme module disable post-lists --state=off --yes --user=admin
```

### wp creationell-theme module enable

**Writes.** On a site that is not local, ask the human first.

Switches a module on.

```bash
wp creationell-theme module enable <slug> [--state=<state>] [--dry-run]
```

Options:

- `<slug>`: Module slug, e.g. contact-form-7.
- `[--state=<state>]`: Target state. Default: `active`. Values: `active`, `hidden`.
- `[--dry-run]`: Show what would change; change nothing.

Preview without changes: `--dry-run`.

Examples:

```bash
wp creationell-theme module enable contact-form-7 --user=admin
wp creationell-theme module enable post-lists --state=hidden --user=admin
```

### wp creationell-theme module list

**Reads only.**

Lists all known modules with configured and effective state, origin and reason.

```bash
wp creationell-theme module list [--format=<format>]
```

Options:

- `[--format=<format>]`: Output format. Default: `table`. Values: `table`, `json`, `csv`, `yaml`, `count`.

Examples:

```bash
wp creationell-theme module list --format=json
```

### wp creationell-theme module scan

**Reads only.**

Counts the contents that use the blocks of a module; reads only.

Live contents are published posts, drafts and the like; stored ones are revisions, posts in the trash and auto drafts.

```bash
wp creationell-theme module scan <slug> [--format=<format>]
```

Options:

- `<slug>`: Module slug, e.g. post-lists.
- `[--format=<format>]`: Output format. Default: `table`. Values: `table`, `json`.

Examples:

```bash
wp creationell-theme module scan post-lists --format=json
```

### wp creationell-theme module status

**Reads only.**

Shows the state of one module.

```bash
wp creationell-theme module status <slug> [--format=<format>]
```

Options:

- `<slug>`: Module slug, e.g. header-footer.
- `[--format=<format>]`: Output format. Default: `table`. Values: `table`, `json`.

Examples:

```bash
wp creationell-theme module status header-footer
```

## wp creationell-theme settings

Lists, reads, sets and resets the theme settings and rebuilds their snapshots.

Writes go through the setter with the channel "cli" and the user of the global parameter --user; without a user with the capability of the setting the setter refuses. "set" and "reset" end with exit code 0 for saved, removed and unchanged, otherwise with 1 and the message of the setter. Without --lang the commands work in the default language. The class also collects the section "settings" of "wp creationell-theme doctor".

Exports, imports, backs up and restores the theme settings and shows the transfer log.

The transfer commands export, import, backup list, backup create, backup restore and log sit next to the settings commands of the settings core. Each of them needs the global parameter --user of a user with the capability creationell_wp_theme_import_export: the log names the person, and the setter checks the capability of every key for that user. Their exit code 0 means done or dry run; 1 means nothing was written (every Transfer_Error, a file that cannot be read or written, a missing user). A declined question of WP_CLI::confirm() ends with 0 and writes nothing, as everywhere in WP-CLI.

Import and restore show the plan first: a table of every row with section, key, language, current and incoming value, status and message, then the warnings. Without --dry-run the command asks before it writes; --yes answers the question and also confirms the module switches with the live contents counted in the plan.

### wp creationell-theme settings backup create

**Writes.** On a site that is not local, ask the human first.

Backs up the settings now, in every active language.

The store keeps the five newest backups.

```bash
wp creationell-theme settings backup create [--sections=<sections>]
```

Options:

- `[--sections=<sections>]`: Comma-separated sections; default: every section.

Examples:

```bash
wp creationell-theme settings backup create --user=admin
```

### wp creationell-theme settings backup list

**Reads only.**

Lists the backups, newest first, without their data.

```bash
wp creationell-theme settings backup list [--format=<format>]
```

Options:

- `[--format=<format>]`: Output format. Default: `table`. Values: `table`, `json`.

Examples:

```bash
wp creationell-theme settings backup list --format=json --user=admin
```

### wp creationell-theme settings backup restore

**Writes.** On a site that is not local, ask the human first.

Restores a backup after a preview, with a backup of the current state first.

```bash
wp creationell-theme settings backup restore <id> [--dry-run] [--yes] [--format=<format>]
```

Options:

- `<id>`: Backup ID, see "backup list".
- `[--dry-run]`: Show the plan and write nothing.
- `[--yes]`: Answer the question with yes and confirm the module switches.
- `[--format=<format>]`: Output: the preview table, or the report as JSON; the question without --yes always shows the table first. Default: `table`. Values: `table`, `json`.

Preview without changes: `--dry-run`.

`--yes` skips the confirmation; on a site that is not local only with the human's consent.

Examples:

```bash
wp creationell-theme settings backup restore 20260925-100000-a1b2c3 --dry-run --user=admin
```

### wp creationell-theme settings export

**Writes.** On a site that is not local, ask the human first.

Exports the settings as JSON file.

Writes to standard output unless --file names a file. Sensitive keys are left out and named in the file; the export is logged.

```bash
wp creationell-theme settings export [--file=<path>] [--force] [--sections=<sections>] [--lang=<codes>]
```

Options:

- `[--file=<path>]`: File to write; without it the JSON goes to standard output.
- `[--force]`: Replace an existing file.
- `[--sections=<sections>]`: Comma-separated sections; default: every section, consent_services only for a user who may manage the consent.
- `[--lang=<codes>]`: Comma-separated language codes; default: every active language.

Examples:

```bash
wp creationell-theme settings export --file=settings.json --user=admin
wp creationell-theme settings export --sections=settings --lang=de --user=admin > de.json
```

### wp creationell-theme settings get

**Reads only.**

Prints the value of a setting.

```bash
wp creationell-theme settings get <key> [--lang=<code>] [--format=<format>]
```

Options:

- `<key>`: Setting key, e.g. color_primary.
- `[--lang=<code>]`: Language; default: the default language. A translated key falls back to the default language.
- `[--format=<format>]`: Output format. Default: `plain`. Values: `plain`, `json`.

Examples:

```bash
wp creationell-theme settings get color_primary
wp creationell-theme settings get footer_text --lang=en --format=json
```

### wp creationell-theme settings import

**Writes.** On a site that is not local, ask the human first.

Imports a settings file after a preview.

The file is checked like an upload (at most 1 MB, a name ending in .json, no part .php), migrated and planned; the plan shows every key and module. Before it writes, the command backs up the touched sections. Rejected values stay as they are and are listed; --strict imports nothing then.

```bash
wp creationell-theme settings import <file> [--sections=<sections>] [--lang=<codes>] [--map-lang=<pairs>] [--dry-run] [--strict] [--yes] [--format=<format>]
```

Options:

- `<file>`: Path of the JSON file.
- `[--sections=<sections>]`: Comma-separated sections; default: every section of the file, without consent_services for a user who may not manage the consent.
- `[--lang=<codes>]`: Comma-separated language codes; default: every active language.
- `[--map-lang=<pairs>]`: Import the texts of a file language as another active language, e.g. en:fa or en:fa,de:fr. --lang then filters the target languages.
- `[--dry-run]`: Show the plan and write nothing.
- `[--strict]`: Import nothing when a value is rejected.
- `[--yes]`: Answer the question with yes and confirm the module switches.
- `[--format=<format>]`: Output: the preview table, or the report as JSON; the question without --yes always shows the table first. Default: `table`. Values: `table`, `json`.

Preview without changes: `--dry-run`.

`--yes` skips the confirmation; on a site that is not local only with the human's consent.

Examples:

```bash
wp creationell-theme settings import settings.json --dry-run --user=admin
wp creationell-theme settings import translated.json --map-lang=en:fa --dry-run --user=admin
wp creationell-theme settings import settings.json --yes --format=json --user=admin
```

### wp creationell-theme settings list

**Reads only.**

Lists the settings with value, origin and lock.

```bash
wp creationell-theme settings list [--lang=<code>] [--section=<section>] [--format=<format>]
```

Options:

- `[--lang=<code>]`: Language of the values; default: the default language.
- `[--section=<section>]`: Only settings of one section: system, modules, design or texts.
- `[--format=<format>]`: Output format. Default: `table`. Values: `table`, `json`.

Examples:

```bash
wp creationell-theme settings list --section=design --format=json
```

### wp creationell-theme settings log

**Reads only.**

Shows the transfer log, newest first; it holds no values.

```bash
wp creationell-theme settings log [--format=<format>]
```

Options:

- `[--format=<format>]`: Output format. Default: `table`. Values: `table`, `json`.

Examples:

```bash
wp creationell-theme settings log --user=admin
```

### wp creationell-theme settings rebuild

**Writes.** On a site that is not local, ask the human first.

Rebuilds the settings snapshots of all active languages from the stored rows.

Needed after a theme update or rows written without the theme; the admin does it on the next admin page. Changes no setting and needs no --user.

```bash
wp creationell-theme settings rebuild
```

Examples:

```bash
wp creationell-theme settings rebuild
```

### wp creationell-theme settings reset

**Writes.** On a site that is not local, ask the human first.

Removes the backend value of a setting, so the next lower layer applies; needs --user with the capability of the setting.

Exit code 0 for removed and unchanged (no backend value), 1 with the message of the setter otherwise.

```bash
wp creationell-theme settings reset <key> [--lang=<code>]
```

Options:

- `<key>`: Setting key, e.g. color_primary.
- `[--lang=<code>]`: Language of a translated text; default: the default language.

Examples:

```bash
wp --user=admin creationell-theme settings reset color_primary
```

### wp creationell-theme settings set

**Writes.** On a site that is not local, ask the human first.

Sets a setting through the setter; needs --user with the capability of the setting.

Exit code 0 for saved, removed (the value equals the next lower layer) and unchanged; 1 with the message of the setter otherwise, e.g. for a color below the contrast minimum.

```bash
wp creationell-theme settings set <key> <value> [--lang=<code>]
```

Options:

- `<key>`: Setting key, e.g. color_primary.
- `<value>`: New value, e.g. '#6f2da8'.
- `[--lang=<code>]`: Language of a translated text; default: the default language.

Examples:

```bash
wp --user=admin creationell-theme settings set color_primary '#6f2da8'
wp --user=editor creationell-theme settings set footer_text 'Hello' --lang=en
```

## wp creationell-theme update

Shows the update state of the parent theme; installs nothing.

### wp creationell-theme update status

**Reads only.** May refresh the cached update offer; installs nothing.

Shows the installed version, the offer of the update manifest and why an update cannot be installed.

The field "status" is update_available, up_to_date, or the reason: manifest_unavailable, checksum_missing, line_unavailable, incompatible. The command reads the cached manifest; a failed fetch is retried after five minutes. Automatic updates of the theme are always disabled.

```bash
wp creationell-theme update status [--refresh] [--format=<format>]
```

Options:

- `[--refresh]`: Fetch the manifest again instead of reading the cache.
- `[--format=<format>]`: Output format. Default: `table`. Values: `table`, `json`.

Examples:

```bash
wp creationell-theme update status
wp creationell-theme update status --refresh --format=json
```

## wp creationell-theme wpml

Shows the WPML state of the site; reads only.

### wp creationell-theme wpml status

**Reads only.**

Shows the WPML state: versions, languages, URL mode, wpml-config.xml, snapshots and findings.

The JSON output equals the section "wpml" of "wp creationell-theme doctor". Writes nothing; stale snapshots are only reported.

```bash
wp creationell-theme wpml status [--format=<format>] [--strict]
```

Options:

- `[--format=<format>]`: Output format. Default: `table`. Values: `table`, `json`.
- `[--strict]`: Exit with 1 when there is at least one finding of the level warning.

Examples:

```bash
wp creationell-theme wpml status --format=json --strict
```

## Doctor sections

| Section | When |
|---|---|
| `theme` | always |
| `bootstrap_line` | always |
| `blog` | only while the module `blog` is active |
| `cbb` | always |
| `child` | always |
| `consent` | always |
| `css` | always |
| `header_footer` | always |
| `modules` | always |
| `settings` | always |
| `wpml` | always |
