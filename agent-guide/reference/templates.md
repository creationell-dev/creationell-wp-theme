<!-- Generated from the source. Do not edit. -->

# Templates

A child theme can override each file below with a file at the same path. Hooks lists the hooks a file fires itself, Contexts the context values it passes to them. Loads and Loaded by list static calls only; parts chosen at runtime show no caller.

## Root templates

### [`404.php`](../../404.php)

The template for displaying 404 pages (not found)

- Contexts: `404`
- Loads: `footer.php`, `header.php`
- Hooks: `creationell_wp_theme_404_after_content`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_entry_title`

### [`archive.php`](../../archive.php)

The template for displaying archive pages

- Contexts: `archive`
- Loads: `footer.php`, `header.php`, `sidebar.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay.php`, `template-parts/loop/custom.php`, `template-parts/loop/loop-none.php`
- Loaded by: `author.php`
- Hooks: `creationell_wp_theme_after_loop`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_loop`, `creationell_wp_theme_before_loop_pagination`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_entry_archive_description`, `creationell_wp_theme_class_entry_title`, `creationell_wp_theme_class_loop_grid_col`, `creationell_wp_theme_class_loop_horizontal_col`, `creationell_wp_theme_class_main_col`, `creationell_wp_theme_loop_layout`, `creationell_wp_theme_loop_pagination`

### [`author.php`](../../author.php)

The template for displaying the author pages

- Contexts: `archive`
- Loads: `archive.php`, `footer.php`, `header.php`, `sidebar.php`, `template-parts/blog/author-header.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay.php`, `template-parts/loop/custom.php`, `template-parts/loop/loop-none.php`
- Hooks: `creationell_wp_theme_after_loop`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_loop`, `creationell_wp_theme_before_loop_pagination`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_loop_grid_col`, `creationell_wp_theme_class_loop_horizontal_col`, `creationell_wp_theme_class_main_col`, `creationell_wp_theme_loop_layout`, `creationell_wp_theme_loop_pagination`

### [`comments.php`](../../comments.php)

The template for displaying comments

- Loaded by: `page-templates/page-full-width-image.php`, `page-templates/page-sidebar-left.php`, `page-templates/page-sidebar-none.php`, `page.php`, `single-templates/single-full-width-image.php`, `single-templates/single-sidebar-left.php`, `single-templates/single-sidebar-none.php`, `single.php`
- Hooks: `creationell_wp_theme_class_comments_closed_alert`, `creationell_wp_theme_comments_closed_text`

### [`footer.php`](../../footer.php)

The template for displaying the footer

- Contexts: `footer-columns`, `footer-info`, `footer-top`
- Loads: `parts/footer.html`, `template-parts/footer/footer-info.php`, `template-parts/footer/footer-menu.php`
- Loaded by: `404.php`, `archive.php`, `author.php`, `home.php`, `index.php`, `page-templates/page-blank.php`, `page-templates/page-full-width-image.php`, `page-templates/page-sidebar-left.php`, `page-templates/page-sidebar-none.php`, `page.php`, `search.php`, `single-templates/single-full-width-image.php`, `single-templates/single-sidebar-left.php`, `single-templates/single-sidebar-none.php`, `single.php`
- Hooks: `creationell_wp_theme_before_footer`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_footer_col`, `creationell_wp_theme_class_footer_columns`, `creationell_wp_theme_class_footer_info`, `creationell_wp_theme_class_footer_to_top_button`, `creationell_wp_theme_class_footer_top`, `creationell_wp_theme_footer_columns_after_container`, `creationell_wp_theme_footer_columns_after_container_open`, `creationell_wp_theme_footer_columns_before_container`, `creationell_wp_theme_footer_columns_before_container_close`, `creationell_wp_theme_footer_columns_before_footer_menu`, `creationell_wp_theme_footer_info_after_container_open`

### [`header.php`](../../header.php)

The header for our theme

- Loads: `parts/header.html`, `template-parts/header/navbar.php`
- Loaded by: `404.php`, `archive.php`, `author.php`, `home.php`, `index.php`, `page-templates/page-blank.php`, `page-templates/page-full-width-image.php`, `page-templates/page-sidebar-left.php`, `page-templates/page-sidebar-none.php`, `page.php`, `search.php`, `single-templates/single-full-width-image.php`, `single-templates/single-sidebar-left.php`, `single-templates/single-sidebar-none.php`, `single.php`
- Hooks: `creationell_wp_theme_after_masthead`, `creationell_wp_theme_after_masthead_open`, `creationell_wp_theme_before_masthead`, `creationell_wp_theme_before_masthead_close`, `creationell_wp_theme_class_header`

### [`home.php`](../../home.php)

The template for displaying the blog page

- Contexts: `index`
- Loads: `footer.php`, `header.php`, `sidebar.php`, `template-parts/blog/home-header.php`, `template-parts/blog/home-no-match.php`, `template-parts/blog/home-none.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay.php`, `template-parts/loop/custom.php`
- Hooks: `creationell_wp_theme_after_loop`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_loop`, `creationell_wp_theme_before_loop_pagination`, `creationell_wp_theme_before_title`, `creationell_wp_theme_blog_home_empty`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_loop_grid_col`, `creationell_wp_theme_class_loop_horizontal_col`, `creationell_wp_theme_class_main_col`, `creationell_wp_theme_loop_layout`, `creationell_wp_theme_loop_pagination`

### [`index.php`](../../index.php)

The main template file

- Contexts: `index`
- Loads: `footer.php`, `header.php`, `sidebar.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay.php`, `template-parts/loop/custom.php`, `template-parts/loop/loop-none.php`
- Hooks: `creationell_wp_theme_after_loop`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_loop`, `creationell_wp_theme_before_loop_pagination`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_entry_title`, `creationell_wp_theme_class_loop_grid_col`, `creationell_wp_theme_class_loop_horizontal_col`, `creationell_wp_theme_class_main_col`, `creationell_wp_theme_loop_layout`, `creationell_wp_theme_loop_pagination`

### [`page.php`](../../page.php)

The template for displaying all pages

- Contexts: `page`
- Loads: `comments.php`, `footer.php`, `header.php`, `sidebar.php`
- Hooks: `creationell_wp_theme_after_featured_image`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_entry_footer`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_entry_title`, `creationell_wp_theme_class_main_col`

### [`search.php`](../../search.php)

The template for displaying search results pages

- Contexts: `search`
- Loads: `footer.php`, `header.php`, `sidebar.php`, `template-parts/loop/cards-grid.php`, `template-parts/loop/cards-horizontal.php`, `template-parts/loop/cards-overlay.php`, `template-parts/loop/content-none.php`, `template-parts/loop/custom.php`
- Hooks: `creationell_wp_theme_after_loop`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_loop`, `creationell_wp_theme_before_loop_pagination`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_entry_title`, `creationell_wp_theme_class_loop_grid_col`, `creationell_wp_theme_class_loop_horizontal_col`, `creationell_wp_theme_class_main_col`, `creationell_wp_theme_loop_layout`, `creationell_wp_theme_loop_pagination`

### [`searchform.php`](../../searchform.php)

Template to show classic searchform widget

- Loaded by: `modules/header-footer/inc/class-header-blocks.php`, `template-parts/blog/404-content.php`, `template-parts/blog/home-none.php`, `template-parts/loop/content-none.php`
- Hooks: `creationell_wp_theme_class_widget_search_button`

### [`sidebar.php`](../../sidebar.php)

The sidebar containing the main widget area

- Contexts: `sidebar`
- Loaded by: `archive.php`, `author.php`, `home.php`, `index.php`, `page-templates/page-full-width-image.php`, `page-templates/page-sidebar-left.php`, `page.php`, `search.php`, `single-templates/single-full-width-image.php`, `single-templates/single-sidebar-left.php`, `single.php`
- Hooks: `creationell_wp_theme_after_sidebar_widgets`, `creationell_wp_theme_before_sidebar_widgets`, `creationell_wp_theme_blog_sidebar_force`, `creationell_wp_theme_class_offcanvas_body`, `creationell_wp_theme_class_offcanvas_header`, `creationell_wp_theme_class_sidebar_button`, `creationell_wp_theme_class_sidebar_col`, `creationell_wp_theme_class_sidebar_offcanvas`, `creationell_wp_theme_offcanvas_sidebar_button_text`, `creationell_wp_theme_offcanvas_sidebar_title`, `creationell_wp_theme_sidebar_aria_label`, `creationell_wp_theme_sidebar_has_widgets`

### [`single.php`](../../single.php)

- Post types: post
- Contexts: `single`
- Loads: `comments.php`, `footer.php`, `header.php`, `sidebar.php`
- Hooks: `creationell_wp_theme_after_entry_terms`, `creationell_wp_theme_after_featured_image`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_entry_footer`, `creationell_wp_theme_before_single_pagination`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_entry_title`, `creationell_wp_theme_class_main_col`, `creationell_wp_theme_entry_header_open`

## Page templates

Folder: [`page-templates/`](../../page-templates/)

### [`page-templates/page-blank.php`](../../page-templates/page-blank.php)

- Template name: Blank
- Loads: `footer.php`, `header.php`

### [`page-templates/page-full-width-image.php`](../../page-templates/page-full-width-image.php)

- Template name: Full Width Image
- Contexts: `page-full-width-image`
- Loads: `comments.php`, `footer.php`, `header.php`, `sidebar.php`
- Hooks: `creationell_wp_theme_after_featured_image`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_entry_footer`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_entry_title`, `creationell_wp_theme_class_featured_full_width_img`, `creationell_wp_theme_class_featured_full_width_img_container`, `creationell_wp_theme_class_full_width_img_title_wrapper`, `creationell_wp_theme_class_main_col`

### [`page-templates/page-sidebar-left.php`](../../page-templates/page-sidebar-left.php)

- Template name: Left Sidebar
- Contexts: `page-sidebar-left`
- Loads: `comments.php`, `footer.php`, `header.php`, `sidebar.php`
- Hooks: `creationell_wp_theme_after_featured_image`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_entry_footer`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_entry_title`, `creationell_wp_theme_class_main_col`

### [`page-templates/page-sidebar-none.php`](../../page-templates/page-sidebar-none.php)

- Template name: No Sidebar
- Contexts: `page-sidebar-none`
- Loads: `comments.php`, `footer.php`, `header.php`
- Hooks: `creationell_wp_theme_after_featured_image`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_entry_footer`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_entry_title`

## Single templates

Folder: [`single-templates/`](../../single-templates/)

### [`single-templates/single-full-width-image.php`](../../single-templates/single-full-width-image.php)

- Template name: Full width image
- Post types: post
- Contexts: `single-full-width-image`
- Loads: `comments.php`, `footer.php`, `header.php`, `sidebar.php`
- Hooks: `creationell_wp_theme_after_entry_terms`, `creationell_wp_theme_after_featured_image`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_entry_footer`, `creationell_wp_theme_before_single_pagination`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_entry_title`, `creationell_wp_theme_class_featured_full_width_img`, `creationell_wp_theme_class_featured_full_width_img_container`, `creationell_wp_theme_class_full_width_img_title_wrapper`, `creationell_wp_theme_class_main_col`, `creationell_wp_theme_entry_header_open`

### [`single-templates/single-sidebar-left.php`](../../single-templates/single-sidebar-left.php)

- Template name: Sidebar left
- Post types: post
- Contexts: `single-sidebar-left`
- Loads: `comments.php`, `footer.php`, `header.php`, `sidebar.php`
- Hooks: `creationell_wp_theme_after_entry_terms`, `creationell_wp_theme_after_featured_image`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_entry_footer`, `creationell_wp_theme_before_single_pagination`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_entry_title`, `creationell_wp_theme_class_main_col`, `creationell_wp_theme_entry_header_open`

### [`single-templates/single-sidebar-none.php`](../../single-templates/single-sidebar-none.php)

- Template name: No Sidebar
- Post types: post
- Contexts: `single-sidebar-none`
- Loads: `comments.php`, `footer.php`, `header.php`
- Hooks: `creationell_wp_theme_after_entry_terms`, `creationell_wp_theme_after_featured_image`, `creationell_wp_theme_after_primary_open`, `creationell_wp_theme_after_title`, `creationell_wp_theme_before_entry_footer`, `creationell_wp_theme_before_single_pagination`, `creationell_wp_theme_before_title`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_content_spacer`, `creationell_wp_theme_class_entry_title`, `creationell_wp_theme_entry_header_open`

## Template parts

### [`template-parts/blog/`](../../template-parts/blog/)

#### [`template-parts/blog/404-content.php`](../../template-parts/blog/404-content.php)

Template part: search, popular sections and the latest posts on the 404 page.

- Loads: `searchform.php`
- Loaded by: `modules/blog/inc/class-not-found.php`

#### [`template-parts/blog/author-header.php`](../../template-parts/blog/author-header.php)

Template part: the header of an author page with profile image, name, job title, biography and links.

- Contexts: `archive`
- Loaded by: `author.php`
- Hooks: `creationell_wp_theme_class_entry_archive_description`, `creationell_wp_theme_class_entry_title`

#### [`template-parts/blog/back-link.php`](../../template-parts/blog/back-link.php)

Template part: the link back to the blog page in the header of a single post.

- Loaded by: `modules/blog/inc/class-entry-parts.php`

#### [`template-parts/blog/entry-meta.php`](../../template-parts/blog/entry-meta.php)

Template part: the meta line of a single post with date, reading time, author and comments.

#### [`template-parts/blog/entry-terms.php`](../../template-parts/blog/entry-terms.php)

Template part: the categories and tags below a single post.

- Hooks: `creationell_wp_theme_class_badge_category`

#### [`template-parts/blog/home-header.php`](../../template-parts/blog/home-header.php)

Template part: the header of the blog page with its title and the excerpt of the blog page.

- Contexts: `index`
- Loaded by: `home.php`
- Hooks: `creationell_wp_theme_class_entry_title`

#### [`template-parts/blog/home-no-match.php`](../../template-parts/blog/home-no-match.php)

Template part: the blog page when its posts exist but the main query is empty, for example after a filter that matches nothing.

- Loaded by: `home.php`

#### [`template-parts/blog/home-none.php`](../../template-parts/blog/home-none.php)

Template part: the blog page without posts.

- Loads: `searchform.php`
- Loaded by: `home.php`

#### [`template-parts/blog/share.php`](../../template-parts/blog/share.php)

Template part: the share section below a single post.

- Loaded by: `modules/blog/inc/class-share-links.php`

#### [`template-parts/blog/sidebar-terms.php`](../../template-parts/blog/sidebar-terms.php)

Template part: the categories and tags at the top of the blog sidebar.

- Loaded by: `modules/blog/inc/class-blog-sidebar.php`
- Hooks: `creationell_wp_theme_class_badge_tag`, `creationell_wp_theme_show_tag_icon`

### [`template-parts/footer/`](../../template-parts/footer/)

#### [`template-parts/footer/footer-info.php`](../../template-parts/footer/footer-info.php)

Template part for the footer info: the footer text of the theme settings or the copyright line.

- Loaded by: `footer.php`, `modules/header-footer/inc/class-text-blocks.php`
- Hooks: `creationell_wp_theme_footer_meta`

#### [`template-parts/footer/footer-menu.php`](../../template-parts/footer/footer-menu.php)

Template part to initialize the footer menu

- Loaded by: `footer.php`, `modules/header-footer/inc/class-header-blocks.php`

### [`template-parts/header-footer/`](../../template-parts/header-footer/)

#### [`template-parts/header-footer/contact.php`](../../template-parts/header-footer/contact.php)

Template part for the contact data of the theme settings, printed by the block creationell-theme/contact.

- Loaded by: `modules/header-footer/inc/class-text-blocks.php`

#### [`template-parts/header-footer/social-links.php`](../../template-parts/header-footer/social-links.php)

Template part for the social links of the theme settings, printed by the block creationell-theme/social-links.

- Loaded by: `modules/header-footer/inc/class-text-blocks.php`

### [`template-parts/header/`](../../template-parts/header/)

#### [`template-parts/header/actions.php`](../../template-parts/header/actions.php)

Template part for displaying the header-actions

- Loads: `template-parts/header/language-switcher.php`
- Loaded by: `template-parts/header/navbar.php`
- Hooks: `creationell_wp_theme_class_header_action_spacer`, `creationell_wp_theme_class_header_button`, `creationell_wp_theme_class_header_search_breakpoint`, `creationell_wp_theme_sidebar_has_widgets`

#### [`template-parts/header/collapse-search.php`](../../template-parts/header/collapse-search.php)

Template part for displaying the top-nav searchform collapse widget

- Contexts: `collapse-search`
- Loaded by: `template-parts/header/navbar.php`
- Hooks: `creationell_wp_theme_class_container`, `creationell_wp_theme_class_header_collapse`, `creationell_wp_theme_class_header_search_breakpoint`, `creationell_wp_theme_sidebar_has_widgets`

#### [`template-parts/header/language-switcher.php`](../../template-parts/header/language-switcher.php)

Template part for the language switcher in the header actions.

- Loaded by: `template-parts/header/actions.php`
- Hooks: `creationell_wp_theme_class_header_action_spacer`

#### [`template-parts/header/main-menu.php`](../../template-parts/header/main-menu.php)

Template part to initialize the navbar menu

- Loaded by: `template-parts/header/navbar.php`
- Hooks: `creationell_wp_theme_class_header_navbar_nav`

#### [`template-parts/header/navbar.php`](../../template-parts/header/navbar.php)

Template part for the navbar of the site header

- Contexts: `header`, `menu`
- Loads: `template-parts/header/actions.php`, `template-parts/header/collapse-search.php`, `template-parts/header/main-menu.php`
- Loaded by: `header.php`, `modules/header-footer/inc/class-header-blocks.php`
- Hooks: `creationell_wp_theme_after_nav_toggler`, `creationell_wp_theme_after_navbar_brand`, `creationell_wp_theme_before_navbar_brand`, `creationell_wp_theme_class_container`, `creationell_wp_theme_class_header_action_spacer`, `creationell_wp_theme_class_header_actions`, `creationell_wp_theme_class_header_button`, `creationell_wp_theme_class_header_navbar_brand`, `creationell_wp_theme_class_header_navbar_breakpoint`, `creationell_wp_theme_class_header_navbar_toggler_breakpoint`, `creationell_wp_theme_class_header_offcanvas_direction`, `creationell_wp_theme_class_offcanvas_body`, `creationell_wp_theme_class_offcanvas_header`, `creationell_wp_theme_logo`, `creationell_wp_theme_logo_height`, `creationell_wp_theme_logo_width`, `creationell_wp_theme_offcanvas_navbar_title`

### [`template-parts/loop/`](../../template-parts/loop/)

#### [`template-parts/loop/cards-grid-term.php`](../../template-parts/loop/cards-grid-term.php)

Template part for displaying taxonomy TERM loop items in grid cards.

- Contexts: `cards-grid`
- Hooks: `creationell_wp_theme_after_loop_item`, `creationell_wp_theme_after_loop_tags`, `creationell_wp_theme_after_loop_thumbnail`, `creationell_wp_theme_after_loop_title`, `creationell_wp_theme_before_loop_item`, `creationell_wp_theme_before_loop_thumbnail`, `creationell_wp_theme_before_loop_title`, `creationell_wp_theme_bs_loop_term_count_label`, `creationell_wp_theme_class_loop_card`, `creationell_wp_theme_class_loop_card_body`, `creationell_wp_theme_class_loop_card_image`, `creationell_wp_theme_class_loop_card_text_excerpt`, `creationell_wp_theme_class_loop_card_text_read_more`, `creationell_wp_theme_class_loop_card_title`, `creationell_wp_theme_class_loop_card_title_link`, `creationell_wp_theme_class_loop_read_more`, `creationell_wp_theme_loop_excerpt`, `creationell_wp_theme_loop_item_after_card_body`, `creationell_wp_theme_loop_meta`, `creationell_wp_theme_loop_read_more`, `creationell_wp_theme_loop_read_more_text`

#### [`template-parts/loop/cards-grid.php`](../../template-parts/loop/cards-grid.php)

Template part for displaying loop items in cards

- Contexts: `cards-grid`
- Loaded by: `archive.php`, `author.php`, `home.php`, `index.php`, `search.php`
- Hooks: `creationell_wp_theme_after_loop_item`, `creationell_wp_theme_after_loop_tags`, `creationell_wp_theme_after_loop_thumbnail`, `creationell_wp_theme_after_loop_title`, `creationell_wp_theme_before_loop_item`, `creationell_wp_theme_before_loop_thumbnail`, `creationell_wp_theme_before_loop_title`, `creationell_wp_theme_class_loop_card`, `creationell_wp_theme_class_loop_card_body`, `creationell_wp_theme_class_loop_card_content_meta_wrapper`, `creationell_wp_theme_class_loop_card_image`, `creationell_wp_theme_class_loop_card_text_read_more`, `creationell_wp_theme_class_loop_card_title`, `creationell_wp_theme_class_loop_card_title_link`, `creationell_wp_theme_class_loop_read_more`, `creationell_wp_theme_loop_category`, `creationell_wp_theme_loop_excerpt`, `creationell_wp_theme_loop_item_after_card_body`, `creationell_wp_theme_loop_meta`, `creationell_wp_theme_loop_read_more`, `creationell_wp_theme_loop_read_more_text`, `creationell_wp_theme_loop_tags`

#### [`template-parts/loop/cards-horizontal-term.php`](../../template-parts/loop/cards-horizontal-term.php)

Template part for displaying taxonomy TERM loop items in horizontal cards.

- Contexts: `cards-horizontal`
- Hooks: `creationell_wp_theme_after_loop_item`, `creationell_wp_theme_after_loop_tags`, `creationell_wp_theme_after_loop_thumbnail`, `creationell_wp_theme_after_loop_title`, `creationell_wp_theme_before_loop_item`, `creationell_wp_theme_before_loop_thumbnail`, `creationell_wp_theme_before_loop_title`, `creationell_wp_theme_bs_loop_term_count_label`, `creationell_wp_theme_class_loop_card`, `creationell_wp_theme_class_loop_card_body`, `creationell_wp_theme_class_loop_card_content_col`, `creationell_wp_theme_class_loop_card_image`, `creationell_wp_theme_class_loop_card_image_col`, `creationell_wp_theme_class_loop_card_row`, `creationell_wp_theme_class_loop_card_text_excerpt`, `creationell_wp_theme_class_loop_card_text_read_more`, `creationell_wp_theme_class_loop_card_title`, `creationell_wp_theme_class_loop_card_title_link`, `creationell_wp_theme_class_loop_read_more`, `creationell_wp_theme_loop_excerpt`, `creationell_wp_theme_loop_item_after_card_body`, `creationell_wp_theme_loop_meta`, `creationell_wp_theme_loop_read_more`, `creationell_wp_theme_loop_read_more_text`

#### [`template-parts/loop/cards-horizontal.php`](../../template-parts/loop/cards-horizontal.php)

Template part for displaying loop items in horizontal cards (default)

- Contexts: `cards-horizontal`
- Loaded by: `archive.php`, `author.php`, `home.php`, `index.php`, `search.php`
- Hooks: `creationell_wp_theme_after_loop_item`, `creationell_wp_theme_after_loop_tags`, `creationell_wp_theme_after_loop_thumbnail`, `creationell_wp_theme_after_loop_title`, `creationell_wp_theme_before_loop_item`, `creationell_wp_theme_before_loop_thumbnail`, `creationell_wp_theme_before_loop_title`, `creationell_wp_theme_class_loop_card`, `creationell_wp_theme_class_loop_card_body`, `creationell_wp_theme_class_loop_card_content_col`, `creationell_wp_theme_class_loop_card_content_meta_wrapper`, `creationell_wp_theme_class_loop_card_image`, `creationell_wp_theme_class_loop_card_image_col`, `creationell_wp_theme_class_loop_card_row`, `creationell_wp_theme_class_loop_card_text_read_more`, `creationell_wp_theme_class_loop_card_title`, `creationell_wp_theme_class_loop_card_title_link`, `creationell_wp_theme_class_loop_read_more`, `creationell_wp_theme_loop_category`, `creationell_wp_theme_loop_excerpt`, `creationell_wp_theme_loop_item_after_card_body`, `creationell_wp_theme_loop_meta`, `creationell_wp_theme_loop_read_more`, `creationell_wp_theme_loop_read_more_text`, `creationell_wp_theme_loop_tags`

#### [`template-parts/loop/cards-overlay-term.php`](../../template-parts/loop/cards-overlay-term.php)

Template part for displaying taxonomy TERM loop items in cards-overlay (hero template).

- Contexts: `cards-overlay`
- Hooks: `creationell_wp_theme_after_loop_item`, `creationell_wp_theme_after_loop_tags`, `creationell_wp_theme_after_loop_thumbnail`, `creationell_wp_theme_after_loop_title`, `creationell_wp_theme_before_loop_item`, `creationell_wp_theme_before_loop_thumbnail`, `creationell_wp_theme_before_loop_title`, `creationell_wp_theme_bs_loop_term_count_label`, `creationell_wp_theme_class_loop_card`, `creationell_wp_theme_class_loop_card_image`, `creationell_wp_theme_class_loop_card_img_overlay`, `creationell_wp_theme_class_loop_card_img_overlay_content_wrapper`, `creationell_wp_theme_class_loop_card_text_excerpt`, `creationell_wp_theme_class_loop_card_text_read_more`, `creationell_wp_theme_class_loop_card_title`, `creationell_wp_theme_class_loop_card_title_link`, `creationell_wp_theme_class_loop_read_more`, `creationell_wp_theme_loop_excerpt`, `creationell_wp_theme_loop_item_after_card_img_overlay`, `creationell_wp_theme_loop_meta`, `creationell_wp_theme_loop_read_more`, `creationell_wp_theme_loop_read_more_text`

#### [`template-parts/loop/cards-overlay.php`](../../template-parts/loop/cards-overlay.php)

Template part for displaying loop items in cards-overlay (hero template, featured image is required)

- Contexts: `cards-overlay`
- Loaded by: `archive.php`, `author.php`, `home.php`, `index.php`, `search.php`
- Hooks: `creationell_wp_theme_after_loop_item`, `creationell_wp_theme_after_loop_tags`, `creationell_wp_theme_after_loop_thumbnail`, `creationell_wp_theme_after_loop_title`, `creationell_wp_theme_before_loop_item`, `creationell_wp_theme_before_loop_thumbnail`, `creationell_wp_theme_before_loop_title`, `creationell_wp_theme_class_loop_card`, `creationell_wp_theme_class_loop_card_content_meta_wrapper`, `creationell_wp_theme_class_loop_card_image`, `creationell_wp_theme_class_loop_card_img_overlay`, `creationell_wp_theme_class_loop_card_img_overlay_content_wrapper`, `creationell_wp_theme_class_loop_card_text_read_more`, `creationell_wp_theme_class_loop_card_title`, `creationell_wp_theme_class_loop_card_title_link`, `creationell_wp_theme_class_loop_read_more`, `creationell_wp_theme_loop_category`, `creationell_wp_theme_loop_excerpt`, `creationell_wp_theme_loop_item_after_card_img_overlay`, `creationell_wp_theme_loop_meta`, `creationell_wp_theme_loop_read_more`, `creationell_wp_theme_loop_read_more_text`, `creationell_wp_theme_loop_tags`

#### [`template-parts/loop/content-none.php`](../../template-parts/loop/content-none.php)

Template part for displaying a message that posts cannot be found

- Loads: `searchform.php`
- Loaded by: `search.php`

#### [`template-parts/loop/custom.php`](../../template-parts/loop/custom.php)

Template part for displaying custom loop items

- Loaded by: `archive.php`, `author.php`, `home.php`, `index.php`, `search.php`
- Hooks: `creationell_wp_theme_after_loop_item`, `creationell_wp_theme_before_loop_item`, `creationell_wp_theme_custom_loop_item`

#### [`template-parts/loop/loop-none.php`](../../template-parts/loop/loop-none.php)

Template part for displaying no content

- Loaded by: `archive.php`, `author.php`, `index.php`

### [`template-parts/post-filter/`](../../template-parts/post-filter/)

#### [`template-parts/post-filter/filter.php`](../../template-parts/post-filter/filter.php)

Template part: the filter bar of the plugin JPKCom Post Filter at the top of the blog sidebar.

- Loaded by: `modules/post-filter/inc/class-post-filter-module.php`

### [`template-parts/post-lists/`](../../template-parts/post-lists/)

#### [`template-parts/post-lists/accordion.php`](../../template-parts/post-lists/accordion.php)

Template part: the posts of the block creationell-theme/post-accordion as a Bootstrap accordion.

- Loads: `template-parts/post-lists/panel.php`
- Loaded by: `modules/post-lists/inc/class-post-lists-module.php`

#### [`template-parts/post-lists/card-grid.php`](../../template-parts/post-lists/card-grid.php)

Template part: post card of the post lists, the slider and the related posts, layout grid.

- Loaded by: `modules/post-lists/inc/class-post-lists-module.php`, `template-parts/post-lists/list.php`, `template-parts/related-posts/related-posts.php`

#### [`template-parts/post-lists/card-hero.php`](../../template-parts/post-lists/card-hero.php)

Template part: post card of the post lists and the slider, layout hero.

- Loaded by: `modules/post-lists/inc/class-post-lists-module.php`, `template-parts/post-lists/list.php`

#### [`template-parts/post-lists/card-list.php`](../../template-parts/post-lists/card-list.php)

Template part: post card of the post lists, layout list (horizontal card).

- Loaded by: `modules/post-lists/inc/class-post-lists-module.php`, `template-parts/post-lists/list.php`

#### [`template-parts/post-lists/empty.php`](../../template-parts/post-lists/empty.php)

Template part: empty state of the post blocks, shown in the block editor only.

- Loaded by: `modules/post-lists/inc/class-post-lists-module.php`, `modules/post-slider/inc/class-post-slider-module.php`, `modules/related-posts/inc/class-related-posts-module.php`

#### [`template-parts/post-lists/list.php`](../../template-parts/post-lists/list.php)

Template part: the post list of the block creationell-theme/post-list.

- Loads: `template-parts/post-lists/card-<creationell_wp_theme_layout>.php`, `template-parts/post-lists/pagination.php`
- Loaded by: `modules/post-lists/inc/class-post-lists-module.php`

#### [`template-parts/post-lists/pagination.php`](../../template-parts/post-lists/pagination.php)

Template part: page links of a paginated post list.

- Loaded by: `modules/post-lists/inc/class-post-lists-module.php`, `template-parts/post-lists/list.php`

#### [`template-parts/post-lists/panel.php`](../../template-parts/post-lists/panel.php)

Template part: the panel of one post in the accordion or the tabs of creationell-theme/post-accordion.

- Loaded by: `modules/post-lists/inc/class-post-lists-module.php`, `template-parts/post-lists/accordion.php`, `template-parts/post-lists/tabs.php`

#### [`template-parts/post-lists/tabs.php`](../../template-parts/post-lists/tabs.php)

Template part: the posts of the block creationell-theme/post-accordion as Bootstrap tabs.

- Loads: `template-parts/post-lists/panel.php`
- Loaded by: `modules/post-lists/inc/class-post-lists-module.php`

### [`template-parts/post-slider/`](../../template-parts/post-slider/)

#### [`template-parts/post-slider/slider.php`](../../template-parts/post-slider/slider.php)

Template part: the post slider of the block creationell-theme/post-slider.

- Loaded by: `modules/post-slider/inc/class-post-slider-module.php`

### [`template-parts/related-posts/`](../../template-parts/related-posts/)

#### [`template-parts/related-posts/related-posts.php`](../../template-parts/related-posts/related-posts.php)

Template part: the related posts below a single post and of the block creationell-theme/related-posts.

- Loads: `template-parts/post-lists/card-grid.php`
- Loaded by: `modules/related-posts/inc/class-related-posts-module.php`

## Block template parts

- [`parts/footer.html`](../../parts/footer.html), loaded by `footer.php`
- [`parts/header.html`](../../parts/header.html), loaded by `header.php`
