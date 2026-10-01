<?php
/**
 * Hooks of the module contact-form-7; loaded only while the module is active.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\ContactForm7\Module;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/class-module.php';
require_once __DIR__ . '/inc/class-form-markup.php';
require_once __DIR__ . '/inc/class-assets.php';

( new Module( __DIR__, CREATIONELL_WP_THEME_URL . '/modules/contact-form-7' ) )->register();
