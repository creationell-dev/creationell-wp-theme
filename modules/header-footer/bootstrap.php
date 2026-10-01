<?php
/**
 * Hooks of the module header-footer; loaded only while the module is active.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\HeaderFooter\Header_Footer_Module;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/class-access-routes.php';
require_once __DIR__ . '/inc/class-access-context.php';
require_once __DIR__ . '/inc/class-template-part-access.php';
require_once __DIR__ . '/inc/class-editor-restrictions.php';
require_once __DIR__ . '/inc/class-menu-scope.php';
require_once __DIR__ . '/inc/class-part-translations.php';
require_once __DIR__ . '/inc/class-header-blocks.php';
require_once __DIR__ . '/inc/class-contact-formatter.php';
require_once __DIR__ . '/inc/class-social-networks.php';
require_once __DIR__ . '/inc/class-text-blocks.php';
require_once __DIR__ . '/inc/class-header-footer-module.php';

Header_Footer_Module::boot();
