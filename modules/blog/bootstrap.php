<?php
/**
 * Hooks of the module blog; loaded only while the module is active.
 *
 * The module gate requires this file with $slug, $state and $manifest in scope.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\Blog\Blog_Module;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/class-blog-home.php';
require_once __DIR__ . '/inc/class-term-lists.php';
require_once __DIR__ . '/inc/class-blog-sidebar.php';
require_once __DIR__ . '/inc/class-entry-parts.php';
require_once __DIR__ . '/inc/class-share-links.php';
require_once __DIR__ . '/inc/class-not-found.php';
require_once __DIR__ . '/inc/class-author-pages.php';
require_once __DIR__ . '/inc/class-author-profile-fields.php';
require_once __DIR__ . '/inc/class-blog-doctor.php';
require_once __DIR__ . '/inc/class-blog-module.php';

Blog_Module::boot( isset( $state ) && is_string( $state ) ? $state : 'active' );
