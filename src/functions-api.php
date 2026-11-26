<?php
/**
 * Public functions of the theme for templates, child themes and plugins.
 *
 * The functions wrap the classes of src/; their names and signatures stay stable.
 * They load before the classes and call them only when they run.
 *
 * They are not pluggable: a child theme or plugin that defines one of them first
 * ends with "Cannot redeclare" instead of silently replacing it. A replaced
 * creationell_wp_theme_consent_allowed() could fail open, a replaced
 * creationell_wp_theme_module_state() could bypass the kill switch constants.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

use Creationell\WpTheme\Blog\Blog_Context;
use Creationell\WpTheme\Blog\Blog_Url;
use Creationell\WpTheme\Blog\Reading_Time;
use Creationell\WpTheme\Consent\Consent_Categories;
use Creationell\WpTheme\Consent\Consent_Gate;
use Creationell\WpTheme\Consent\Script_Marker;
use Creationell\WpTheme\Core\Template_Part_Gate;
use Creationell\WpTheme\Modules\Module_State;
use Creationell\WpTheme\Settings\Bootstrap_Line;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Settings;
use Creationell\WpTheme\Wpml\Language_Switcher;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns the state of a theme module.
 *
 * Precedence: default "off" < child default < value saved in the backend <
 * child lock < constant CREATIONELL_WP_THEME_MODULE_<SLUG>; the child layers
 * come from the filter creationell_wp_theme_modules. A module without its
 * folder, an unknown slug or a state the module does not know gives "off".
 * Reliable from after_setup_theme on, when the child theme has added its
 * filters.
 *
 * Example:
 *
 *     if ( 'active' === creationell_wp_theme_module_state( 'header-footer' ) ) {
 *         // Render the template part of the module.
 *     }
 *
 * @api
 * @since 1.0.0
 *
 * @param string $slug Module slug, e.g. "header-footer".
 * @return string "active", "hidden" or "off".
 */
function creationell_wp_theme_module_state( string $slug ): string {
	return Module_State::instance()->state( $slug );
}

/**
 * Returns the value of a theme setting.
 *
 * Precedence: default < child default < value saved in the backend < child
 * lock < constant CREATIONELL_WP_THEME_<KEY>; the child layers come from the
 * filter creationell_wp_theme_settings. Translated texts follow the language of
 * the request and fall back to the default language. Module switches follow
 * creationell_wp_theme_module_state(). Reads only the autoloaded snapshot, never
 * ACF. An unknown key gives null and a _doing_it_wrong() notice.
 *
 * Example:
 *
 *     $line   = creationell_wp_theme_setting( 'bootstrap_line' ); // 5
 *     $footer = creationell_wp_theme_setting( 'footer_text' );    // '' or the saved text
 *
 * @api
 * @since 1.0.0
 *
 * @param string $key Setting key, e.g. "bootstrap_line" or "module_consent".
 * @return mixed Value, or null for an unknown key.
 */
function creationell_wp_theme_setting( string $key ): mixed {
	try {
		return Settings::instance()->get( $key );
	} catch ( InvalidArgumentException $exception ) {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html( sprintf( 'Unknown setting key "%s". Settings of modules are known from init on.', $key ) ),
			'1.0.0'
		);
		return null;
	}
}

/**
 * Returns the active Bootstrap line.
 *
 * This theme version includes line 5 only; a constant that asks for another
 * line keeps line 5 and shows a notice.
 *
 * Example:
 *
 *     $folder = 'bootstrap-' . creationell_wp_theme_bootstrap_line(); // "bootstrap-5"
 *
 * @api
 * @since 1.0.0
 *
 * @return int Line, 5.
 */
function creationell_wp_theme_bootstrap_line(): int {
	return Bootstrap_Line::instance()->active();
}

/**
 * Tells whether the visitor allowed a consent category.
 *
 * Fails closed: PHP never knows the visitor's choice, so only "necessary" is
 * allowed, also with a consent provider. Mark a script with its category
 * instead, or ask window.creationellWpTheme.consent.allowed() in the browser:
 *
 *     wp_script_add_data( 'my-analytics', 'creationell-wp-theme-consent', 'analytics' );
 *
 * Example:
 *
 *     if ( creationell_wp_theme_consent_allowed( 'necessary' ) ) {
 *         // Always true.
 *     }
 *
 * @api
 * @since 1.0.0
 *
 * @param string $category Category, e.g. "necessary" or "analytics".
 * @return bool True when allowed.
 */
function creationell_wp_theme_consent_allowed( string $category ): bool {
	return Consent_Gate::allowed( $category );
}

/**
 * Returns the consent categories.
 *
 * By default "necessary", "functional", "analytics" and "marketing"; the
 * filter creationell_wp_theme_consent_categories changes the list. Values
 * that are no category name (lower case letters, digits, "-" and "_",
 * starting with a letter) fall away; "necessary" is always first.
 *
 * Example:
 *
 *     $categories = creationell_wp_theme_consent_categories(); // array( 'necessary', 'functional', ... )
 *
 * @api
 * @since 1.0.0
 *
 * @return list<string> Categories.
 */
function creationell_wp_theme_consent_categories(): array {
	return Consent_Categories::all();
}

/**
 * Returns an inline script that runs only after consent to a category.
 *
 * Outside "necessary" the tag gets type="text/plain" and data-category; the
 * consent provider runs it after consent. The code is escaped, so "<script"
 * and "</script" in it cannot end the tag. An unknown category keeps the
 * script blocked and gives a _doing_it_wrong() notice.
 *
 * Example:
 *
 *     $tag = creationell_wp_theme_consent_inline_script( 'analytics', 'window.myCounter = 1;' );
 *
 * @api
 * @since 1.0.0
 *
 * @param string $category   Category, e.g. "analytics"; "necessary" runs at once.
 * @param string $javascript JavaScript code.
 * @return string SCRIPT tag, or an empty string when WordPress cannot embed the code.
 */
function creationell_wp_theme_consent_inline_script( string $category, string $javascript ): string {
	$attributes = array();
	if ( Consent_Gate::NECESSARY !== $category ) {
		if ( ! Consent_Categories::is_known( $category ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				esc_html( sprintf( 'Unknown consent category "%1$s"; the script stays blocked. Use one of: %2$s.', $category, implode( ', ', Consent_Categories::all() ) ) ),
				'1.0.0'
			);
		}
		$attributes = array(
			'type'          => 'text/plain',
			'data-category' => $category,
		);
	}
	return wp_get_inline_script_tag( Script_Marker::escape_script( $javascript ), $attributes );
}

/**
 * Returns the language switcher as escaped HTML.
 *
 * Needs WPML with at least two languages; without them, before init and with
 * "enabled" false it returns an empty string. The first switcher of a request
 * is a navigation landmark "Language", every further one a group. The links
 * lead to the current page in each language, or to the home page of a
 * language without translation. Print the result through wp_kses() with
 * Language_Switcher::allowed_html(), as template-parts/header/language-switcher.php does.
 *
 * Example:
 *
 *     echo wp_kses(
 *         creationell_wp_theme_language_switcher(
 *             array(
 *                 'variant' => 'list',
 *                 'context' => 'footer',
 *             )
 *         ),
 *         \Creationell\WpTheme\Wpml\Language_Switcher::allowed_html()
 *     );
 *
 * @api
 * @since 1.0.0
 *
 * @param array<string, mixed> $args Arguments: enabled (bool, default true), variant ("dropdown" or "list", default "dropdown"), display ("code" or "native"; default "code" for the dropdown, "native" for the list), context ("navbar", "footer" or "block", default "navbar"). The filter creationell_wp_theme_language_switcher_args may change them.
 * @return string HTML, or an empty string.
 */
function creationell_wp_theme_language_switcher( array $args = array() ): string {
	return Language_Switcher::instance()->render( $args );
}

/**
 * Prints the template part header or footer of the Site Editor.
 *
 * Needs the module header-footer in the state "active" and a part of the
 * active theme with content. Otherwise it prints nothing and returns false, so
 * the caller prints its PHP markup instead; header.php and footer.php of the
 * theme do so. Another slug than header or footer gives false and a
 * _doing_it_wrong() notice. The part renders through the template-part block,
 * a changed part of the database before the file in parts/.
 *
 * Example:
 *
 *     if ( ! creationell_wp_theme_render_template_part( 'footer' ) ) {
 *         get_template_part( 'template-parts/footer/my-footer' );
 *     }
 *
 * @api
 * @since 1.0.0
 *
 * @param string $slug Part slug, "header" or "footer".
 * @return bool True when the part was printed.
 */
function creationell_wp_theme_render_template_part( string $slug ): bool {
	return Template_Part_Gate::render( $slug );
}

/**
 * Returns the URL of the blog page.
 *
 * With a static front page it is the posts page of the reading options, in
 * the language of the request with WPML; with posts on the front page it is
 * the home URL. Without a posts page it is an empty string. The filter
 * creationell_wp_theme_blog_url may change it.
 *
 * Example:
 *
 *     $url = creationell_wp_theme_blog_url();
 *     if ( '' !== $url ) {
 *         printf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Blog', 'my-child' ) );
 *     }
 *
 * @api
 * @since 1.0.0
 *
 * @return string URL, or an empty string.
 */
function creationell_wp_theme_blog_url(): string {
	return Blog_Url::url();
}

/**
 * Returns the title of the posts page as plain text.
 *
 * Tags are removed and character references decoded; escape the title for
 * the output. Empty without a posts page and with posts on the front page.
 *
 * Example:
 *
 *     echo esc_html( creationell_wp_theme_blog_title() ); // "News & more"
 *
 * @api
 * @since 1.0.0
 *
 * @return string Title, or an empty string.
 */
function creationell_wp_theme_blog_title(): string {
	return Blog_Url::title();
}

/**
 * Tells whether at least one post is published.
 *
 * Asked once per request.
 *
 * Example:
 *
 *     if ( creationell_wp_theme_has_blog_posts() ) {
 *         // Link to the blog.
 *     }
 *
 * @api
 * @since 1.0.0
 *
 * @return bool True when a published post exists.
 */
function creationell_wp_theme_has_blog_posts(): bool {
	return Blog_Url::has_posts();
}

/**
 * Returns the minutes a post takes to read, at least 1.
 *
 * Counts the words of the content without blocks that excerpts drop,
 * shortcodes and HTML, and Han, Hiragana and Katakana characters at 500 a
 * minute. The words per minute come from the setting blog_reading_wpm
 * (200 while it is not registered); the filter
 * creationell_wp_theme_reading_time_wpm may change them. The result is stored
 * in post meta until the post changes. An ID without a post gives 1.
 *
 * Example:
 *
 *     $minutes = creationell_wp_theme_blog_reading_time( get_the_ID() );
 *     printf(
 *         esc_html( _n( '%s minute', '%s minutes', $minutes, 'my-child' ) ),
 *         esc_html( number_format_i18n( $minutes ) )
 *     );
 *
 * @api
 * @since 1.0.0
 *
 * @param int|WP_Post $post Post ID or post.
 * @return int Minutes.
 */
function creationell_wp_theme_blog_reading_time( int|WP_Post $post ): int {
	$post = $post instanceof WP_Post ? $post : get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return 1;
	}
	$wpm = 200;
	if ( Registry::instance()->has( 'blog_reading_wpm' ) ) {
		$value = Settings::instance()->get( 'blog_reading_wpm' );
		$wpm   = is_numeric( $value ) ? (int) $value : $wpm;
	}
	return Reading_Time::minutes( $post, $wpm );
}

/**
 * Tells whether the request shows the blog.
 *
 * True for the blog page, a single post, a category, tag or date archive and
 * an author archive of posts; false for search results. The filter
 * creationell_wp_theme_is_blog_view may change the answer. Reliable once the
 * main query ran.
 *
 * Example:
 *
 *     if ( creationell_wp_theme_is_blog_view() ) {
 *         // Show a newsletter box.
 *     }
 *
 * @api
 * @since 1.0.0
 *
 * @return bool True for a blog view.
 */
function creationell_wp_theme_is_blog_view(): bool {
	return Blog_Context::is_blog_view();
}
