<?php
/**
 * Texts of the block.json files of the module header-footer, for the extraction of translatable strings.
 *
 * The theme never loads this file. Blockstudio passes the texts of block.json at
 * run time and Block_I18n translates them with the text domain of the theme; the
 * literal calls here let the extraction find them. Each text appears once.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return array(
	// Block navbar.
	__( 'Navbar', 'creationell-wp-theme' ),
	__( 'Main menu of the header with logo, search and a menu that opens from the side on small screens.', 'creationell-wp-theme' ),
	__( 'header', 'creationell-wp-theme' ),
	__( 'menu', 'creationell-wp-theme' ),
	__( 'navigation', 'creationell-wp-theme' ),
	__( 'Show the full menu from', 'creationell-wp-theme' ),
	__( 'Below this screen width the menu opens from the side.', 'creationell-wp-theme' ),
	__( 'Small screens (576 px)', 'creationell-wp-theme' ),
	__( 'Medium screens (768 px)', 'creationell-wp-theme' ),
	__( 'Large screens (992 px)', 'creationell-wp-theme' ),
	__( 'Extra large screens (1200 px)', 'creationell-wp-theme' ),
	__( 'Extra extra large screens (1400 px)', 'creationell-wp-theme' ),
	__( 'Show logo', 'creationell-wp-theme' ),
	__( 'Show search', 'creationell-wp-theme' ),
	__( 'Menu opens from', 'creationell-wp-theme' ),
	__( 'Start side', 'creationell-wp-theme' ),
	__( 'End side', 'creationell-wp-theme' ),

	// Block logo.
	__( 'Logo', 'creationell-wp-theme' ),
	__( 'Logo of the site from the theme files, with a variant for the dark color scheme; the site name without logo files.', 'creationell-wp-theme' ),
	__( 'logo', 'creationell-wp-theme' ),
	__( 'brand', 'creationell-wp-theme' ),
	__( 'Link to the home page', 'creationell-wp-theme' ),

	// Block search.
	__( 'Search form', 'creationell-wp-theme' ),
	__( 'Search form of the theme.', 'creationell-wp-theme' ),
	__( 'search', 'creationell-wp-theme' ),

	// Block footer-menu.
	__( 'Footer menu', 'creationell-wp-theme' ),
	__( 'Menu assigned to the location Footer menu.', 'creationell-wp-theme' ),
	__( 'footer', 'creationell-wp-theme' ),

	// Block footer-text.
	__( 'Footer text', 'creationell-wp-theme' ),
	__( 'Footer text of the theme settings, or the copyright line without it, with the footer links of the theme.', 'creationell-wp-theme' ),
	__( 'copyright', 'creationell-wp-theme' ),
	__( 'text', 'creationell-wp-theme' ),

	// Block contact.
	__( 'Contact', 'creationell-wp-theme' ),
	__( 'Address, phone and email of the theme settings.', 'creationell-wp-theme' ),
	__( 'contact', 'creationell-wp-theme' ),
	__( 'address', 'creationell-wp-theme' ),
	__( 'phone', 'creationell-wp-theme' ),
	__( 'email', 'creationell-wp-theme' ),
	__( 'Heading', 'creationell-wp-theme' ),
	__( 'Empty shows "Contact".', 'creationell-wp-theme' ),
	__( 'Heading level', 'creationell-wp-theme' ),
	__( 'Heading 2', 'creationell-wp-theme' ),
	__( 'Heading 3', 'creationell-wp-theme' ),
	__( 'Heading 4', 'creationell-wp-theme' ),
	__( 'Heading 5', 'creationell-wp-theme' ),
	__( 'Heading 6', 'creationell-wp-theme' ),
	__( 'Show address', 'creationell-wp-theme' ),
	__( 'Show phone', 'creationell-wp-theme' ),
	__( 'Show email', 'creationell-wp-theme' ),

	// Block social-links.
	__( 'Social links', 'creationell-wp-theme' ),
	__( 'Links to the social media profiles of the theme settings.', 'creationell-wp-theme' ),
	__( 'social', 'creationell-wp-theme' ),
	__( 'links', 'creationell-wp-theme' ),
	__( 'profiles', 'creationell-wp-theme' ),
	__( 'Name of the list', 'creationell-wp-theme' ),
	__( 'Screen readers read it out. Empty uses "Social media".', 'creationell-wp-theme' ),

	// Block language-switcher.
	__( 'Language switcher', 'creationell-wp-theme' ),
	__( 'Links to the page in the other languages of WPML; shows nothing without WPML.', 'creationell-wp-theme' ),
	__( 'language', 'creationell-wp-theme' ),
	__( 'translation', 'creationell-wp-theme' ),
	__( 'WPML', 'creationell-wp-theme' ),
	__( 'Display', 'creationell-wp-theme' ),
	__( 'List of languages', 'creationell-wp-theme' ),
	__( 'Dropdown', 'creationell-wp-theme' ),
);
