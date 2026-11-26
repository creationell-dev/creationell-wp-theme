# Styles and SCSS

The styles of a project live in the child theme as SCSS. The site compiles them together with the theme styles and the design settings into one stylesheet.

No build on your machine: the site compiles the SCSS with `wp creationell-theme css build`. Never add compiled CSS to the child theme and never edit the files in `assets/css/bootstrap-5/` or `assets/vendor/` of the parent theme.

## Project files

- `assets/scss/_project-variables.scss`: Bootstrap variables of the project, for example `$border-radius: .5rem;`. The build reads it before Bootstrap. The theme fixes a few variables after this file, mostly for accessibility (focus ring, field borders, code color), so a project value for them has no effect: `$gray-600`, `$focus-ring-opacity`, `$btn-focus-outline-width`, `$input-border-color`, `$input-focus-border-color`, `$form-check-input-border` and `$code-color`. Change the affected rules in `assets/scss/project.scss` instead, and keep their contrast.
- `assets/scss/project.scss`: the styles of the project, with the Bootstrap variables, mixins and utilities available. The build reads it after the styles of the theme.
- Further partials in `assets/scss/` and its subfolders, imported from these two files.

Colors and fonts come from the theme settings, not from SCSS variables: change them with `wp creationell-theme settings set` or on the design page ([reference/settings.md](reference/settings.md)). Use logical properties (`margin-inline-start` instead of `margin-left`), so the styles also work in languages written right to left.

## Bootstrap Lines

The parent theme compiles the SCSS of the child theme only for the Bootstrap lines its `style.css` declares in the header `Bootstrap Lines`, for example `Bootstrap Lines: 5`. While the active line is not declared, the build leaves the project styles out. A missing header, or a line this theme version does not include, gives an admin notice, and `wp creationell-theme child check` reports it under the rule `lines-declared`.

## Building the stylesheet

- The stylesheet is built when the design settings are saved. After a change of the SCSS files the admin shows a notice with the button "Rebuild the stylesheet".
- `wp creationell-theme css build` compiles when the fingerprint of the design settings and the SCSS files changed; `--force` compiles also with an unchanged fingerprint. The command writes: on a site that is not local, ask the human first.
- `wp creationell-theme css status` reads only. It shows the stored state (`ok`, `package` or `failed`), the line, the fingerprint, whether the stylesheet is stale and the active languages written right to left.
- With every design setting at its default and no SCSS in the child theme, the package stylesheet of the theme applies and the own files are deleted.
- A failed build keeps the previous stylesheet active and ends with exit code 1; the error is in `wp creationell-theme css status`. Fix the SCSS and build again.

After an SCSS change, run `wp creationell-theme css status` before you finish: the stylesheet must not be stale. Options of both commands: [reference/cli.md](reference/cli.md).

## Right to left

While a language written right to left is active (Arabic, Hebrew, Farsi, ...), the build adds a mirrored copy of the stylesheet for it, project styles included: `margin-left` becomes `margin-right`, `translateX(10px)` becomes `translateX(-10px)`.

- Logical properties such as `margin-inline-start` need no mirroring and stay as they are.
- `/* rtl:ignore */` in front of a declaration keeps it unchanged.
- Not mirrored: positions with lengths in `background-position` and `object-position`, `object-position: left`, `perspective-origin` in percent, the angle of `conic-gradient(from ...)`, `left` before a slash in the `background` shorthand, transform values taken from `var()` or `env()`, and `env(safe-area-inset-left)` or `env(safe-area-inset-right)` in `inset`, `scroll-padding`, `scroll-margin`, `width` and logical properties. Give such a declaration a value directive (`background-position: 10px 20px /*rtl:right 10px top 20px*/`) or a rule under `[dir=rtl]`, and a variable its own value (`[dir=rtl] .teaser { --shift: -10px; }`).
- A `/*rtl:raw:...*/` belongs before or after a declaration: inside a value it stops the build, after the last rule of `@media` or of a file it is lost.
- After a language written right to left is switched on, it gets the stylesheet of the theme without the design settings until the stylesheet is rebuilt; `wp creationell-theme doctor` warns.

## theme.json

A `theme.json` in the child theme must not set color palettes, font families or font sizes, also not per block: these tokens come from the parent theme and its settings. `wp creationell-theme child check` reports them under the rule `no-token-presets`. Other settings and styles of `theme.json` are allowed.

## Fonts

Fonts load from the site itself, never from a font service.

1. Copy the `.woff2` files into `assets/fonts/` of the child theme: directly in the folder, with names of letters, digits, `-`, `_` and `.`, one file per weight and style. Any other file there breaks the rule `allowed-paths`.
2. Add the font in `functions.php` with the filter [`creationell_wp_theme_font_catalog`](reference/hooks-core.md#creationell_wp_theme_font_catalog). The key is the slug of the font (lower case letters, digits, hyphens).
3. Choose the font on the design page, or set `font_body` or `font_headings` to the slug.

```php
add_filter(
	'creationell_wp_theme_font_catalog',
	static function ( $fonts ) {
		$fonts = is_array( $fonts ) ? $fonts : array();
		$fonts['brand-sans'] = array(
			'name'    => 'Brand Sans',
			'stack'   => '"Brand Sans", system-ui, sans-serif',
			'scripts' => array( 'latin', 'latin-ext' ),
			'faces'   => array(
				array( 'src' => 'file:./assets/fonts/brand-sans-regular.woff2', 'weight' => '400', 'style' => 'normal' ),
				array( 'src' => 'file:./assets/fonts/brand-sans-bold.woff2', 'weight' => '700', 'style' => 'normal' ),
			),
		);
		return $fonts;
	}
);
```

`scripts` lists the scripts the files cover (`latin`, `latin-ext`, `vietnamese`, `cyrillic`, `arabic`, `cjk`); the design page warns about languages of the site the font does not cover. An extra font for another script joins as further faces with a `unicode_range`. An invalid entry is left out of the catalog with a notice for developers. Add only fonts whose license allows self-hosting on the web.
