<!-- Generated from the source. Do not edit. -->

# Hooks: modules

Hooks whose first path lies in `modules/`.

## `creationell_wp_theme_author_image_size`

Filter. Filters the image size of the profile image on the author page.

| Parameter | Type | Description |
|---|---|---|
| `$size` | `string\|array<int, int>` | Registered image size or width and height; default medium. |
| `$user` | `WP_User` | Author. |

- Returns: the filtered `$size`.
- Fired in: `modules/blog/inc/class-author-pages.php`

## `creationell_wp_theme_author_is_public`

Filter. Filters whether a user counts as a public author with an author page.

Without the filter a user with at least one published post is public; any other user gets the 404 page on the author address.

| Parameter | Type | Description |
|---|---|---|
| `$is_public` | `bool` | Whether the user has a published post. |
| `$user` | `WP_User` | User. |

- Returns: the filtered `$is_public`.
- Fired in: `modules/blog/inc/class-author-pages.php`

## `creationell_wp_theme_blog_sidebar_has_filter`

Filter. Filters whether the blog sidebar shows the filter bar of the module post-filter.

The module post-filter answers true where it prints its filter bar, so the sidebar shows also without widgets. Only true counts.

| Parameter | Type | Description |
|---|---|---|
| `$has_filter` | `bool` | Whether the sidebar shows a filter bar; false by default. |

- Returns: the filtered `$has_filter`.
- Fired in: `modules/blog/inc/class-blog-sidebar.php`

## `creationell_wp_theme_blog_sidebar_terms`

Filter. Filters whether the blog sidebar shows the categories and tags.

The module post-filter switches the lists off where its filter bar takes their place. Only true counts; any other answer hides the lists.

Example:

```php
add_filter( 'creationell_wp_theme_blog_sidebar_terms', static fn( bool $show ): bool => $show && ! is_singular( 'post' ) );
```

| Parameter | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether the lists show; true by default. |

- Returns: the filtered `$show`.
- Fired in: `modules/blog/inc/class-blog-sidebar.php`

## `creationell_wp_theme_cf7_autop`

Filter. Filters whether Contact Form 7 adds paragraphs and line breaks (autop) to the form markup.

The theme switches autop off in forms, because the form templates bring their own paragraphs; mails are not affected. Values other than true or false give false and a notice.

Example, in the functions.php of a child theme:

```php
add_filter( 'creationell_wp_theme_cf7_autop', '__return_true' );
```

| Parameter | Type | Description |
|---|---|---|
| `$autop` | `bool` | Whether autop applies in forms; false by default. |

- Returns: the filtered `$autop`.
- Fired in: `modules/contact-form-7/inc/class-module.php`

## `creationell_wp_theme_cf7_classes`

Filter. Filters the classes the theme adds to the form elements of Contact Form 7.

Keys are the roles control (text-like inputs and text areas), select, range, submit, choice (list item of a checkbox, radio or acceptance field), choice_input and choice_label; values are lists of class names. Missing roles keep their defaults, an empty list adds no class. The first submit class is always added, the others only when the submit has no class starting with "btn-" (CF7 option class:btn-outline-secondary). Invalid entries fall back to their defaults and give a notice.

Example, in the functions.php of a child theme:

```php
add_filter(
    'creationell_wp_theme_cf7_classes',
    static fn( array $classes ): array => array_merge( $classes, array( 'submit' => array( 'btn', 'btn-dark' ) ) )
);
```

| Parameter | Type | Description |
|---|---|---|
| `$classes` | `array<string, array<int, string>>` | Classes per role; Form_Markup::DEFAULT_CLASSES by default. |

- Returns: the filtered `$classes`.
- Fired in: `modules/contact-form-7/inc/class-form-markup.php`

## `creationell_wp_theme_cf7_inline_choices`

Filter. Filters whether the choices of checkbox and radio fields stand side by side.

True adds form-check-inline to every choice. The theme stacks them by default: a list is easier to read and to hit. Values other than true or false give false and a notice.

Example, in the functions.php of a child theme:

```php
add_filter( 'creationell_wp_theme_cf7_inline_choices', '__return_true' );
```

| Parameter | Type | Description |
|---|---|---|
| `$inline` | `bool` | Whether the choices stand side by side; false by default. |

- Returns: the filtered `$inline`.
- Fired in: `modules/contact-form-7/inc/class-form-markup.php`

## `creationell_wp_theme_consent_category_details`

Filter. Filters the texts and cookies of the consent categories in the cookie settings dialog.

Keys are category names of creationell_wp_theme_consent_categories(); the categories themselves come only from that core filter. Each entry may set "title" (plain text), "description" (text with links, bold, italic and line breaks) and "cookies". Cookies are listed in the dialog; for a category other than "necessary" they are also deleted when the visitor withdraws the consent. A name with "is_regex" true is a regular expression without delimiters. A cookie may also set "service" (shown in a service column), "first_party" (false: set by another domain, so not deleted) and "domain" (deleted on that domain when the site lies in it). A cookie name of a backend service wins over the same name here, in every category.

Example, in the functions.php of a child theme:

```php
add_filter(
    'creationell_wp_theme_consent_category_details',
    static function ( array $details ): array {
        $details['analytics'] = array(
            'description' => 'Matomo counts visits without sharing data.',
            'cookies'     => array(
                array( 'name' => '^_pk_', 'is_regex' => true, 'description' => 'Matomo', 'duration' => '13 months' ),
            ),
        );
        return $details;
    }
);
```

| Parameter | Type | Description |
|---|---|---|
| `$details` | `array<string, array{title?: string, description?: string, cookies?: list<array{name: string, is_regex?: bool, description?: string, duration?: string, service?: string, first_party?: bool, domain?: string}>}>` | Details by category, empty by default. |

- Returns: the filtered `$details`.
- Fired in: `modules/consent/inc/class-texts.php`

## `creationell_wp_theme_consent_config`

Filter. Filters the configuration of CookieConsent.

Protection rules run afterwards: the root element, opt-in mode, script management, cookie deletion, the cookie name, buttons of equal weight, a non-empty "Reject optional" button, "necessary" locked on, all other categories off and only the categories of creationell_wp_theme_consent_categories() stay as the theme sets them. A change to one of them is restored with a notice.

Example, in the functions.php of a child theme:

```php
add_filter(
    'creationell_wp_theme_consent_config',
    static function ( array $config ): array {
        $config['guiOptions']['consentModal']['layout'] = 'box';
        return $config;
    }
);
```

| Parameter | Type | Description |
|---|---|---|
| `$config` | `array<string, mixed>` | Configuration of CookieConsent. |

- Returns: the filtered `$config`.
- Fired in: `modules/consent/inc/class-config-builder.php`

## `creationell_wp_theme_consent_footer_link`

Filter. Filters whether the footer shows the link that opens the cookie settings.

Switch it off when a page carries the block creationell-theme/consent-link at another place of every page. Values other than true or false keep the link and give a notice.

Example, in the functions.php of a child theme:

```php
add_filter( 'creationell_wp_theme_consent_footer_link', '__return_false' );
```

| Parameter | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether the footer shows the link; true by default. |

- Returns: the filtered `$show`.
- Fired in: `modules/consent/inc/class-links.php`

## `creationell_wp_theme_consent_services`

Filter. Filters the services of the backend before the cookie settings dialog and the withdrawal list use them.

Each service has id, category, name, provider, privacy_url, purpose (texts already resolved for the language of the request) and cookies with name, match ("exact", "prefix" or "regex"), first_party, parent_domain, purpose and duration. The result passes the same rules as the backend; a broken service falls away with a notice.

Example, in the functions.php of a child theme:

```php
add_filter(
    'creationell_wp_theme_consent_services',
    static fn( array $services ): array => array_values(
        array_filter( $services, static fn( array $service ): bool => 'google-analytics-4' !== $service['id'] )
    )
);
```

| Parameter | Type | Description |
|---|---|---|
| `$services` | `list<array{id: string, category: string, name: string, provider: string, privacy_url: string, purpose: string, cookies: list<array{name: string, match: string, first_party: bool, parent_domain: bool, purpose: string, duration: string}>}>` | Resolved services of the backend. |

- Returns: the filtered `$services`.
- Fired in: `modules/consent/inc/class-texts.php`

## `creationell_wp_theme_header_footer_denied_blocks`

Filter. Filters the blocks editors may not insert while editing header and footer in the Site Editor.

Administrators with the native capability edit_theme_options are not affected. Add a block name to hide it, remove one to offer it.

| Parameter | Type | Description |
|---|---|---|
| `$denied` | `array<int, string>` | Block names; default core/html, core/freeform, core/shortcode, core/navigation, core/template-part, core/site-logo, core/site-title, core/site-tagline. |

- Returns: the filtered `$denied`.
- Fired in: `modules/header-footer/inc/class-editor-restrictions.php`

## `creationell_wp_theme_hide_cbb_post_grid`

Filter. Filters whether the block creabb/post-grid of CreaBootstrapBlocks leaves the inserter while the module post-lists is active.

Existing post grids keep rendering either way. Only false keeps the block in the inserter.

Example:

```php
add_filter( 'creationell_wp_theme_hide_cbb_post_grid', '__return_false' );
```

| Parameter | Type | Description |
|---|---|---|
| `$hide` | `bool` | True to hide the block. |

- Returns: the filtered `$hide`.
- Fired in: `modules/post-lists/inc/class-post-lists-module.php`

## `creationell_wp_theme_logo`

Filter. Filters the URL of the logo.

| Parameter | Type | Description |
|---|---|---|
| `$url` | `string` | Logo URL. |
| `$variant` | `string` | Logo variant, default or theme-dark. |

- Returns: the filtered `$url`.
- Fired in: `modules/header-footer/inc/class-header-blocks.php`, `template-parts/header/navbar.php`

## `creationell_wp_theme_logo_height`

Filter. Filters the height attribute of the logo.

| Parameter | Type | Description |
|---|---|---|
| `$height` | `string` | Height in pixels. |

- Returns: the filtered `$height`.
- Fired in: `modules/header-footer/inc/class-header-blocks.php`, `template-parts/header/navbar.php`

## `creationell_wp_theme_logo_width`

Filter. Filters the width attribute of the logo.

| Parameter | Type | Description |
|---|---|---|
| `$width` | `string` | Width in pixels. |

- Returns: the filtered `$width`.
- Fired in: `modules/header-footer/inc/class-header-blocks.php`, `template-parts/header/navbar.php`

## `creationell_wp_theme_related_posts_auto_insert`

Filter. Filters whether the related posts show below a single post by themselves.

Runs only while the module related-posts is active, on a singular request for a post type of the setting related_posts_post_types whose content holds no related posts block. Only true keeps the output.

Example:

```php
add_filter(
    'creationell_wp_theme_related_posts_auto_insert',
    static fn( bool $insert, int $post_id ): bool => $insert && ! has_term( 'press', 'category', $post_id ),
    10,
    2
);
```

| Parameter | Type | Description |
|---|---|---|
| `$insert` | `bool` | True to print the related posts. |
| `$post_id` | `int` | Post ID. |

- Returns: the filtered `$insert`.
- Fired in: `modules/related-posts/inc/class-related-posts-module.php`

## `creationell_wp_theme_share_protected`

Filter. Filters whether a post with a password gets the own share links.

A post counts as protected while the visitor has to enter its password and also after the visitor entered it, as with Shariff. Asked only for the own links; with Shariff its setting "Disable the Shariff buttons on password protected posts" decides. Only true shares the post.

| Parameter | Type | Description |
|---|---|---|
| `$share` | `bool` | False: a post with a password gets no own links. |
| `$post` | `WP_Post` | Post with a password. |

- Returns: the filtered `$share`.
- Fired in: `modules/blog/inc/class-share-links.php`

## `creationell_wp_theme_shariff_atts`

Filter. Filters the attributes the theme passes to Shariff for the share buttons below a post.

The theme passes the permalink and the plain title explicitly, no headline (the section has its heading) and the theme wcag; all other attributes come from the settings of Shariff. Any answer other than an array gives the defaults.

| Parameter | Type | Description |
|---|---|---|
| `$atts` | `array<string, string>` | Attributes of shariff3uu_render(): url, title, headline and theme. |
| `$post` | `WP_Post` | Post that is shared. |

- Returns: the filtered `$atts`.
- Fired in: `modules/blog/inc/class-share-links.php`
