<?php
/**
 * WP-CLI command "wp creationell-theme consent services".
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Cli;

use Creationell\WpTheme\Consent\Consent_Service_Catalog;
use Creationell\WpTheme\Consent\Consent_Services_Store;
use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Set_Result;
use Creationell\WpTheme\Settings\Set_Status;
use Creationell\WpTheme\Settings\Write_Context;
use WP_CLI;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lists, adds from presets and deletes the services of the cookie dialog.
 *
 * Every subcommand, "list" and "presets" included, needs --user=<login> of a
 * user with the capability creationell_wp_theme_manage_consent; without it the
 * command stops with exit code 1. Changes go through Consent_Services_Store
 * (channel "cli"). Deleting asks for confirmation unless --yes is given.
 *
 * @since 1.0.0
 */
final class Consent_Command {

	/**
	 * Command name below the theme namespace.
	 *
	 * @since 1.0.0
	 */
	public const NAME = 'creationell-theme consent services';

	/**
	 * Message of a subcommand without the capability.
	 *
	 * @since 1.0.0
	 */
	public const USER_ERROR = 'This command reads and changes consent services. Run it with --user=<login> of a user with the capability creationell_wp_theme_manage_consent.';

	/**
	 * Formats of the lists.
	 *
	 * @var list<string>
	 */
	private const FORMATS = array( 'table', 'json', 'csv', 'yaml', 'count' );

	/**
	 * Fields of the list of services.
	 *
	 * @var list<string>
	 */
	private const LIST_FIELDS = array( 'id', 'category', 'name', 'provider', 'cookies', 'preset', 'newer', 'purpose' );

	/**
	 * Fields of the list of presets.
	 *
	 * @var list<string>
	 */
	private const PRESET_FIELDS = array( 'id', 'category', 'name', 'checked', 'cookies', 'applied' );

	/**
	 * Registers every subcommand as its own command; runs on cli_init.
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $command Instance whose methods run the subcommands; without one a new instance.
	 * @return void
	 */
	public static function register( ?self $command = null ): void {
		$command = $command ?? new self();
		WP_CLI::add_command( self::NAME . ' list', array( $command, 'list_' ) );
		WP_CLI::add_command( self::NAME . ' presets', array( $command, 'presets' ) );
		WP_CLI::add_command( self::NAME . ' add-preset', array( $command, 'add_preset' ) );
		WP_CLI::add_command( self::NAME . ' delete', array( $command, 'delete' ) );
	}

	/**
	 * Lists the stored services.
	 *
	 * ## OPTIONS
	 *
	 * [--lang=<code>]
	 * : Language of the purpose texts; default: the default language.
	 *
	 * [--format=<format>]
	 * : Output format: table, json, csv, yaml or count. Default: table.
	 *
	 * [--user=<login>]
	 * : User with the capability creationell_wp_theme_manage_consent.
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme consent services list --user=admin --lang=en --format=json
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments, none.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function list_( array $args, array $assoc_args ): void {
		unset( $args );
		if ( ! self::may_use() ) {
			return;
		}
		$format = self::format( $assoc_args );
		$lang   = self::lang( $assoc_args );
		if ( null === $format ) {
			return;
		}
		$store  = Consent_Services_Store::read();
		$stored = array();
		foreach ( $store['services'] as $service ) {
			if ( is_array( $service ) && is_string( $service['id'] ?? null ) ) {
				$stored[ $service['id'] ] = $service;
			}
		}
		$rows = array();
		foreach ( Consent_Services_Store::services_for( $store, $lang, Language::instance()->default() ) as $service ) {
			$raw    = $stored[ $service['id'] ] ?? array();
			$preset = $raw['preset'] ?? '';
			$rows[] = array(
				'id'       => $service['id'],
				'category' => $service['category'],
				'name'     => $service['name'],
				'provider' => $service['provider'],
				'cookies'  => count( $service['cookies'] ),
				'preset'   => is_string( $preset ) ? $preset : '',
				'newer'    => Consent_Service_Catalog::newer( $raw ) ?? '',
				'purpose'  => $service['purpose'],
			);
		}
		\WP_CLI\Utils\format_items( $format, $rows, self::LIST_FIELDS );
	}

	/**
	 * Lists the presets of the catalog and whether they are applied.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format: table, json, csv, yaml or count. Default: table.
	 *
	 * [--user=<login>]
	 * : User with the capability creationell_wp_theme_manage_consent.
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme consent services presets --user=admin
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments, none.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function presets( array $args, array $assoc_args ): void {
		unset( $args );
		if ( ! self::may_use() ) {
			return;
		}
		$format = self::format( $assoc_args );
		if ( null === $format ) {
			return;
		}
		foreach ( Consent_Service_Catalog::findings() as $finding ) {
			WP_CLI::warning( 'Preset dropped: ' . $finding );
		}
		$applied = array();
		foreach ( Consent_Services_Store::read()['services'] as $service ) {
			if ( is_array( $service ) && is_string( $service['preset'] ?? null ) ) {
				$applied[ $service['preset'] ] = true;
			}
		}
		$rows = array();
		foreach ( Consent_Service_Catalog::all() as $id => $preset ) {
			$rows[] = array(
				'id'       => $id,
				'category' => $preset['category'],
				'name'     => $preset['name'],
				'checked'  => $preset['checked'],
				'cookies'  => count( $preset['cookies'] ),
				'applied'  => isset( $applied[ $id ] ) ? 'yes' : 'no',
			);
		}
		\WP_CLI\Utils\format_items( $format, $rows, self::PRESET_FIELDS );
	}

	/**
	 * Adds a service from a preset of the catalog.
	 *
	 * ## OPTIONS
	 *
	 * <preset>
	 * : ID of the preset, see "presets".
	 *
	 * [--user=<login>]
	 * : User with the capability creationell_wp_theme_manage_consent.
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme consent services add-preset matomo --user=admin
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments: the preset ID.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function add_preset( array $args, array $assoc_args ): void {
		unset( $assoc_args );
		if ( ! self::may_use() ) {
			return;
		}
		$id      = $args[0] ?? '';
		$presets = Consent_Service_Catalog::all();
		if ( ! isset( $presets[ $id ] ) ) {
			WP_CLI::error( sprintf( 'Unknown preset: %s. See wp creationell-theme consent services presets.', $id ) );
			return;
		}
		$result = Consent_Service_Catalog::apply( $id, self::context() );
		if ( Set_Status::SAVED === $result->status ) {
			WP_CLI::success( sprintf( 'Added service %1$s from the preset (checked %2$s). Check the entries before going live.', $id, $presets[ $id ]['checked'] ) );
			return;
		}
		if ( Set_Status::INVALID === $result->status && 'duplicate' === self::first_code( $result ) ) {
			WP_CLI::error( sprintf( 'Preset %s is already applied.', $id ) );
			return;
		}
		WP_CLI::error( self::refusal( $result ) );
	}

	/**
	 * Deletes a service with its texts; the category texts stay.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : ID of the service, see "list".
	 *
	 * [--yes]
	 * : Do not ask for confirmation.
	 *
	 * [--user=<login>]
	 * : User with the capability creationell_wp_theme_manage_consent.
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme consent services delete matomo --yes --user=admin
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments: the service ID.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function delete( array $args, array $assoc_args ): void {
		if ( ! self::may_use() ) {
			return;
		}
		$id    = $args[0] ?? '';
		$store = Consent_Services_Store::read();
		$kept  = array();
		$count = null;
		foreach ( $store['services'] as $service ) {
			if ( is_array( $service ) && ( $service['id'] ?? null ) === $id && null === $count ) {
				$count = is_array( $service['cookies'] ?? null ) ? count( $service['cookies'] ) : 0;
				continue;
			}
			$kept[] = $service;
		}
		if ( null === $count ) {
			WP_CLI::error( sprintf( 'Unknown service: %s. See wp creationell-theme consent services list.', $id ) );
			return;
		}
		WP_CLI::confirm( sprintf( 'Delete the service %1$s with %2$d cookies?', $id, $count ), $assoc_args );
		$store['services'] = $kept;
		foreach ( $store['texts'] as $code => $entry ) {
			$list = is_array( $entry ) ? ( $entry['services'] ?? null ) : null;
			if ( is_array( $entry ) && is_array( $list ) ) {
				unset( $list[ $id ] );
				$entry['services']       = $list;
				$store['texts'][ $code ] = $entry;
			}
		}
		$result = Consent_Services_Store::save( $store, self::context() );
		if ( Set_Status::SAVED !== $result->status ) {
			WP_CLI::error( self::refusal( $result ) );
			return;
		}
		WP_CLI::success( sprintf( 'Deleted service %1$s (%2$d cookies).', $id, $count ) );
		WP_CLI::log( 'Visitors are asked again only after a consent revision bump.' );
	}

	/**
	 * Tells whether the current user may use the command; stops with an error otherwise.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when allowed.
	 */
	private static function may_use(): bool {
		if ( user_can( get_current_user_id(), Capabilities::MANAGE_CONSENT ) ) {
			return true;
		}
		WP_CLI::error( self::USER_ERROR );
		return false;
	}

	/**
	 * Returns the write context of the command.
	 *
	 * @since 1.0.0
	 *
	 * @return Write_Context Context for the channel "cli".
	 */
	private static function context(): Write_Context {
		return new Write_Context( channel: 'cli', user_id: get_current_user_id(), lang: Language::instance()->default() );
	}

	/**
	 * Returns the code of the first error of a refused result.
	 *
	 * @since 1.0.0
	 *
	 * @param Set_Result $result Result.
	 * @return string Code; empty without errors.
	 */
	private static function first_code( Set_Result $result ): string {
		$errors = $result->data['errors'] ?? null;
		$first  = is_array( $errors ) ? ( $errors[0] ?? null ) : null;
		return is_array( $first ) && is_string( $first['code'] ?? null ) ? $first['code'] : '';
	}

	/**
	 * Describes a refused result: the errors as "field: message", else the message and status.
	 *
	 * @since 1.0.0
	 *
	 * @param Set_Result $result Result.
	 * @return string Text.
	 */
	private static function refusal( Set_Result $result ): string {
		$errors = $result->data['errors'] ?? null;
		$lines  = array();
		foreach ( is_array( $errors ) ? $errors : array() as $error ) {
			if ( is_array( $error ) && is_string( $error['field'] ?? null ) && is_string( $error['message'] ?? null ) ) {
				$lines[] = $error['field'] . ': ' . $error['message'];
			}
		}
		if ( array() !== $lines ) {
			return implode( '; ', $lines );
		}
		return sprintf( '%1$s (%2$s)', $result->message, $result->status->value );
	}

	/**
	 * Reads and checks the format argument; stops with an error for an unknown format.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return string|null Format, or null after an error.
	 */
	private static function format( array $assoc_args ): ?string {
		$format = $assoc_args['format'] ?? 'table';
		if ( is_string( $format ) && in_array( $format, self::FORMATS, true ) ) {
			return $format;
		}
		WP_CLI::error( sprintf( 'Unknown format; use one of: %s.', implode( ', ', self::FORMATS ) ) );
		return null;
	}

	/**
	 * Reads and checks --lang.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return string Language code; the default language without --lang.
	 */
	private static function lang( array $assoc_args ): string {
		$language = Language::instance();
		$lang     = $assoc_args['lang'] ?? null;
		if ( null === $lang ) {
			return $language->default();
		}
		$active = $language->active();
		if ( is_string( $lang ) && in_array( $lang, $active, true ) ) {
			return $lang;
		}
		WP_CLI::error( sprintf( 'Unknown language %1$s; active languages: %2$s.', is_string( $lang ) ? $lang : '?', implode( ', ', $active ) ) );
		return $language->default();
	}
}
