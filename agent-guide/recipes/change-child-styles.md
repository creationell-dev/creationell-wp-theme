# Change styles in the child theme

## Goal

Change the look of a project with SCSS in the child theme and fonts of its own, and let the site build the stylesheet. Colors and fonts that the theme settings cover are set there, not in SCSS.

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)). Editing files, `wp creationell-theme css build` and `wp creationell-theme settings set` all change the site.
- The header `Bootstrap Lines` in the `style.css` of the child theme declares the active Bootstrap line, for example `Bootstrap Lines: 5` ([child-bootstrap-lines.md](child-bootstrap-lines.md)). Without it the build leaves the project styles out.
- There is no build on your machine: the site compiles the SCSS itself. Details: [styles-and-scss.md](../styles-and-scss.md).

## Steps

1. For a color or a font of the design settings, set the setting instead of writing SCSS, for example `wp creationell-theme settings set color_primary '#6f2da8' --user=<login>`. All keys: [reference/settings.md](../reference/settings.md).
2. Put Bootstrap variables of the project into `assets/scss/_project-variables.scss` of the child theme, for example `$border-radius: .5rem;`. The build reads this file before Bootstrap.
3. Put the styles of the project into `assets/scss/project.scss` of the child theme. The build reads it after the styles of the theme, with the Bootstrap variables, mixins and utilities available. Further partials go into `assets/scss/` and its subfolders and are imported from these two files.
4. Use logical properties such as `margin-inline-start`, so the styles also work in languages written right to left.
5. For a font of the project, copy its `.woff2` files directly into `assets/fonts/` of the child theme, then add it with the filter [`creationell_wp_theme_font_catalog`](../reference/hooks-core.md#creationell_wp_theme_font_catalog) in the `functions.php` of the child theme. The example is in [styles-and-scss.md](../styles-and-scss.md#fonts). Choose the font on the design page or with `wp creationell-theme settings set font_body <slug> --user=<login>`.
6. Build the stylesheet: `wp creationell-theme css build`. Saving the design settings or the button "Rebuild the stylesheet" in the admin does the same. A failed build keeps the previous stylesheet and ends with exit code 1.

## Check

- `wp creationell-theme css status --format=json` shows `stale` as `false` and the status `ok`. A status `failed` names the error: fix the SCSS and build again.
- `wp creationell-theme child check` gives `Success: 0 violations.`
- `wp creationell-theme doctor` reports no new problem in its sections `css` and `child`.
- Open a page on the site and look at the changed element, also with a language written right to left if the site has one.

## Common mistakes

- Compiled CSS in the child theme, a build tool on your machine or an edited file in `assets/css/bootstrap-5/` of the parent theme: the site compiles the SCSS itself, and a theme update replaces the parent files.
- A color in SCSS instead of the setting: the design page and its contrast check no longer know it.
- A `.woff2` file in a subfolder of `assets/fonts/`, or any other file there except its `README.md`, breaks the rule `allowed-paths`.
- A `theme.json` in the child theme with a color palette, font families or font sizes breaks the rule `no-token-presets`.
- Forgetting the build after an SCSS change: the site keeps the old stylesheet, and `css status` shows it as stale.
