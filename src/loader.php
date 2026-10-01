<?php
/**
 * Loads the theme code in a fixed order: the constants, the public functions, the classes
 * of src/, the template helpers of inc/; then Theme::boot() registers the hooks of the
 * theme core.
 *
 * The theme has no autoloader; every file is listed here and loaded by its path.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

use Creationell\WpTheme\Core\Theme;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Constants and public functions.
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/functions-api.php';

// Theme core.
require_once __DIR__ . '/core/class-environment.php';
require_once __DIR__ . '/core/class-theme-support.php';
require_once __DIR__ . '/core/class-theme.php';
require_once __DIR__ . '/core/class-capabilities.php';
require_once __DIR__ . '/core/class-site-editor-lock.php';
require_once __DIR__ . '/core/class-template-part-gate.php';
require_once __DIR__ . '/admin/class-notices.php';
require_once __DIR__ . '/admin/class-menu.php';
require_once __DIR__ . '/assets/class-stylesheet-locator.php';
require_once __DIR__ . '/assets/class-bootstrap-bridge.php';
require_once __DIR__ . '/assets/class-assets.php';
require_once __DIR__ . '/assets/class-editor-assets.php';
require_once __DIR__ . '/settings/class-definition.php';
require_once __DIR__ . '/settings/class-registry.php';
require_once __DIR__ . '/settings/class-settings.php';
require_once __DIR__ . '/settings/class-bootstrap-line.php';
require_once __DIR__ . '/settings/class-language.php';
require_once __DIR__ . '/settings/class-option-store.php';
require_once __DIR__ . '/settings/class-sanitizer.php';
require_once __DIR__ . '/settings/class-snapshot.php';
require_once __DIR__ . '/settings/class-contrast.php';
require_once __DIR__ . '/settings/class-contrast-rules.php';
require_once __DIR__ . '/settings/class-token-map.php';
require_once __DIR__ . '/settings/class-font-family.php';
require_once __DIR__ . '/settings/class-font-catalog.php';
require_once __DIR__ . '/settings/class-font-coverage.php';
require_once __DIR__ . '/settings/class-write-context.php';
require_once __DIR__ . '/settings/enum-set-status.php';
require_once __DIR__ . '/settings/class-set-result.php';
require_once __DIR__ . '/settings/class-setter.php';
require_once __DIR__ . '/settings/class-theme-json-tokens.php';
require_once __DIR__ . '/settings/class-global-styles-lock.php';
require_once __DIR__ . '/settings/transfer/enum-transfer-error.php';
require_once __DIR__ . '/settings/transfer/class-transfer-exception.php';
require_once __DIR__ . '/settings/transfer/enum-row-status.php';
require_once __DIR__ . '/settings/transfer/class-json-codec.php';
require_once __DIR__ . '/settings/transfer/class-reference-codec.php';
require_once __DIR__ . '/settings/transfer/class-transfer-file.php';
require_once __DIR__ . '/settings/transfer/class-migrator.php';
require_once __DIR__ . '/settings/transfer/class-removed-keys.php';
require_once __DIR__ . '/settings/transfer/class-upload-validator.php';
require_once __DIR__ . '/settings/transfer/class-transfer-log.php';
require_once __DIR__ . '/settings/transfer/class-backup-store.php';
require_once __DIR__ . '/settings/transfer/class-import-session.php';
require_once __DIR__ . '/settings/transfer/class-transfer.php';
require_once __DIR__ . '/settings/transfer/interface-section.php';
require_once __DIR__ . '/settings/transfer/class-export-request.php';
require_once __DIR__ . '/settings/transfer/class-import-options.php';
require_once __DIR__ . '/settings/transfer/class-plan-row.php';
require_once __DIR__ . '/settings/transfer/class-import-plan.php';
require_once __DIR__ . '/settings/transfer/class-import-report.php';
require_once __DIR__ . '/settings/transfer/class-settings-section.php';
require_once __DIR__ . '/settings/transfer/class-modules-section.php';
require_once __DIR__ . '/settings/transfer/class-sections.php';
require_once __DIR__ . '/settings/transfer/class-exporter.php';
require_once __DIR__ . '/settings/transfer/class-importer.php';
require_once __DIR__ . '/admin/class-transfer-page.php';
require_once __DIR__ . '/modules/class-module-manifest.php';
require_once __DIR__ . '/modules/class-module-catalog.php';
require_once __DIR__ . '/modules/interface-module-store.php';
require_once __DIR__ . '/modules/interface-module-row-store.php';
require_once __DIR__ . '/modules/class-option-module-store.php';
require_once __DIR__ . '/modules/class-module-state.php';
require_once __DIR__ . '/core/class-scan-result.php';
require_once __DIR__ . '/core/class-content-scanner.php';
require_once __DIR__ . '/modules/class-switch-request.php';
require_once __DIR__ . '/modules/class-switch-result.php';
require_once __DIR__ . '/modules/class-module-switcher.php';
require_once __DIR__ . '/settings/class-settings-module-store.php';
require_once __DIR__ . '/modules/class-inserter-visibility.php';
require_once __DIR__ . '/modules/class-block-i18n.php';
require_once __DIR__ . '/modules/class-module-gate.php';
require_once __DIR__ . '/assets/class-vendor-scripts.php';
require_once __DIR__ . '/posts/class-query-args.php';
require_once __DIR__ . '/posts/class-related-query.php';
require_once __DIR__ . '/posts/class-display-options.php';
require_once __DIR__ . '/posts/class-pagination.php';
require_once __DIR__ . '/posts/class-content-guard.php';
require_once __DIR__ . '/posts/class-slider-config.php';
require_once __DIR__ . '/settings/acf/class-field-factory.php';
require_once __DIR__ . '/settings/acf/class-options-pages.php';
require_once __DIR__ . '/settings/acf/class-acf-adapter.php';
require_once __DIR__ . '/admin/class-settings-notices.php';
require_once __DIR__ . '/admin/class-module-page.php';
require_once __DIR__ . '/admin/class-theme-switch-notice.php';
require_once __DIR__ . '/consent/interface-consent-provider.php';
require_once __DIR__ . '/consent/class-consent-gate.php';
require_once __DIR__ . '/consent/class-consent-categories.php';
require_once __DIR__ . '/consent/class-script-marker.php';
require_once __DIR__ . '/consent/class-consent-bridge.php';
require_once __DIR__ . '/wpml/class-wpml-integration.php';
require_once __DIR__ . '/wpml/class-language-switcher.php';
require_once __DIR__ . '/wpml/class-snapshot-sync.php';
require_once __DIR__ . '/wpml/class-acf-compat.php';
require_once __DIR__ . '/cli/class-doctor-command.php';
require_once __DIR__ . '/cli/class-module-command.php';
require_once __DIR__ . '/child/class-child-theme.php';
require_once __DIR__ . '/child/class-child-rules.php';
require_once __DIR__ . '/cli/class-child-command.php';
require_once __DIR__ . '/cli/class-update-command.php';
require_once __DIR__ . '/cli/class-wpml-command.php';
require_once __DIR__ . '/cli/class-settings-command.php';
require_once __DIR__ . '/cli/class-css-command.php';
require_once __DIR__ . '/cli/class-settings-transfer-command.php';

// Theme updater; loaded up front so that no updater code loads after an update replaced the files.
require_once __DIR__ . '/update/enum-update-error.php';
require_once __DIR__ . '/update/class-manifest.php';
require_once __DIR__ . '/update/class-theme-updater.php';

// Stylesheet compiler; the classes of a Bootstrap line and lib/ load through Compiler_Factory only.
require_once __DIR__ . '/compiler/interface-stylesheet-compiler.php';
require_once __DIR__ . '/compiler/class-compile-request.php';
require_once __DIR__ . '/compiler/class-compile-result.php';
require_once __DIR__ . '/compiler/class-scss-value.php';
require_once __DIR__ . '/compiler/class-fingerprint.php';
require_once __DIR__ . '/compiler/class-license-banner.php';
require_once __DIR__ . '/compiler/class-atomic-file-writer.php';
require_once __DIR__ . '/compiler/class-line-unavailable-exception.php';
require_once __DIR__ . '/compiler/class-compiler-factory.php';
require_once __DIR__ . '/compiler/class-custom-stylesheet.php';

// Template helpers.
require_once dirname( __DIR__ ) . '/inc/body.php';                    // Required WP body classes.
require_once dirname( __DIR__ ) . '/inc/breadcrumb.php';              // Breadcrumb.
require_once dirname( __DIR__ ) . '/inc/columns.php';                 // Main/sidebar column width and breakpoints.
require_once dirname( __DIR__ ) . '/inc/comments.php';                // Comments.
require_once dirname( __DIR__ ) . '/inc/excerpt.php';                 // Adds excerpt to pages.
require_once dirname( __DIR__ ) . '/inc/icons.php';                   // Allowed HTML for inline SVG icons output via creationell_wp_theme_icon_* filters.
require_once dirname( __DIR__ ) . '/inc/class-creationell-wp-theme-nav-walker.php'; // Bootstrap 5 navwalker.
require_once dirname( __DIR__ ) . '/inc/navmenu.php';                 // Register the nav menus.
require_once dirname( __DIR__ ) . '/inc/pagination.php';              // Pagination for loop and single posts.
require_once dirname( __DIR__ ) . '/inc/password-protected-form.php'; // Form if post or page is protected by password.
require_once dirname( __DIR__ ) . '/inc/template-tags.php';           // Meta information like author, date, comments, category and tags badges.
require_once dirname( __DIR__ ) . '/inc/template-functions.php';      // Functions which enhance the theme by hooking into WordPress.
require_once dirname( __DIR__ ) . '/inc/widgets.php';                 // Register widget area and disables Gutenberg in widgets.
require_once dirname( __DIR__ ) . '/inc/tinymce-editor.php';          // Fix body margin and font-family in backend if classic editor is used.

// Blocks: patterns.
require_once dirname( __DIR__ ) . '/inc/blocks/patterns.php'; // Register pattern category and script to hide wp-block classes.

// Widgets.
require_once dirname( __DIR__ ) . '/inc/blocks/block-widget-archives.php';        // Archive block.
require_once dirname( __DIR__ ) . '/inc/blocks/block-widget-calendar.php';        // Calendar block.
require_once dirname( __DIR__ ) . '/inc/blocks/block-widget-categories.php';      // Categories block.
require_once dirname( __DIR__ ) . '/inc/blocks/block-widget-latest-comments.php'; // Latest comments block.
require_once dirname( __DIR__ ) . '/inc/blocks/block-widget-latest-posts.php';    // Latest posts block.
require_once dirname( __DIR__ ) . '/inc/blocks/block-widget-search.php';          // Searchform block.

// Contents.
require_once dirname( __DIR__ ) . '/inc/blocks/block-buttons.php'; // Button block.
require_once dirname( __DIR__ ) . '/inc/blocks/block-code.php';    // Code block.
require_once dirname( __DIR__ ) . '/inc/blocks/block-quote.php';   // Quote block.
require_once dirname( __DIR__ ) . '/inc/blocks/block-table.php';   // Table block.

/**
 * Filters whether blocks and patterns without theme styles are disabled.
 *
 * @since 1.0.0
 *
 * @param bool $disable Whether to disable them.
 */
if ( apply_filters( 'creationell_wp_theme_disable_unsupported_blocks', false ) ) {
	require_once dirname( __DIR__ ) . '/inc/blocks/disable-unsupported-blocks.php';
}

// Hooks of the theme core.
Theme::boot();
