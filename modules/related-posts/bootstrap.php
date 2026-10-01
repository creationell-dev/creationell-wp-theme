<?php
/**
 * Hooks of the module related-posts; loaded only while the module is active or hidden.
 *
 * The module gate requires this file with $slug, $state and $manifest in scope.
 * Only an active module adds the related posts below single posts; a hidden
 * one renders existing blocks only (FS-13).
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\RelatedPosts\Related_Posts_Module;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/class-related-posts-module.php';

Related_Posts_Module::boot( isset( $state ) && is_string( $state ) ? $state : 'active' );
