<!-- Generated from the source. Do not edit. -->

# Modules

Every module ships switched off. Switch one with `wp creationell-theme module enable <slug>`, preset it from the child theme with the filter `creationell_wp_theme_modules`, or fix it with its constant. What the states and reasons of `wp creationell-theme module list` mean: [child-theme.md](../child-theme.md).

| Slug | Title | Type | States |
|---|---|---|---|
| `blog` | Blog | integration | `active`, `off` |
| `consent` | Cookie consent | block | `active`, `off` |
| `contact-form-7` | Contact Form 7 styling | integration | `active`, `off` |
| `header-footer` | Header and footer in the Site Editor | block | `active`, `off` |
| `post-filter` | Post filter | integration | `active`, `off` |
| `post-lists` | Post lists | block | `active`, `hidden`, `off` |
| `post-slider` | Post slider | block | `active`, `hidden`, `off` |
| `related-posts` | Related posts | block | `active`, `hidden`, `off` |

## `blog`: Blog

Sidebar with categories and tags, reading time, back link, share links, a richer 404 page and optional author pages for the posts of the site.

- Type: integration
- States: `active`, `off`
- Requires: nothing
- Files outside the module folder: `template-parts/blog/`
- Switch: setting `module_blog`, constant `CREATIONELL_WP_THEME_MODULE_BLOG`
- Read more: [modules/blog/README.md](../../modules/blog/README.md)

## `consent`: Cookie consent

Cookie banner and preferences dialog based on CookieConsent. Not legally reviewed.

- Type: block
- States: `active`, `off`
- Requires: nothing
- Blocks: `creationell-theme/consent-link`
- Replaces the older plugin: `bs-cookie-settings/main.php`
- Asks before switching off: The cookie banner, the preferences dialog and the withdrawal link disappear immediately. Marked scripts stay blocked.
- Switch: setting `module_consent`, constant `CREATIONELL_WP_THEME_MODULE_CONSENT`
- Read more: [modules/consent/README.md](../../modules/consent/README.md)

## `contact-form-7`: Contact Form 7 styling

Bootstrap styling and accessible behaviour for Contact Form 7 forms. Form templates come from the plugin "Contact Form 7: Accessible Defaults".

- Type: integration
- States: `active`, `off`
- Requires: plugin `contact-form-7/wp-contact-form-7.php`
- Replaces the older plugin: `bs-contact-form-7/main.php`
- Switch: setting `module_contact_form_7`, constant `CREATIONELL_WP_THEME_MODULE_CONTACT_FORM_7`
- Read more: [modules/contact-form-7/README.md](../../modules/contact-form-7/README.md)

## `header-footer`: Header and footer in the Site Editor

Header and footer as template parts that editors change in the Site Editor. While the module is off, the theme shows its own header and footer.

- Type: block
- States: `active`, `off`
- Requires: Blockstudio 7.6 or newer
- Blocks: `creationell-theme/navbar`, `creationell-theme/logo`, `creationell-theme/search`, `creationell-theme/footer-menu`, `creationell-theme/footer-text`, `creationell-theme/contact`, `creationell-theme/social-links`, `creationell-theme/language-switcher`
- Files outside the module folder: `parts/header.html`, `parts/footer.html`, `patterns/header-footer/`, `template-parts/header-footer/`
- Asks before switching off: Customised header and footer parts stay in the database but are no longer shown.
- Switch: setting `module_header_footer`, constant `CREATIONELL_WP_THEME_MODULE_HEADER_FOOTER`
- Read more: [modules/header-footer/README.md](../../modules/header-footer/README.md)

## `post-filter`: Post filter

Shows the filters of the plugin JPKCom Post Filter in the blog sidebar instead of the plain category and tag lists.

- Type: integration
- States: `active`, `off`
- Requires: plugin `jpkcom-post-filter/jpkcom-post-filter.php`, module `blog`
- Files outside the module folder: `template-parts/post-filter/`
- Switch: setting `module_post_filter`, constant `CREATIONELL_WP_THEME_MODULE_POST_FILTER`
- Read more: [modules/post-filter/README.md](../../modules/post-filter/README.md)

## `post-lists`: Post lists

Blocks for post lists as a grid, a list or large image cards, and as an accordion or tabs. Replaces the plugin bs Grid.

- Type: block
- States: `active`, `hidden`, `off`
- Requires: Blockstudio 7.6 or newer
- Blocks: `creationell-theme/post-list`, `creationell-theme/post-accordion`
- Files outside the module folder: `template-parts/post-lists/`
- Replaces the older plugin: `bs-grid/main.php`
- Switch: setting `module_post_lists`, constant `CREATIONELL_WP_THEME_MODULE_POST_LISTS`
- Read more: [modules/post-lists/README.md](../../modules/post-lists/README.md)

## `post-slider`: Post slider

Block for a slider of posts, as cards in columns or as large image cards. Replaces the post sliders of the plugin bs Swiper.

- Type: block
- States: `active`, `hidden`, `off`
- Requires: Blockstudio 7.6 or newer
- Blocks: `creationell-theme/post-slider`
- Files outside the module folder: `template-parts/post-slider/`
- Replaces the older plugin: `bs-swiper/main.php`
- Switch: setting `module_post_slider`, constant `CREATIONELL_WP_THEME_MODULE_POST_SLIDER`
- Read more: [modules/post-slider/README.md](../../modules/post-slider/README.md)

## `related-posts`: Related posts

Related posts below a single post and as a block: posts that share a category or a tag with the current post. Needs the module Post lists.

- Type: block
- States: `active`, `hidden`, `off`
- Requires: module `post-lists`, Blockstudio 7.6 or newer
- Blocks: `creationell-theme/related-posts`
- Files outside the module folder: `template-parts/related-posts/`
- Replaces the older plugin: `bs-swiper/main.php`
- Switch: setting `module_related_posts`, constant `CREATIONELL_WP_THEME_MODULE_RELATED_POSTS`
- Read more: [modules/related-posts/README.md](../../modules/related-posts/README.md)

Reserved slug without a module in this package: `woocommerce`.
