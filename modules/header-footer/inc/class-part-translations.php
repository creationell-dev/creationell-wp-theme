<?php
/**
 * Missing WPML translations of the template parts header and footer.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\HeaderFooter;

use Creationell\WpTheme\Admin\Notices;
use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Core\Template_Part_Gate;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Wpml\Wpml_Integration;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Finds header and footer parts without a usable WPML translation and tells the editors.
 *
 * Only with WPML. A part stored in the database for the active stylesheet
 * needs a translation in every active language, linked by WPML and stored
 * under the same slug, because the template-part block looks parts up by
 * slug. Without one the language shows the part of the default language
 * (display-as-translated in the wpml-config.xml of the theme), with another slug the
 * part file of the theme. Parts that exist only as theme files need no
 * translation: their patterns take the texts from the theme translations.
 * The theme translates nothing itself and has no language fallback of its
 * own.
 *
 * Header and footer are translated with WPML only: in a language without a
 * linked translation the Site Editor edits the stored part of the default
 * language, and saving there overwrites it; no unlinked copy appears (checked
 * in a browser with WordPress 7.1 and WPML 5.0). The dashboard therefore
 * shows the missing translations, and the Site Editor warns in such a
 * language. Both go to users with the template parts capability.
 *
 * @since 1.0.0
 */
final class Part_Translations {

	/**
	 * ID of the dashboard notice.
	 *
	 * @since 1.0.0
	 */
	public const NOTICE = 'header-footer-translations';

	/**
	 * ID of the notice in the Site Editor.
	 *
	 * @since 1.0.0
	 */
	public const EDITOR_NOTICE = 'creationell-wp-theme-part-translations';

	/**
	 * Reason of a finding: no linked translation.
	 *
	 * @since 1.0.0
	 */
	public const REASON_MISSING = 'missing';

	/**
	 * Reason of a finding: the linked translation has another slug.
	 *
	 * @since 1.0.0
	 */
	public const REASON_SLUG = 'slug';

	/**
	 * Script handle of the Site Editor that carries the notice.
	 */
	private const EDITOR_SCRIPT = 'wp-edit-site';

	/**
	 * Language code WPML gives for "All languages" in the admin.
	 */
	private const ALL_LANGUAGES = 'all';

	/**
	 * Registers the hooks; called when the module boots.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'load-index.php', array( self::class, 'queue_notice' ), 10, 0 );
		add_action( 'load-site-editor.php', array( self::class, 'editor_notice' ), 10, 0 );
	}

	/**
	 * Returns the header and footer parts without a usable translation.
	 *
	 * For every part of the database (Template_Part_Gate::stored_parts()) and
	 * every active language: without an ID from wpml_object_id the reason is
	 * "missing", with an ID of a post under another slug "slug". A part and
	 * language appear once, header first, then in the order of the stored parts
	 * and the languages. Empty without WPML.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array{slug: string, lang: string, reason: string}> Findings.
	 */
	public static function missing(): array {
		if ( ! Wpml_Integration::is_active() ) {
			return array();
		}
		$languages = Language::instance()->active();
		$findings  = array();
		foreach ( Access_Routes::PARTS as $slug ) {
			foreach ( Template_Part_Gate::stored_parts( $slug ) as $post_id ) {
				foreach ( $languages as $lang ) {
					$reason = self::check( $post_id, $slug, $lang );
					if ( null === $reason || isset( $findings[ $slug . '|' . $lang ] ) ) {
						continue;
					}
					$findings[ $slug . '|' . $lang ] = array(
						'slug'   => $slug,
						'lang'   => $lang,
						'reason' => $reason,
					);
				}
			}
		}
		return array_values( $findings );
	}

	/**
	 * Returns the parts without a usable part in a language: no stored part translated into it, or only the theme file.
	 *
	 * Call it with WPML only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $lang Language code.
	 * @return list<string> Part slugs, header first.
	 */
	public static function untranslated( string $lang ): array {
		$slugs = array();
		foreach ( Access_Routes::PARTS as $slug ) {
			$usable = false;
			foreach ( Template_Part_Gate::stored_parts( $slug ) as $post_id ) {
				if ( null === self::check( $post_id, $slug, $lang ) ) {
					$usable = true;
					break;
				}
			}
			if ( ! $usable ) {
				$slugs[] = $slug;
			}
		}
		return $slugs;
	}

	/**
	 * Queues the notice about missing translations; runs on load-index.php, the dashboard.
	 *
	 * The notice looks the findings up when it renders, for users with the
	 * template parts capability only, and stays empty without findings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function queue_notice(): void {
		if ( ! Wpml_Integration::is_active() ) {
			return;
		}
		Notices::add( self::NOTICE, static fn(): string => self::message( self::missing() ), Notices::WARNING, Capabilities::EDIT_TEMPLATE_PARTS );
	}

	/**
	 * Warns in the Site Editor while a part has no usable translation into the current language; runs on load-site-editor.php.
	 *
	 * Only with WPML, in a language other than the default one and "All
	 * languages", and for users with the template parts capability. The notice
	 * goes to the notices store of the block editor, because the Site Editor
	 * hides admin notices; the theme loads no script file for it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function editor_notice(): void {
		if ( ! Wpml_Integration::is_active() || ! current_user_can( Capabilities::EDIT_TEMPLATE_PARTS ) ) {
			return;
		}
		$language = Language::instance();
		$lang     = $language->current();
		if ( self::ALL_LANGUAGES === $lang || $language->is_default( $lang ) ) {
			return;
		}
		$slugs = self::untranslated( $lang );
		if ( array() === $slugs ) {
			return;
		}
		$message = wp_json_encode( self::editor_message( $slugs, $lang, $language->default() ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE );
		$id      = wp_json_encode( self::EDITOR_NOTICE );
		if ( false === $message || false === $id ) {
			return;
		}
		wp_add_inline_script( self::EDITOR_SCRIPT, sprintf( 'wp.data.dispatch( "core/notices" ).createWarningNotice( %1$s, { id: %2$s } );', $message, $id ), 'after' );
	}

	/**
	 * Builds the text of the dashboard notice.
	 *
	 * One sentence per part and reason with the language codes, then the rule
	 * to translate with WPML only.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{slug: string, lang: string, reason: string}> $findings Findings of missing().
	 * @return string Plain text; empty without findings.
	 */
	public static function message( array $findings ): string {
		if ( array() === $findings ) {
			return '';
		}
		$sentences = array();
		foreach ( self::group( $findings ) as $group ) {
			$name = self::name( $group['slug'] );
			$lang = implode( ', ', $group['langs'] );
			if ( self::REASON_SLUG === $group['reason'] ) {
				$sentences[] = sprintf(
					/* translators: 1: part name, e.g. "footer", 2: comma-separated language codes, 3: part slug. */
					__( 'The %1$s translation into %2$s has a slug other than "%3$s"; pages in these languages show the %1$s of the theme file.', 'creationell-wp-theme' ),
					$name,
					$lang,
					$group['slug']
				);
				continue;
			}
			$sentences[] = sprintf(
				/* translators: 1: part name, e.g. "header", 2: comma-separated language codes. */
				__( 'The %1$s has no translation into %2$s; pages in these languages show the %1$s of the default language.', 'creationell-wp-theme' ),
				$name,
				$lang
			);
		}
		$sentences[] = __( 'Translate header and footer with WPML only, not by switching the language in the Site Editor.', 'creationell-wp-theme' );
		return implode( ' ', $sentences );
	}

	/**
	 * Builds the warnings of the doctor section header_footer, in English like the other doctor warnings.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{slug: string, lang: string, reason: string}> $findings Findings of missing().
	 * @return list<string> One warning per part and reason.
	 */
	public static function doctor_warnings( array $findings ): array {
		$warnings = array();
		foreach ( self::group( $findings ) as $group ) {
			$lang       = implode( ', ', $group['langs'] );
			$warnings[] = self::REASON_SLUG === $group['reason']
				? sprintf( 'The translation of template part %1$s into %2$s has another slug; these languages show the part file of the theme. Translate it again with WPML.', $group['slug'], $lang )
				: sprintf( 'Template part %1$s has no translation into %2$s; these languages show the part of the default language. Translate it with WPML.', $group['slug'], $lang );
		}
		return $warnings;
	}

	/**
	 * Tells why a stored part has no usable translation into a language.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID of a stored part.
	 * @param string $slug    Its slug.
	 * @param string $lang    Language code.
	 * @return string|null REASON_MISSING, REASON_SLUG, or null when the translation is usable.
	 */
	private static function check( int $post_id, string $slug, string $lang ): ?string {
		$translated = apply_filters( 'wpml_object_id', $post_id, 'wp_template_part', false, $lang );
		if ( ! is_numeric( $translated ) || (int) $translated <= 0 ) {
			return self::REASON_MISSING;
		}
		return get_post_field( 'post_name', (int) $translated ) === $slug ? null : self::REASON_SLUG;
	}

	/**
	 * Groups findings by part and reason, in the order they first appear.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{slug: string, lang: string, reason: string}> $findings Findings.
	 * @return list<array{slug: string, reason: string, langs: list<string>}> Groups.
	 */
	private static function group( array $findings ): array {
		$groups = array();
		foreach ( $findings as $finding ) {
			$key = $finding['slug'] . '|' . $finding['reason'];
			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = array(
					'slug'   => $finding['slug'],
					'reason' => $finding['reason'],
					'langs'  => array(),
				);
			}
			$groups[ $key ]['langs'][] = $finding['lang'];
		}
		return array_values( $groups );
	}

	/**
	 * Builds the text of the Site Editor notice.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $slugs        Parts without a usable part in the language.
	 * @param string             $lang         Current language code.
	 * @param string             $default_lang Default language code.
	 * @return string Plain text.
	 */
	private static function editor_message( array $slugs, string $lang, string $default_lang ): string {
		return sprintf(
			/* translators: 1: language code, e.g. "en", 2: comma-separated part names, 3: code of the default language. */
			__( 'No linked translation into %1$s exists for: %2$s. Saving here overwrites the part of the default language %3$s. Translate header and footer with WPML first, or switch the admin language to %3$s.', 'creationell-wp-theme' ),
			$lang,
			implode( ', ', array_map( static fn( string $slug ): string => self::name( $slug ), $slugs ) ),
			$default_lang
		);
	}

	/**
	 * Returns the name of a part inside a sentence.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Part slug.
	 * @return string Translated name; the slug for another part.
	 */
	private static function name( string $slug ): string {
		return match ( $slug ) {
			'header' => _x( 'header', 'template part name inside a sentence', 'creationell-wp-theme' ),
			'footer' => _x( 'footer', 'template part name inside a sentence', 'creationell-wp-theme' ),
			default  => $slug,
		};
	}
}
