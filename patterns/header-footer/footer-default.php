<?php
/**
 * Title: Footer
 * Slug: creationell-wp-theme/footer-default
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * Content of the template part footer of the module header-footer: in the
 * column area of the PHP footer the columns contact, social links and footer
 * menu, below them the footer info with the footer text or the copyright line.
 * The pattern holds no text; the texts of the footer come from the theme
 * settings.
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- wp:group {"className":"bg-body-tertiary pt-5 pb-4 creationell-theme-footer-columns","layout":{"type":"default"}} -->
<div class="wp-block-group bg-body-tertiary pt-5 pb-4 creationell-theme-footer-columns"><!-- wp:group {"className":"container","layout":{"type":"default"}} -->
<div class="wp-block-group container"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:creationell-theme/contact /--></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:creationell-theme/social-links /--></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:creationell-theme/footer-menu /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"bg-body-tertiary text-body-secondary border-top py-2 text-center creationell-theme-footer-info","layout":{"type":"default"}} -->
<div class="wp-block-group bg-body-tertiary text-body-secondary border-top py-2 text-center creationell-theme-footer-info"><!-- wp:group {"className":"container","layout":{"type":"default"}} -->
<div class="wp-block-group container"><!-- wp:creationell-theme/footer-text /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
