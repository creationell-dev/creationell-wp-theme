# Create a Contact Form 7 form from an accessible template

## Goal

Build a contact form that starts from an accessible template and shows in the Bootstrap look of the theme, with labels, grouped choices and a consent checkbox that works.

## Before you start

- A child theme `creationell-wp-child-theme` is active; without one, ask the human for the project's child theme package.
- On a site that is not local, ask the human before any step that changes it ([AGENTS.md](../../AGENTS.md)).
- The plugins: Contact Form 7 6.0 or later; install the versions the project names. Forms start from a template of the plugin "Contact Form 7: Accessible Defaults". The theme brings no form templates.
- `wp plugin list` shows which plugins are installed and active; it only reads.

## Steps

1. In WP-Admin under Plugins, install and activate Contact Form 7 and "Contact Form 7: Accessible Defaults". Deactivate the plugin bs Contact Form 7 where it is active: while it runs, the module stays off.
2. Switch the module on: `wp creationell-theme module enable contact-form-7 --user=<login>`, or the switch on Theme settings > Modules ([switch-module.md](switch-module.md)).
3. Under Contact > Add New, choose a template of the accessible defaults, for example "Basic", and adapt the fields. Keep one `<label for="...">` per text field and select.
4. Checkboxes and radio buttons: add the option `use_label_element`, so the text is the label and a larger click target. Group them in `<fieldset>` with `<legend>`.
5. Consent checkbox: add `acceptance_as_validation: on` under Additional Settings; without it Contact Form 7 keeps the submit button disabled.
6. Insert the form with its shortcode or the block Contact Form 7.

```text
<fieldset>
<legend>Topics</legend>
[checkbox topics id:topics use_label_element "Design" "Hosting"]
</fieldset>
<p>[acceptance privacy id:privacy] I agree that my data is processed. [/acceptance]</p>
```

A field with the class `creationell-theme-cf7-raw` (`class:creationell-theme-cf7-raw` in the form tag) keeps the markup of Contact Form 7. Other classes and choices side by side come from the filters of the module, not from the form ([README of the module](../../modules/contact-form-7/README.md)).

## Check

- `wp creationell-theme module status contact-form-7 --format=json` shows `effective` `active`; otherwise `reason` names the missing plugin or the active bs Contact Form 7.
- `wp plugin is-active contact-form-7` ends with exit code 0, and `wp plugin list --name=contact-form-7 --field=version` prints 6.0 or later.
- On the site, submit the empty form: the focus moves to the first invalid field and its error text shows. Then send it filled in.

## Common mistakes

- bs Contact Form 7 still active: the module stays `off` with the reason `legacy_plugin:bs-contact-form-7/main.php`.
- Contact Form 7 older than 6.0: the module shows `active` but adds nothing, and the forms keep the look of the plugin. Check the version with `wp plugin list --name=contact-form-7 --field=version`.
- A text field without its own label, or checkboxes without `use_label_element`: screen readers announce no name.
- A consent checkbox without `acceptance_as_validation: on`: the submit button stays disabled.
- Styling a form with classes in the form instead of the filters of the module.
