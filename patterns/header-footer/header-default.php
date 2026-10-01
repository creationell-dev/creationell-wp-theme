<?php
/**
 * Title: Header
 * Slug: creationell-wp-theme/header-default
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * Content of the template part header of the module header-footer: a bar with
 * the language switcher (empty without WPML), then the navbar, locked against
 * moving and removing. The navbar block leaves out the switcher of the header
 * actions, so the page shows one switcher. The pattern holds no text; the
 * texts of the header come from the theme settings.
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- wp:group {"className":"container d-flex justify-content-end small","layout":{"type":"default"}} -->
<div class="wp-block-group container d-flex justify-content-end small"><!-- wp:creationell-theme/language-switcher /--></div>
<!-- /wp:group -->

<!-- wp:creationell-theme/navbar {"lock":{"move":true,"remove":true}} /-->
