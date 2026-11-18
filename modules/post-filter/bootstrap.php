<?php
/**
 * Hooks of the module post-filter; loaded only while the module is active.
 *
 * The module gate requires this file with $slug, $state and $manifest in scope,
 * after the bootstrap.php of the module blog, which this module requires.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\PostFilter\Post_Filter_Module;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/class-post-filter-module.php';

Post_Filter_Module::boot( isset( $state ) && is_string( $state ) ? $state : 'active' );
