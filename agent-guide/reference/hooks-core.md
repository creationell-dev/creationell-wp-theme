<!-- Generated from the source. Do not edit. -->

# Hooks: core

Hooks whose first path lies in `src/`.

## `creationell_wp_theme_before_module_switch`

Action. Fires before a module switch is written.

A backup of the settings, for example, is made here. The state is written after the callbacks return, whatever they do.

| Parameter | Type | Description |
|---|---|---|
| `$slug` | `string` | Module slug. |
| `$from` | `string` | Configured state before the switch. |
| `$to` | `string` | Target state. |
| `$channel` | `string` | Channel: admin, cli or import. |

- Fired in: `src/modules/class-module-switcher.php`

## `creationell_wp_theme_blog_url`

Filter. Filters the URL of the blog page, used by the link back to the blog.

Example:

```php
add_filter(
    'creationell_wp_theme_blog_url',
    static fn( string $url ): string => home_url( '/news/' )
);
```

| Parameter | Type | Description |
|---|---|---|
| `$url` | `string` | URL of the posts page, the home URL with posts on the front page, or an empty string for none. |

- Returns: the filtered `$url`.
- Fired in: `src/blog/class-blog-url.php`

## `creationell_wp_theme_capabilities`

Filter. Filters who may use the areas of the theme; the only filter for capabilities.

"unlock" opens an area of the agency to editors (edit_pages): design, modules, import_export, menus, consent. "revoke" takes an area of the editors back to the agency (manage_options): basic, template_parts. "restricted_admins" lists user logins of administrators who may use only the unlocked areas; they never switch the Bootstrap line. These values are defaults: the agency may change them on the rights page of the theme settings. "locked" lists the entries (areas or "restricted_admins") that keep the value of the child theme. The Bootstrap line is never unlocked. The filter changes no role and writes nothing to the database; an import never changes rights.

Example, in the functions.php of a child theme:

```php
add_filter(
    'creationell_wp_theme_capabilities',
    static function ( array $caps ): array {
        $caps['unlock']['design']    = true;
        $caps['restricted_admins'][] = 'customer-admin';
        $caps['locked'][]            = 'design';
        return $caps;
    }
);
```

| Parameter | Type | Description |
|---|---|---|
| `$caps` | `array{unlock: array<string, bool>, revoke: array<string, bool>, restricted_admins: array<int, string>, locked: array<int, string>}` | Defaults: nothing unlocked, nothing revoked, no restricted administrator, nothing locked. |

- Returns: the filtered `$caps`.
- Fired in: `src/core/class-capabilities.php`

## `creationell_wp_theme_consent_categories`

Filter. Filters the consent categories.

Values that are no category name fall away; "necessary" is always added. A value other than an array keeps the defaults.

| Parameter | Type | Description |
|---|---|---|
| `$categories` | `array<int, string>` | Categories, by default necessary, functional, analytics, marketing. |

- Returns: the filtered `$categories`.
- Fired in: `src/consent/class-consent-categories.php`

## `creationell_wp_theme_consent_provider`

Filter. Filters the consent provider.

The consent module returns its provider here when the value is still null, so a provider of another plugin stays.

| Parameter | Type | Description |
|---|---|---|
| `$provider` | `Consent_Provider_Interface\|null` | Provider, null by default. |

- Returns: the filtered `$provider`.
- Fired in: `src/consent/class-consent-gate.php`

## `creationell_wp_theme_consent_service_presets`

Filter. Filters the consent service presets.

| Parameter | Type | Description |
|---|---|---|
| `$presets` | `array<string, array<string, mixed>>` | Presets by ID. |

- Returns: the filtered `$presets`.
- Fired in: `src/consent/class-consent-service-catalog.php`

## `creationell_wp_theme_consent_services_saved`

Action. Fires after the consent services were saved.

| Parameter | Type | Description |
|---|---|---|
| `$clean` | `array<string, mixed>` | Stored data. |
| `$context` | `Write_Context` | Write context. |

- Fired in: `src/consent/class-consent-services-store.php`

## `creationell_wp_theme_content_width`

Filter. Filters the content width in pixels.

| Parameter | Type | Description |
|---|---|---|
| `$width` | `int` | Content width in pixels. |

- Returns: the filtered `$width`.
- Fired in: `src/core/class-theme-support.php`

## `creationell_wp_theme_css_built`

Action. Fires after a build of the individual stylesheet wrote its state.

The status is "ok", "package" (the package stylesheet applies) or "failed" (the previous files stay active, "error" says why).

| Parameter | Type | Description |
|---|---|---|
| `$state` | `array<string, mixed>` | State as stored in the option creationell_wp_theme_css_state. |

- Fired in: `src/compiler/class-custom-stylesheet.php`

## `creationell_wp_theme_disable_unsupported_blocks`

Filter. Filters whether blocks and patterns without theme styles are disabled.

| Parameter | Type | Description |
|---|---|---|
| `$disable` | `bool` | Whether to disable them. |

- Returns: the filtered `$disable`.
- Fired in: `src/loader.php`

## `creationell_wp_theme_font_catalog`

Filter. Filters the fonts a child theme adds to the font catalog.

Each entry needs a slug (lower case letters, digits, hyphens) as key, a name, a CSS font stack and the scripts it covers (latin, latin-ext, vietnamese, cyrillic, arabic, cjk). Font files are optional and must lie in assets/fonts/ of the child theme as .woff2; the core prints the font-face rules for them. Invalid entries are dropped and reported.

Example:

```php
add_filter(
    'creationell_wp_theme_font_catalog',
    static function ( mixed $fonts ): mixed {
        $fonts = is_array( $fonts ) ? $fonts : array();
        $fonts['brand-sans'] = array(
            'name'    => 'Brand Sans',
            'stack'   => '"Brand Sans", Arial, sans-serif',
            'scripts' => array( 'latin', 'latin-ext' ),
            'faces'   => array(
                array( 'src' => 'file:./assets/fonts/brand-sans.woff2', 'weight' => '400', 'style' => 'normal' ),
            ),
        );
        return $fonts;
    }
);
```

| Parameter | Type | Description |
|---|---|---|
| `$fonts` | `array<string, array{name: string, stack: string, scripts: array<int, string>, faces?: array<int, array{src: string, weight: string, style: string, unicode_range?: string}>}>` | Child fonts by slug; empty in the parent theme. |

- Returns: the filtered `$fonts`.
- Fired in: `src/settings/class-font-catalog.php`

## `creationell_wp_theme_import_line_map`

Filter. Filters how the keys of a file from another Bootstrap line are imported.

A key maps to the key of this site that takes its value, or to false when this line has no counterpart ("line_unmapped"). Keys that are not in the map are imported under their own name. The Bootstrap line itself is never imported.

Example:

```php
add_filter(
    'creationell_wp_theme_import_line_map',
    static function ( mixed $map, int $from, int $to ): mixed {
        $map = is_array( $map ) ? $map : array();
        if ( 6 === $from && 5 === $to ) {
            $map['color_tertiary'] = false;
        }
        return $map;
    },
    10,
    3
);
```

| Parameter | Type | Description |
|---|---|---|
| `$map` | `array<string, string\|false>` | Map from file key to key of this site or false; empty: every key keeps its name. |
| `$from` | `int` | Bootstrap line of the file. |
| `$to` | `int` | Bootstrap line of this site. |

- Returns: the filtered `$map`.
- Fired in: `src/settings/transfer/class-settings-section.php`

## `creationell_wp_theme_is_blog_view`

Filter. Filters whether the request is a blog view.

Only true counts; any other answer gives false.

Example:

```php
add_filter(
    'creationell_wp_theme_is_blog_view',
    static fn( bool $is_blog_view ): bool => $is_blog_view || is_post_type_archive( 'podcast' )
);
```

| Parameter | Type | Description |
|---|---|---|
| `$is_blog_view` | `bool` | True for the blog page, a single post, a category, tag, date or post author archive; false for search results. |

- Returns: the filtered `$is_blog_view`.
- Fired in: `src/blog/class-blog-context.php`

## `creationell_wp_theme_language_switcher_args`

Filter. Filters the arguments of a language switcher.

| Parameter | Type | Description |
|---|---|---|
| `$args` | `array` | Arguments: enabled (bool), variant ("dropdown" or "list"), display ("code", "native", or null for the default of the variant), context ("navbar", "footer" or "block"). |
| `$context` | `string` | Place of the switcher, the same as $args['context']. |

- Returns: the filtered `$args`.
- Fired in: `src/wpml/class-language-switcher.php`

## `creationell_wp_theme_language_switcher_languages`

Filter. Filters the languages of the language switcher.

Each entry has the keys code, name (native name), locale (WordPress locale), bcp47 (tag for lang and hreflang), url, current (bool) and missing (bool, true when the page has no translation and the URL leads to the home page of the language). Entries without code, name or a valid URL are dropped after the filter.

| Parameter | Type | Description |
|---|---|---|
| `$languages` | `array` | Languages in the order of the WPML settings. |

- Returns: the filtered `$languages`.
- Fired in: `src/wpml/class-language-switcher.php`

## `creationell_wp_theme_load_icon_font`

Filter. Filters whether the theme loads the Bootstrap Icons font.

Only the boolean true switches the font on.

Example: add_filter( 'creationell_wp_theme_load_icon_font', '__return_true' );

| Parameter | Type | Description |
|---|---|---|
| `$load` | `bool` | True when CreaBootstrapBlocks is active. |

- Returns: the filtered `$load`.
- Fired in: `src/assets/class-assets.php`

## `creationell_wp_theme_loaded`

Action. Fires after the theme core has registered its hooks, while functions.php of the parent theme loads.

A child theme hooks in from its own functions.php, which WordPress loads before the one of the parent theme.

No parameters.

- Fired in: `src/core/class-theme.php`

## `creationell_wp_theme_module_backup_available`

Filter. Filters whether a backup of the settings is available before a module goes off.

Without one, switching a module off warns that nothing backs up its settings; the backup of the settings import returns true.

| Parameter | Type | Description |
|---|---|---|
| `$available` | `bool` | Whether a backup is available; false in the theme. |

- Returns: the filtered `$available`.
- Fired in: `src/modules/class-module-switcher.php`

## `creationell_wp_theme_module_state_changed`

Action. Fires after a module switch was written.

The new state takes effect with the next request.

| Parameter | Type | Description |
|---|---|---|
| `$slug` | `string` | Module slug. |
| `$from` | `string` | Configured state before the switch. |
| `$to` | `string` | Configured state after the switch. |
| `$channel` | `string` | Channel: admin, cli or import. |

- Fired in: `src/modules/class-module-switcher.php`

## `creationell_wp_theme_modules`

Filter. Filters the child layers of the modules: a default and a lock per module slug.

A child theme sets a default state, which a site constant overrides, or locks a state. Values outside the states of a module switch it off.

Example, in the functions.php of a child theme:

```php
add_filter(
    'creationell_wp_theme_modules',
    static function ( mixed $modules ): mixed {
        $modules = is_array( $modules ) ? $modules : array();
        $modules['header-footer'] = array( 'default' => 'active' );
        return $modules;
    }
);
```

| Parameter | Type | Description |
|---|---|---|
| `$modules` | `array<string, array{default?: string, locked?: string}>` | Layers by module slug; empty in the parent theme. |

- Returns: the filtered `$modules`.
- Fired in: `src/modules/class-module-state.php`

## `creationell_wp_theme_post_card_classes`

Filter. Filters the CSS classes of the elements of a post card of the post lists, the slider and the related posts.

Example:

```php
add_filter(
    'creationell_wp_theme_post_card_classes',
    static function ( array $classes, string $part ): array {
        if ( 'card-grid' === $part ) {
            $classes['card'] .= ' shadow-sm';
        }
        return $classes;
    },
    10,
    2
);
```

| Parameter | Type | Description |
|---|---|---|
| `$classes` | `array<string, string>` | Space-separated classes by element: card, image, body, badges, title, link, meta, read_more, tags and more per template. |
| `$part` | `string` | Card template: card-grid, card-list or card-hero. |
| `$context` | `string` | Block context: post-list, post-slider or related-posts; the accordion and the tabs of post-accordion show panels without cards. |

- Returns: the filtered `$classes`.
- Fired in: `src/posts/class-display-options.php`

## `creationell_wp_theme_post_query_args`

Filter. Filters the WP_Query arguments of a post list, accordion, slider or of the related posts.

Example:

```php
add_filter(
    'creationell_wp_theme_post_query_args',
    static function ( array $args, string $context ): array {
        if ( 'post-slider' === $context ) {
            $args['orderby'] = 'rand';
        }
        return $args;
    },
    10,
    2
);
```

| Parameter | Type | Description |
|---|---|---|
| `$args` | `array<string, mixed>` | WP_Query arguments. |
| `$context` | `string` | post-list, post-accordion, post-slider or related-posts. |
| `$attributes` | `array<string, mixed>` | Block attributes; for related-posts the settings of the module. |

- Returns: the filtered `$args`.
- Fired in: `src/posts/class-query-args.php`

## `creationell_wp_theme_reading_time_wpm`

Filter. Filters the words per minute of the reading time of a post.

The value is kept between 100 and 1000; an answer that is no number keeps the value passed in.

Example:

```php
add_filter(
    'creationell_wp_theme_reading_time_wpm',
    static fn( int $wpm, WP_Post $post ): int => has_category( 'tutorials', $post ) ? 150 : $wpm,
    10,
    2
);
```

| Parameter | Type | Description |
|---|---|---|
| `$wpm` | `int` | Words per minute. |
| `$post` | `WP_Post` | Post. |

- Returns: the filtered `$wpm`.
- Fired in: `src/blog/class-reading-time.php`

## `creationell_wp_theme_settings`

Filter. Filters the child defaults and locks of the theme settings.

A child theme sets a default ("default") or locks a value ("locked") per key. A default counts until an editor saves another value in the backend; a lock always wins, only a site constant is stronger. Values must be valid for the setting (e.g. colors as "#rrggbb" in lower case); invalid ones are ignored and reported. Module switches use creationell_wp_theme_modules.

Example:

```php
add_filter(
    'creationell_wp_theme_settings',
    static function ( mixed $settings ): mixed {
        $settings = is_array( $settings ) ? $settings : array();
        $settings['color_primary'] = array( 'default' => '#6f2da8' );
        $settings['font_body']     = array( 'locked' => 'system-serif' );
        return $settings;
    }
);
```

| Parameter | Type | Description |
|---|---|---|
| `$settings` | `array<string, array{default?: mixed, locked?: mixed}>` | Layers by setting key; empty in the parent theme. |

- Returns: the filtered `$settings`.
- Fired in: `src/settings/class-settings.php`

## `creationell_wp_theme_settings_imported`

Action. Fires after an import or a restore wrote settings or module states.

Dry runs and imports without anything to write do not fire it.

| Parameter | Type | Description |
|---|---|---|
| `$report` | `Import_Report` | Report with the backup ID and every row. |

- Fired in: `src/settings/transfer/class-importer.php`

## `creationell_wp_theme_settings_saved`

Action. Fires after the setter wrote settings and rebuilt the snapshots.

| Parameter | Type | Description |
|---|---|---|
| `$keys` | `array<int, string>` | Keys written since the last commit, in the order of their first write. |
| `$ctx` | `Write_Context` | Context of the last write. |

- Fired in: `src/settings/class-setter.php`

## `creationell_wp_theme_snapshot_rebuilt`

Action. Fires after a rebuild changed snapshots of the settings.

Readers that cache setting values drop their caches here.

| Parameter | Type | Description |
|---|---|---|
| `$langs` | `array<int, string>` | Codes of the changed languages. |

- Fired in: `src/settings/class-snapshot.php`

## `creationell_wp_theme_swiper_options`

Filter. Filters the Swiper options of a post slider before they are printed as JSON.

Example:

```php
add_filter(
    'creationell_wp_theme_swiper_options',
    static function ( array $options ): array {
        $options['speed'] = 600;
        return $options;
    }
);
```

| Parameter | Type | Description |
|---|---|---|
| `$options` | `array<string, mixed>` | Swiper options and the labels of the pause button. |
| `$context` | `string` | post-slider or related-posts. |

- Returns: the filtered `$options`.
- Fired in: `src/posts/class-slider-config.php`

## `creationell_wp_theme_template_part_classes`

Filter. Filters the CSS classes of the wrappers of the template parts header and footer.

| Parameter | Type | Description |
|---|---|---|
| `$classes` | `array<string, string>` | Space-separated classes by part slug, header and footer. |

- Returns: the filtered `$classes`.
- Fired in: `src/core/class-template-part-gate.php`
