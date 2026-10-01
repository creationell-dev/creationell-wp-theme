<?php
/**
 * Hooks of the module post-lists; loaded only while the module is active or hidden.
 *
 * The module gate requires this file with $slug, $state and $manifest in scope.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\PostLists\Post_Lists_Module;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/class-post-lists-module.php';

Post_Lists_Module::boot( isset( $state ) && is_string( $state ) ? $state : 'active' );
