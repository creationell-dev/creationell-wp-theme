<?php
/**
 * Hooks of the module post-slider; loaded only while the module is active or hidden.
 *
 * The module gate requires this file with $slug, $state and $manifest in scope.
 * Hidden or active, the module behaves the same; the gate takes the block out
 * of the inserter while it is hidden.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\PostSlider\Post_Slider_Module;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/class-post-slider-module.php';

Post_Slider_Module::boot();
