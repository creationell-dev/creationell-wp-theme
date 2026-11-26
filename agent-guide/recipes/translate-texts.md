# Make a text translatable and renew the translations

## Goal

Write every new text of the child theme or of an override so that it can be translated, and keep the translations of the site current with WPML String Translation.

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)).
- The rules in full: [i18n.md](../i18n.md). The theme ships English and German in two forms of address: `de_DE` uses "du", `de_DE_formal` uses "Sie".

## Steps

1. Choose the text domain. A new text of the child theme or of an override uses `creationell-wp-child-theme`. A text copied unchanged from the parent theme into an override keeps `creationell-wp-theme`, so its shipped translations still apply; once you change the wording, the text is new.
2. Write the source text in English, as a whole sentence, with a literal text in the call: no variables and no joined pieces inside the translation function.
3. Use the function that also escapes, such as `esc_html__()` or `esc_attr__()`. Put variable parts into placeholders, with a `translators:` comment right above the call. Use `_n()` for a text with a number:

   ```php
   printf(
   	/* translators: %s: number of results */
   	esc_html( _n( '%s result', '%s results', $count, 'creationell-wp-child-theme' ) ),
   	esc_html( number_format_i18n( $count ) )
   );
   ```

4. Labels for assistive technology, such as `aria-label`, are translatable too. Scripts get their texts from PHP as attributes.
5. Translate the texts per site in WPML String Translation: under WPML > Theme and plugins localization, scan the child theme (and the parent theme for corrections), then translate under WPML > String Translation, domain `creationell-wp-child-theme` or `creationell-wp-theme`. WPML keeps its own files, and they win over the files of the theme, also after a theme update. Setup of WPML: [set-up-wpml-site.md](set-up-wpml-site.md). On a site without WPML, ask the human how the project translates the texts of the child theme.
6. Texts of Theme settings > Texts, such as the footer text, are settings: keep them per language on the settings page, not in String Translation.
7. After every update of the parent theme or a change of the child theme, scan again in WPML and translate the new texts. The changelog at the end of the [README of the theme](../../README.md) lists the new or changed texts of every release.

## Check

- `wp creationell-theme child check` gives `Success: 0 violations.`; the child theme holds no folder `languages/`.
- `wp creationell-theme wpml status` shows the languages of the site and no warning.
- Open a page in each language on the site: the new text shows translated, in German in the form of address of the site.

## Common mistakes

- A new text with the text domain of the parent theme: no translation of the site applies to it.
- A sentence built from pieces, or a variable inside the translation function: the text cannot be translated or is never found.
- Editing the files in `languages/` of the parent theme or adding a folder `languages/` to the child theme: a theme update replaces the first, and `child check` rejects the second.
- For "Sie" with WPML, the WPML language `de` needs the locale `de_DE_formal` ([i18n.md](../i18n.md#german-sie-and-du)).
