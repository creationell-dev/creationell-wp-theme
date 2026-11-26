# Understand the Bootstrap lines in the child theme

## Goal

Declare which Bootstrap lines the project styles of the child theme support, so the theme builds them, and read which line is active. A Bootstrap line is a major version of Bootstrap: line 5 is Bootstrap 5.

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)).
- This theme version includes line 5 only. A later version may include line 6 next to it; styles written for Bootstrap 5 do not always work with Bootstrap 6.

## Steps

1. Open the `style.css` of the child theme and keep the header `Bootstrap Lines` with the lines the project SCSS works with:

   ```css
   /*
   Theme Name: Project Child Theme
   Template: creationell-wp-theme
   Update URI: false
   Bootstrap Lines: 5
   */
   ```

2. One line is `Bootstrap Lines: 5`. Add a second line, comma-separated, only once the installed theme includes it (the section `bootstrap_line` of `wp creationell-theme doctor` lists the available lines) and the project SCSS works with it. With this theme version, `5` is the only valid value.
3. Read the active line with `wp creationell-theme doctor`. A site may request a line with the constant `CREATIONELL_WP_THEME_BOOTSTRAP_LINE` in `wp-config.php`; a line the theme does not include keeps line 5, and the doctor says why.
4. Before a line changes, test the project SCSS on the new line on a staging site, then add the line to the header, then switch the site. Each of these steps changes a site: ask the human first.

## Check

- `wp creationell-theme child check` gives `Success: 0 violations.` A missing header or a line the theme does not include shows the rule `lines-declared`.
- `wp creationell-theme doctor` shows the active line and the available ones in the section `bootstrap_line`, and the declared lines of the child theme in the section `child`.
- `wp creationell-theme css status` shows the line of the built stylesheet.

## Common mistakes

- No header `Bootstrap Lines`: the site keeps the styles of the theme without the project styles, the admin shows a notice and the doctor warns.
- Declaring a line the theme does not include, for example `5, 6` with this theme version: `child check` shows the rule `lines-declared`.
- Declaring a line before the project SCSS works with it: the build then compiles styles that break on that line.
- Changing the constant on a site that is not local without asking: it switches the whole site.
- The theme uses the project SCSS only for a declared line ([styles-and-scss.md](../styles-and-scss.md#bootstrap-lines)).
