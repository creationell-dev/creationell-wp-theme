# Agent guide

This folder explains the theme to coding agents that work on a project with a child theme; start with [AGENTS.md](../AGENTS.md).

## Guides

- [architecture.md](architecture.md): how the theme loads, its folders, its names and its public API.
- [child-theme.md](child-theme.md): the contract of the child theme: allowed paths, rules, filters, precedence, module states, rights and a scaffold.
- [styles-and-scss.md](styles-and-scss.md): project SCSS, building the stylesheet on the site, right to left, theme.json and fonts.
- [blog.md](blog.md): tasks and traps of the module blog.
- [integrations.md](integrations.md): what the theme expects of CreaBootstrapBlocks, Blockstudio, WPML, Contact Form 7, the consent module, Shariff Wrapper and JPKCom Post Filter.
- [accessibility.md](accessibility.md): the patterns that overrides and project code keep: landmarks, headings, skip links, link names, live regions, focus, contrast, and a quick check.
- [i18n.md](i18n.md): text domains, translatable texts, Sie and Du, WPML String Translation and the generated files.
- [security.md](security.md): escaping, input and nonces, rights, scripts of third parties through consent, secrets.

## Recipes

One task on a project site each, with steps that use only WP-Admin, WP-CLI and files of the child theme, and a check at the end.

- [recipes/card-markup.md](recipes/card-markup.md): classes of the post cards through their filter, or a copied card for new markup.
- [recipes/change-child-styles.md](recipes/change-child-styles.md): project SCSS, Bootstrap variables and fonts in the child theme, built on the site.
- [recipes/child-bootstrap-lines.md](recipes/child-bootstrap-lines.md): the header `Bootstrap Lines` of the child theme and the active Bootstrap line.
- [recipes/create-cf7-form.md](recipes/create-cf7-form.md): a Contact Form 7 form from an accessible template, with labels, grouped choices and consent.
- [recipes/enable-cookie-banner.md](recipes/enable-cookie-banner.md): the module `consent`, its texts, the services and their cookies.
- [recipes/import-export-settings.md](recipes/import-export-settings.md): settings and module states as a JSON file, with a preview and a backup.
- [recipes/import-texts-as-other-language.md](recipes/import-texts-as-other-language.md): the texts of one file language imported as another language of the site.
- [recipes/load-script-with-consent.md](recipes/load-script-with-consent.md): a script of a project plugin that runs only after consent to its category.
- [recipes/override-child-template.md](recipes/override-child-template.md): a template of the parent theme copied to the same path in the child theme.
- [recipes/preset-module.md](recipes/preset-module.md): module defaults and locks in the `functions.php` of the child theme.
- [recipes/set-up-blog.md](recipes/set-up-blog.md): the posts page, the module `blog`, its settings and the optional post filter.
- [recipes/set-up-wpml-site.md](recipes/set-up-wpml-site.md): WPML with String Translation and ACF Multilingual for the theme.
- [recipes/switch-module.md](recipes/switch-module.md): a module switched on or off on one site, with a preview before it goes off.
- [recipes/translate-texts.md](recipes/translate-texts.md): translatable texts in the child theme and their translation in WPML.

## Reference

Do not edit these files.

- [reference/hooks.md](reference/hooks.md): overview of the own hooks and of the files that list them. Generated from the source.
- [reference/hooks-classes.md](reference/hooks-classes.md): the filters for the CSS classes of each place. Generated from the source.
- [reference/hooks-core.md](reference/hooks-core.md): hooks of the theme core. Generated from the source.
- [reference/hooks-modules.md](reference/hooks-modules.md): hooks of the modules. Generated from the source.
- [reference/hooks-template-parts.md](reference/hooks-template-parts.md): hooks of the template parts. Generated from the source.
- [reference/hooks-templates.md](reference/hooks-templates.md): hooks of the root templates, the template helpers, the patterns and the page and single templates. Generated from the source.
- [reference/templates.md](reference/templates.md): every template a child theme can override. Generated from the source.
- [reference/modules.md](reference/modules.md): every module with its slug, type and states. Generated from the source.
- [reference/settings.md](reference/settings.md): every setting with its type, default and source. Generated from the source.
- [reference/cli.md](reference/cli.md): every `wp creationell-theme` command and whether it reads or writes. Generated from the source.
