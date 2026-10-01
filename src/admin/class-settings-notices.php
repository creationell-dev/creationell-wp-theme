<?php
/**
 * Notices of the theme settings pages.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Admin;

use Creationell\WpTheme\Settings\Acf\Acf_Adapter;
use Creationell\WpTheme\Settings\Acf\Options_Pages;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Set_Result;
use Creationell\WpTheme\Settings\Set_Status;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells the user of a settings page what the last save did and whether the page is read-only.
 *
 * ACF redirects after saving, so the results wait per user in a short transient
 * and are shown once on the next settings page: saved and reset settings as
 * success, refusals (locked, forbidden, contrast, invalid …) as error with
 * label and reason. Unlike Notices, these notices reach every user of the
 * pages, editors included.
 *
 * @since 1.0.0
 */
final class Settings_Notices {

	/**
	 * Prefix of the transient per user; the user ID follows.
	 *
	 * @since 1.0.0
	 */
	public const TRANSIENT_PREFIX = 'creationell_wp_theme_settings_notice_';

	/**
	 * Seconds the results wait for the next page load.
	 *
	 * @since 1.0.0
	 */
	public const LIFETIME = 300;

	/**
	 * Keeps the results of a save for the next page load of a user; results without change are left out.
	 *
	 * @since 1.0.0
	 *
	 * @param int                    $user_id User ID.
	 * @param array<int, Set_Result> $results Results.
	 * @return void
	 */
	public static function store( int $user_id, array $results ): void {
		$entries = array();
		foreach ( $results as $result ) {
			if ( Set_Status::UNCHANGED === $result->status ) {
				continue;
			}
			$entries[] = array(
				'key'     => $result->key,
				'status'  => $result->status->value,
				'message' => $result->message,
			);
		}
		if ( array() === $entries ) {
			delete_transient( self::TRANSIENT_PREFIX . $user_id );
			return;
		}
		set_transient( self::TRANSIENT_PREFIX . $user_id, $entries, self::LIFETIME );
	}

	/**
	 * Returns and forgets the waiting results of a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array{key: string, status: string, message: string}> Results in save order.
	 */
	public static function take( int $user_id ): array {
		$stored = get_transient( self::TRANSIENT_PREFIX . $user_id );
		if ( false === $stored ) {
			return array();
		}
		delete_transient( self::TRANSIENT_PREFIX . $user_id );
		if ( ! is_array( $stored ) ) {
			return array();
		}
		$entries = array();
		foreach ( $stored as $entry ) {
			if ( is_array( $entry ) && is_string( $entry['key'] ?? null ) && is_string( $entry['status'] ?? null ) && is_string( $entry['message'] ?? null ) ) {
				$entries[] = array(
					'key'     => $entry['key'],
					'status'  => $entry['status'],
					'message' => $entry['message'],
				);
			}
		}
		return $entries;
	}

	/**
	 * Prints the notices of the current settings page for the current user; runs on admin_notices.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		self::print( Options_Pages::current(), Acf_Adapter::instance()->current_user() );
	}

	/**
	 * Prints the read-only hint and the waiting results on a settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $page    Menu slug of the settings page; null prints nothing.
	 * @param int         $user_id User ID.
	 * @return void
	 */
	public static function print( ?string $page, int $user_id ): void {
		if ( null === $page ) {
			return;
		}
		if ( ! Options_Pages::writable( $page, $user_id ) ) {
			self::notice( 'info', array( __( 'Read only: you may view these settings but not change them.', 'creationell-wp-theme' ) ) );
		}
		$saved   = array();
		$removed = array();
		$errors  = array();
		foreach ( self::take( $user_id ) as $entry ) {
			$label = self::label( $entry['key'] );
			if ( Set_Status::SAVED->value === $entry['status'] ) {
				$saved[] = $label;
			} elseif ( Set_Status::REMOVED->value === $entry['status'] ) {
				$removed[] = $label;
			} else {
				$errors[] = sprintf(
					/* translators: 1: label of the setting, 2: reason. */
					__( 'Not saved: %1$s: %2$s', 'creationell-wp-theme' ),
					$label,
					'' !== $entry['message'] ? $entry['message'] : $entry['status']
				);
			}
		}
		$success = array();
		if ( array() !== $saved ) {
			/* translators: %s: comma-separated labels of settings. */
			$success[] = sprintf( __( 'Saved: %s.', 'creationell-wp-theme' ), implode( ', ', $saved ) );
		}
		if ( array() !== $removed ) {
			/* translators: %s: comma-separated labels of settings. */
			$success[] = sprintf( __( 'Back to the default: %s.', 'creationell-wp-theme' ), implode( ', ', $removed ) );
		}
		self::notice( 'success', $success );
		self::notice( 'error', $errors );
	}

	/**
	 * Prints one notice with a paragraph per line; nothing without lines.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $type  Notice type: info, success or error.
	 * @param array<int, string> $lines Plain text lines.
	 * @return void
	 */
	private static function notice( string $type, array $lines ): void {
		if ( array() === $lines ) {
			return;
		}
		$paragraphs = '';
		foreach ( $lines as $line ) {
			$paragraphs .= '<p>' . esc_html( $line ) . '</p>';
		}
		printf(
			'<div class="notice notice-%1$s">%2$s</div>' . "\n",
			esc_attr( $type ),
			wp_kses( $paragraphs, array( 'p' => array() ) )
		);
	}

	/**
	 * Returns the label of a setting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Setting key.
	 * @return string Translated label, or the key without one.
	 */
	private static function label( string $key ): string {
		$registry = Registry::instance();
		$label    = $registry->has( $key ) ? $registry->get( $key )->label_text() : '';
		return '' !== $label ? $label : $key;
	}
}
