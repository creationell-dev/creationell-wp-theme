<!-- Generated from the source. Do not edit. -->

# Settings

Every theme setting with its type, default and where it comes from. Read a value with `wp creationell-theme settings get <key>`, change it with `settings set` (see [cli.md](cli.md)); a module switch follows [modules.md](modules.md). A constant of the listed name fixes the value on a site.

## system

### `bootstrap_line`

Bootstrap line. Major Bootstrap version the theme styles and scripts are built on.

- Type `enum`, default `5`
- Allowed values: `5`, `6` (not available)
- Section `system`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_switch_bootstrap_line`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BOOTSTRAP_LINE`

## modules

### `module_post_lists`

Post lists.

- Type `enum`, default `"off"`
- Allowed values: `"active"`, `"hidden"`, `"off"`
- Section `modules`, level `advanced`
- Module: [`post-lists`](modules.md)
- Capability: `creationell_wp_theme_manage_modules`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_MODULE_POST_LISTS`

### `module_post_slider`

Post slider.

- Type `enum`, default `"off"`
- Allowed values: `"active"`, `"hidden"`, `"off"`
- Section `modules`, level `advanced`
- Module: [`post-slider`](modules.md)
- Capability: `creationell_wp_theme_manage_modules`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_MODULE_POST_SLIDER`

### `module_related_posts`

Related posts.

- Type `enum`, default `"off"`
- Allowed values: `"active"`, `"hidden"`, `"off"`
- Section `modules`, level `advanced`
- Module: [`related-posts`](modules.md)
- Capability: `creationell_wp_theme_manage_modules`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_MODULE_RELATED_POSTS`

### `module_contact_form_7`

Contact Form 7.

- Type `enum`, default `"off"`
- Allowed values: `"active"`, `"off"`
- Section `modules`, level `advanced`
- Module: [`contact-form-7`](modules.md)
- Capability: `creationell_wp_theme_manage_modules`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_MODULE_CONTACT_FORM_7`

### `module_consent`

Consent.

- Type `enum`, default `"off"`
- Allowed values: `"active"`, `"off"`
- Section `modules`, level `advanced`
- Module: [`consent`](modules.md)
- Capability: `creationell_wp_theme_manage_modules`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_MODULE_CONSENT`

### `module_header_footer`

Header and footer.

- Type `enum`, default `"off"`
- Allowed values: `"active"`, `"off"`
- Section `modules`, level `advanced`
- Module: [`header-footer`](modules.md)
- Capability: `creationell_wp_theme_manage_modules`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_MODULE_HEADER_FOOTER`

### `module_woocommerce`

WooCommerce.

- Type `enum`, default `"off"`
- Allowed values: `"active"`, `"off"`
- Section `modules`, level `advanced`
- Module: `woocommerce` (reserved, no module in this package)
- Capability: `creationell_wp_theme_manage_modules`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_MODULE_WOOCOMMERCE`

### `module_blog`

Blog.

- Type `enum`, default `"off"`
- Allowed values: `"active"`, `"off"`
- Section `modules`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_modules`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_MODULE_BLOG`

### `module_post_filter`

Post filter.

- Type `enum`, default `"off"`
- Allowed values: `"active"`, `"off"`
- Section `modules`, level `advanced`
- Module: [`post-filter`](modules.md)
- Capability: `creationell_wp_theme_manage_modules`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_MODULE_POST_FILTER`

## design

### `color_primary`

Primary color. Buttons, links and highlights.

- Type `color`, default `"#0b5ed7"`
- Section `design`, group `colors`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_COLOR_PRIMARY`

### `color_secondary`

Secondary color.

- Type `color`, default `"#5c636a"`
- Section `design`, group `colors`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_COLOR_SECONDARY`

### `color_success`

Success color.

- Type `color`, default `"#198754"`
- Section `design`, group `colors`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_COLOR_SUCCESS`

### `color_info`

Info color.

- Type `color`, default `"#0dcaf0"`
- Section `design`, group `colors`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_COLOR_INFO`

### `color_warning`

Warning color.

- Type `color`, default `"#ffc107"`
- Section `design`, group `colors`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_COLOR_WARNING`

### `color_danger`

Danger color.

- Type `color`, default `"#dc3545"`
- Section `design`, group `colors`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_COLOR_DANGER`

### `color_light`

Light color.

- Type `color`, default `"#f8f9fa"`
- Section `design`, group `colors`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_COLOR_LIGHT`

### `color_dark`

Dark color.

- Type `color`, default `"#212529"`
- Section `design`, group `colors`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_COLOR_DARK`

### `color_body_text`

Text color.

- Type `color`, default `"#212529"`
- Section `design`, group `colors`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_COLOR_BODY_TEXT`

### `color_body_bg`

Background color.

- Type `color`, default `"#ffffff"`
- Section `design`, group `colors`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_COLOR_BODY_BG`

### `font_body`

Body font.

- Type `font`, default `"system-sans"`
- Section `design`, group `fonts`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_FONT_BODY`

### `font_headings`

Headings font. Without an own font the headings use the body font.

- Type `font`, default `"inherit"`
- Section `design`, group `fonts`, level `advanced`
- Module: theme core
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_FONT_HEADINGS`

### `blog_sidebar_terms`

Term lists in the blog sidebar. Categories and tags, only one of them, or none.

- Type `enum`, default `"both"`
- Allowed values: `"both"`, `"categories"`, `"tags"`, `"off"`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_SIDEBAR_TERMS`

### `blog_sidebar_counts`

Number of posts in the term lists. Shows how many posts each category and tag has.

- Type `bool`, default `true`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_SIDEBAR_COUNTS`

### `blog_sidebar_mobile`

Blog sidebar on small screens. Below the content, or in the offcanvas panel as on other pages.

- Type `enum`, default `"below"`
- Allowed values: `"below"`, `"offcanvas"`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_SIDEBAR_MOBILE`

### `blog_category_badge`

Category badge above the post title. Shows the category of a single post above its title.

- Type `bool`, default `true`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_CATEGORY_BADGE`

### `blog_meta_date`

Date in the meta line. Shows the date of a post below its title.

- Type `bool`, default `true`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_META_DATE`

### `blog_meta_reading_time`

Reading time in the meta line. Shows how many minutes a post takes to read.

- Type `bool`, default `true`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_META_READING_TIME`

### `blog_meta_author`

Author in the meta line. Shows the author of a post below its title.

- Type `bool`, default `true`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_META_AUTHOR`

### `blog_meta_comments`

Comments in the meta line. Shows the number of comments with a link to them, while comments are open or there are some.

- Type `bool`, default `true`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_META_COMMENTS`

### `blog_reading_wpm`

Words per minute. Reading speed for the reading time, from 100 to 400.

- Type `int`, default `200`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_READING_WPM`

### `blog_back_link`

Back link to the blog. Shows a link back to the blog page above a single post.

- Type `bool`, default `true`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_BACK_LINK`

### `blog_entry_terms`

Terms below a post. Categories and tags at the end of a single post, only one of them, or none.

- Type `enum`, default `"both"`
- Allowed values: `"both"`, `"categories"`, `"tags"`, `"off"`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_ENTRY_TERMS`

### `blog_share`

Share links. Automatic uses the plugin Shariff while it is active, otherwise the own links of the theme.

- Type `enum`, default `"auto"`
- Allowed values: `"auto"`, `"shariff"`, `"links"`, `"off"`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_SHARE`

### `blog_share_networks`

Networks of the own share links. Names separated by commas, e.g. linkedin,email,copy. Available: linkedin, facebook, whatsapp, email, copy, x, bluesky and threads.

- Type `string`, default `"linkedin,facebook,whatsapp,email,copy"`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Longest value: 200 characters
- Constant: `CREATIONELL_WP_THEME_BLOG_SHARE_NETWORKS`

### `blog_404_posts`

Latest posts on the 404 page. From 0 to 6; 0 shows no posts.

- Type `int`, default `3`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_404_POSTS`

### `blog_404_search`

Search form on the 404 page. Shown only while the widget area of the 404 page holds no search.

- Type `bool`, default `true`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_404_SEARCH`

### `blog_author_pages`

Author pages. Public pages for authors with posts. While this is off, author addresses show the 404 page.

- Type `bool`, default `false`
- Section `design`, group `blog`, level `advanced`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_BLOG_AUTHOR_PAGES`

### `consent_revision`

Consent revision. Raise the number after a change of the cookies: every visitor is asked again. From 0 to 9999.

- Type `int`, default `0`
- Section `design`, group `consent`, level `advanced`
- Module: [`consent`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_CONSENT_REVISION`

### `consent_cookie_days`

Consent lifetime in days. How long the choice of a visitor is kept, from 30 to 395 days.

- Type `int`, default `182`
- Section `design`, group `consent`, level `advanced`
- Module: [`consent`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_CONSENT_COOKIE_DAYS`

### `post_lists_max_posts`

Most posts per list. Upper limit for the number of posts of a post list, an accordion or a slider, from 1 to 50.

- Type `int`, default `24`
- Section `design`, group `post-lists`, level `advanced`
- Module: [`post-lists`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_POST_LISTS_MAX_POSTS`

### `post_slider_autoplay_delay`

Slideshow delay in milliseconds. Time each slide stays while a post slider plays by itself, from 3000 to 15000 milliseconds.

- Type `int`, default `6000`
- Section `design`, group `post-slider`, level `advanced`
- Module: [`post-slider`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_POST_SLIDER_AUTOPLAY_DELAY`

### `related_posts_post_types`

Post types with related posts. Names of the public post types, separated by commas, e.g. post,page. Media are never included.

- Type `string`, default `"post"`
- Section `design`, group `related-posts`, level `advanced`
- Module: [`related-posts`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Longest value: 200 characters
- Constant: `CREATIONELL_WP_THEME_RELATED_POSTS_POST_TYPES`

### `related_posts_taxonomies`

Related by. Posts are related when they share a category, a tag or either of both.

- Type `enum`, default `"category"`
- Allowed values: `"category"`, `"post_tag"`, `"both"`
- Section `design`, group `related-posts`, level `advanced`
- Module: [`related-posts`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_RELATED_POSTS_TAXONOMIES`

### `related_posts_count`

Number of related posts. From 1 to 12.

- Type `int`, default `3`
- Section `design`, group `related-posts`, level `advanced`
- Module: [`related-posts`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_RELATED_POSTS_COUNT`

### `related_posts_display`

Display of related posts. Grid or slider; the slider needs the module post slider and shows a grid while it is off.

- Type `enum`, default `"grid"`
- Allowed values: `"grid"`, `"slider"`
- Section `design`, group `related-posts`, level `advanced`
- Module: [`related-posts`](modules.md)
- Capability: `creationell_wp_theme_manage_design`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_RELATED_POSTS_DISPLAY`

## texts

### `footer_text`

Footer text. Links, bold, italic and line breaks are allowed. Empty shows the copyright line.

- Type `html_inline`, default `""`
- Section `texts`, group `footer`, level `basic`
- Module: theme core
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: yes
- Longest value: 500 characters
- Constant: `CREATIONELL_WP_THEME_FOOTER_TEXT`

### `contact_address`

Address.

- Type `text`, default `""`
- Section `texts`, group `contact`, level `basic`
- Module: theme core
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: yes
- Longest value: 300 characters
- Constant: `CREATIONELL_WP_THEME_CONTACT_ADDRESS`

### `contact_phone`

Phone.

- Type `tel`, default `""`
- Section `texts`, group `contact`, level `basic`
- Module: theme core
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: no
- Longest value: 50 characters
- Constant: `CREATIONELL_WP_THEME_CONTACT_PHONE`

### `contact_email`

Email.

- Type `email`, default `""`
- Section `texts`, group `contact`, level `basic`
- Module: theme core
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: no
- Longest value: 254 characters
- Constant: `CREATIONELL_WP_THEME_CONTACT_EMAIL`

### `social_facebook`

Facebook.

- Type `url`, default `""`
- Section `texts`, group `social`, level `basic`
- Module: theme core
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: no
- Longest value: 2048 characters
- Constant: `CREATIONELL_WP_THEME_SOCIAL_FACEBOOK`

### `social_instagram`

Instagram.

- Type `url`, default `""`
- Section `texts`, group `social`, level `basic`
- Module: theme core
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: no
- Longest value: 2048 characters
- Constant: `CREATIONELL_WP_THEME_SOCIAL_INSTAGRAM`

### `social_linkedin`

LinkedIn.

- Type `url`, default `""`
- Section `texts`, group `social`, level `basic`
- Module: theme core
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: no
- Longest value: 2048 characters
- Constant: `CREATIONELL_WP_THEME_SOCIAL_LINKEDIN`

### `social_xing`

XING.

- Type `url`, default `""`
- Section `texts`, group `social`, level `basic`
- Module: theme core
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: no
- Longest value: 2048 characters
- Constant: `CREATIONELL_WP_THEME_SOCIAL_XING`

### `social_youtube`

YouTube.

- Type `url`, default `""`
- Section `texts`, group `social`, level `basic`
- Module: theme core
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: no
- Longest value: 2048 characters
- Constant: `CREATIONELL_WP_THEME_SOCIAL_YOUTUBE`

### `social_mastodon`

Mastodon.

- Type `url`, default `""`
- Section `texts`, group `social`, level `basic`
- Module: theme core
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: no
- Longest value: 2048 characters
- Constant: `CREATIONELL_WP_THEME_SOCIAL_MASTODON`

### `blog_label_categories`

Heading of the categories. Empty shows "Categories".

- Type `string`, default `""`
- Section `texts`, group `blog`, level `basic`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: yes
- Longest value: 120 characters
- Constant: `CREATIONELL_WP_THEME_BLOG_LABEL_CATEGORIES`

### `blog_label_tags`

Heading of the tags. Empty shows "Tags".

- Type `string`, default `""`
- Section `texts`, group `blog`, level `basic`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: yes
- Longest value: 120 characters
- Constant: `CREATIONELL_WP_THEME_BLOG_LABEL_TAGS`

### `blog_back_link_text`

Text of the back link. Empty shows "Back to {title}". {title} is the title of the blog page.

- Type `string`, default `""`
- Section `texts`, group `blog`, level `basic`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: yes
- Longest value: 120 characters
- Constant: `CREATIONELL_WP_THEME_BLOG_BACK_LINK_TEXT`

### `blog_share_title`

Heading of the share links. Empty shows "Share this post".

- Type `string`, default `""`
- Section `texts`, group `blog`, level `basic`
- Module: [`blog`](modules.md)
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: yes
- Longest value: 120 characters
- Constant: `CREATIONELL_WP_THEME_BLOG_SHARE_TITLE`

### `consent_banner_title`

Cookie banner title. Empty shows "We use cookies".

- Type `string`, default `""`
- Section `texts`, group `consent`, level `basic`
- Module: [`consent`](modules.md)
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: yes
- Longest value: 120 characters
- Constant: `CREATIONELL_WP_THEME_CONSENT_BANNER_TITLE`

### `consent_banner_text`

Cookie banner text. Links, bold, italic and line breaks are allowed. Empty shows the default text.

- Type `html_inline`, default `""`
- Section `texts`, group `consent`, level `basic`
- Module: [`consent`](modules.md)
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: yes
- Longest value: 1000 characters
- Constant: `CREATIONELL_WP_THEME_CONSENT_BANNER_TEXT`

### `consent_dialog_text`

Cookie settings introduction. First paragraph of the cookie settings dialog. Links, bold, italic and line breaks are allowed. Empty shows the default text.

- Type `html_inline`, default `""`
- Section `texts`, group `consent`, level `basic`
- Module: [`consent`](modules.md)
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: yes
- Longest value: 2000 characters
- Constant: `CREATIONELL_WP_THEME_CONSENT_DIALOG_TEXT`

### `consent_imprint_page`

Imprint page. Linked in the cookie banner after the privacy policy page. The privacy policy page comes from Settings > Privacy. The banner links both pages only when they are published. None hides the imprint link.

- Type `int`, default `0`
- Section `texts`, group `consent`, level `basic`
- Module: [`consent`](modules.md)
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: no
- Constant: `CREATIONELL_WP_THEME_CONSENT_IMPRINT_PAGE`

### `related_posts_heading`

Heading of the related posts. Empty shows "Related posts".

- Type `string`, default `""`
- Section `texts`, group `related-posts`, level `basic`
- Module: [`related-posts`](modules.md)
- Capability: `creationell_wp_theme_manage_basic`
- Translatable: yes
- Longest value: 120 characters
- Constant: `CREATIONELL_WP_THEME_RELATED_POSTS_HEADING`
