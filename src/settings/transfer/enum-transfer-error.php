<?php
/**
 * Error codes of the settings transfer.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reasons why an import or export stops without writing anything.
 *
 * The value is the stable code for redirects, WP-CLI output and tests; message()
 * returns the translated text for people. Call message() after init.
 *
 * @since 1.0.0
 */
enum Transfer_Error: string {

	case UPLOAD_FAILED         = 'upload_failed';
	case TOO_LARGE             = 'too_large';
	case WRONG_EXTENSION       = 'wrong_extension';
	case INVALID_JSON          = 'invalid_json';
	case WRONG_FORMAT          = 'wrong_format';
	case SCHEMA_INVALID        = 'schema_invalid';
	case SCHEMA_NEWER          = 'schema_newer';
	case NO_MIGRATION          = 'no_migration';
	case LANGUAGE_ALL          = 'language_all';
	case ACFE_MULTILANG        = 'acfe_multilang';
	case FORBIDDEN             = 'forbidden';
	case TOKEN_INVALID         = 'token_invalid';
	case TOKEN_EXPIRED         = 'token_expired';
	case TOKEN_USER_MISMATCH   = 'token_user_mismatch';
	case NOTHING_SELECTED      = 'nothing_selected';
	case CONFIRMATION_REQUIRED = 'confirmation_required';
	case STRICT_REJECTED       = 'strict_rejected';
	case BACKUP_NOT_FOUND      = 'backup_not_found';

	/**
	 * Returns the translated message of the error.
	 *
	 * @since 1.0.0
	 *
	 * @return string Message, a full sentence.
	 */
	public function message(): string {
		return match ( $this ) {
			self::UPLOAD_FAILED         => __( 'The file could not be uploaded. Please try again.', 'creationell-wp-theme' ),
			self::TOO_LARGE             => __( 'The file is larger than 1 MB.', 'creationell-wp-theme' ),
			self::WRONG_EXTENSION       => __( 'Only JSON files ending in .json can be imported.', 'creationell-wp-theme' ),
			self::INVALID_JSON          => __( 'The file does not contain valid JSON.', 'creationell-wp-theme' ),
			self::WRONG_FORMAT          => __( 'The file is not a settings export of this theme.', 'creationell-wp-theme' ),
			self::SCHEMA_INVALID        => __( 'The file does not match the export format.', 'creationell-wp-theme' ),
			self::SCHEMA_NEWER          => __( 'The file comes from a newer theme version. Update the theme first.', 'creationell-wp-theme' ),
			self::NO_MIGRATION          => __( 'The file uses an older format that this theme version cannot convert.', 'creationell-wp-theme' ),
			self::LANGUAGE_ALL          => __( 'Choose a single language in the language switcher first; all languages at once are not supported.', 'creationell-wp-theme' ),
			self::ACFE_MULTILANG        => __( 'Import and export are not available while the multilingual mode of ACF Extended is active.', 'creationell-wp-theme' ),
			self::FORBIDDEN             => __( 'You are not allowed to import or export settings.', 'creationell-wp-theme' ),
			self::TOKEN_INVALID         => __( 'The import could not be found. Upload the file again.', 'creationell-wp-theme' ),
			self::TOKEN_EXPIRED         => __( 'The import has expired. Upload the file again.', 'creationell-wp-theme' ),
			self::TOKEN_USER_MISMATCH   => __( 'The import was started by another user.', 'creationell-wp-theme' ),
			self::NOTHING_SELECTED      => __( 'Select at least one section.', 'creationell-wp-theme' ),
			self::CONFIRMATION_REQUIRED => __( 'Confirm the module changes before importing.', 'creationell-wp-theme' ),
			self::STRICT_REJECTED       => __( 'Nothing was imported because some values were rejected.', 'creationell-wp-theme' ),
			self::BACKUP_NOT_FOUND      => __( 'The backup could not be found.', 'creationell-wp-theme' ),
		};
	}
}
