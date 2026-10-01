# Module contact-form-7

Bootstrap styling and accessible behaviour for forms of the plugin
[Contact Form 7](https://contactform7.com/). The module brings no form
templates, no mail templates, no block and no settings: form templates come from
the plugin "Contact Form 7: Accessible Defaults".

| | |
|---|---|
| States | `active`, `off` (default `off`) |
| Needs | Contact Form 7 6.0 or later (`contact-form-7/wp-contact-form-7.php`) |
| Replaces | the plugin bs Contact Form 7 (`bs-contact-form-7/main.php`) |
| Settings | none |
| Outposts | none |

Switch it on in *Theme settings > Modules*, with
`wp creationell-theme module enable contact-form-7 --user=<admin>`, with the
constant in `wp-config.php` or with the filter `creationell_wp_theme_modules`
of a child theme (see `modules/README.md`). The golden path
`docs/golden-paths/create-cf7-form.md` of the repository shows the whole way
from the plugins to a checked form.

## What the module does

While the module is `active` and Contact Form 7 6.0 or later runs:

- The stylesheet of Contact Form 7 stays off (`wpcf7_load_css`). The module
  stylesheet `assets/css/contact-form-7.css` (handle
  `creationell-wp-theme-contact-form-7`) loads on every front-end page after the
  theme stylesheet. It uses only the Bootstrap variables of the theme, so theme
  colors and the dark mode apply.
- Contact Form 7 adds no paragraphs and line breaks (autop) to the form markup.
  Mails keep the autop setting of Contact Form 7.
- Error texts show below the fields; the live region of Contact Form 7 is hidden
  only visually, so screen readers still announce the result.
- The status message after sending looks like a Bootstrap alert, colored by the
  result: sent (success), invalid input, missing consent, failed or aborted
  (danger), spam (warning), payment required (info). A decorative icon repeats
  the color; the text carries the meaning.
- The spinner of Contact Form 7 looks like a small Bootstrap spinner and turns
  slower when the visitor asks for reduced motion.
- After a failed submission the script `assets/js/contact-form-7.js` moves the
  focus to the first invalid field, or after missing consent to the unchecked
  consent checkbox. It loads only where Contact Form 7 loads its own script.

The module adds nothing when Contact Form 7 is missing, older than 6.0, or when
bs Contact Form 7 is still active; Contact Form 7 then keeps its own look.

## Bootstrap classes in the form markup

The filter `wpcf7_form_elements` (priority 20, after other plugins such as
captcha widgets on 10) adds Bootstrap classes to the markup of Contact Form 7.
It only adds classes; it removes nothing, changes no other attribute and gives
the same markup when it runs twice.

| Element of Contact Form 7 | Added classes (role) |
|---|---|
| text, email, url, tel, number, date, password and file inputs, text areas, the free text input of a checkbox | `form-control` (`control`) |
| selects | `form-select` (`select`) |
| range inputs | `form-range` (`range`) |
| submit input or button | `btn btn-primary` (`submit`); only `btn` when the submit already has a `btn-` class, e.g. `[submit class:btn-outline-secondary "Send"]` |
| list item of a checkbox, radio or acceptance field | `form-check` (`choice`) |
| its checkbox or radio input | `form-check-input` (`choice_input`) |
| its label text | `form-check-label` (`choice_label`) |

Hidden inputs, captcha widgets and markup without the classes of Contact Form 7
stay unchanged. The submit stays an `<input>`. A field with the class option
`class:creationell-theme-cf7-raw` keeps the markup of Contact Form 7; on a
checkbox, radio or acceptance field the option covers the whole group.

## Notes for editors

- Start every form from a template of "Contact Form 7: Accessible Defaults".
- Use the option `use_label_element` for checkboxes and radio buttons, so the
  text next to a box is its label and a larger click target.
- Set `acceptance_as_validation: on` in the additional settings of a form with
  a consent checkbox. Without it Contact Form 7 keeps the submit button disabled
  until the box is checked.

## Filters

`creationell_wp_theme_cf7_autop` (bool, default `false`): `true` keeps the autop
of Contact Form 7 in forms. Values other than `true` or `false` give `false` and
a notice.

```php
add_filter( 'creationell_wp_theme_cf7_autop', '__return_true' );
```

`creationell_wp_theme_cf7_classes` (array, default in the table above): class
lists per role (`control`, `select`, `range`, `submit`, `choice`,
`choice_input`, `choice_label`). Missing roles keep their defaults, an empty
list adds no class. Each class passes `sanitize_html_class()`; a string may hold
several classes separated by spaces. Unknown roles and values that are no list
of strings fall back to the defaults and give a notice. The first `submit`
class is always added, the others only without an own `btn-` class.

```php
add_filter(
	'creationell_wp_theme_cf7_classes',
	static fn( array $classes ): array => array_merge( $classes, array( 'submit' => array( 'btn', 'btn-dark' ) ) )
);
```

`creationell_wp_theme_cf7_inline_choices` (bool, default `false`): `true` puts
the choices of checkbox and radio fields side by side (`form-check-inline`).
Stacked choices are easier to read and to hit. Values other than `true` or
`false` give `false` and a notice.

```php
add_filter( 'creationell_wp_theme_cf7_inline_choices', '__return_true' );
```

## Limits

- The module styles Contact Form 7 only; other form plugins keep their look.
- The script of Contact Form 7 loads as the plugin decides; the module does not
  load it conditionally.
- No floating validation tips (`use-floating-validation-tip`), no styles for the
  form block in the editor, no reCAPTCHA integration. The reCAPTCHA badge stays
  visible.
- The module sets no cookies and stores nothing in the database.

## Tests

Group `cf7` of the repository: unit tests of the classes, the stylesheet and
the manifest; markup and WordPress cases in wp-env
(`composer test:integration -- --profile=cf7`); end-to-end cases with axe
against the development copy (`tests/e2e/theme/cf7.spec.ts` after the seed
`tests/e2e/theme/seed-cf7.php`). Screen reader and zoom checks are manual:
section "Contact Form 7 forms" of `docs/testing/manual-a11y-checklist.md`.

## Files

| Path | Content |
|---|---|
| `module.php` | Manifest |
| `settings.php` | Settings: none |
| `bootstrap.php` | Loads the classes and registers the hooks |
| `inc/class-module.php` | Guards and hooks |
| `inc/class-assets.php` | Stylesheet and script |
| `inc/class-form-markup.php` | Bootstrap classes in the form markup |
| `assets/css/contact-form-7.css` | Stylesheet, plain CSS without build step |
| `assets/js/contact-form-7.js` | Focus script, plain JavaScript without build step |
