<?php
/**
 * Excerpt.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


// Excerpt for pages.
add_post_type_support( 'page', 'excerpt' );
