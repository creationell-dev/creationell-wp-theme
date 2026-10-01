<?php
/**
 * Hooks of the module consent; loaded only while the module is active.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\Consent\Module;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/class-provider.php';
require_once __DIR__ . '/inc/class-links.php';
require_once __DIR__ . '/inc/class-texts.php';
require_once __DIR__ . '/inc/class-config-builder.php';
require_once __DIR__ . '/inc/class-module.php';

( new Module( __DIR__, CREATIONELL_WP_THEME_URL . '/modules/consent' ) )->register();
