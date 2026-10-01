<?php
/**
 * Section "modules" of a settings transfer file.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Closure;
use Creationell\WpTheme\Modules\Module_State;
use Creationell\WpTheme\Modules\Module_Switcher;
use Creationell\WpTheme\Modules\Switch_Request;
use Creationell\WpTheme\Modules\Switch_Result;
use Creationell\WpTheme\Settings\Registry;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Exports and imports the module states through the module switcher, never through the setter.
 *
 * The file holds per switch key module_<slug> the backend state of the module
 * or null (no deviation from the base: registry default and child default).
 * The plan asks the switcher for a dry run with the target state (the file
 * value, or the base for null) in the channel "import" without confirmation.
 * A module whose switch off needs a confirmation marks the plan; apply()
 * switches it only when the person confirmed and saw at least as many live
 * contents as the switcher counts then.
 *
 * @since 1.0.0
 */
final class Modules_Section implements Section_Interface {

	/**
	 * ID of the section.
	 *
	 * @since 1.0.0
	 */
	public const ID = 'modules';

	/**
	 * Switches a module; null uses the shared module switcher.
	 *
	 * @var Closure|null
	 * @phpstan-var (Closure(Switch_Request): Switch_Result)|null
	 */
	private ?Closure $switcher;

	/**
	 * State resolver; null for the shared one.
	 *
	 * @var Module_State|null
	 */
	private ?Module_State $state;

	/**
	 * Takes the switch and the state resolver.
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null      $switcher Switches a module and returns the result; without one the shared module switcher.
	 * @param Module_State|null $state  State resolver; without one the shared one.
	 * @phpstan-param (Closure(Switch_Request): Switch_Result)|null $switcher
	 */
	public function __construct( ?Closure $switcher = null, ?Module_State $state = null ) {
		$this->switcher = $switcher;
		$this->state    = $state;
	}

	/**
	 * Returns the ID of the section.
	 *
	 * @since 1.0.0
	 *
	 * @return string "modules".
	 */
	public function id(): string {
		return self::ID;
	}

	/**
	 * Returns the translated name of the section.
	 *
	 * @since 1.0.0
	 *
	 * @return string Label.
	 */
	public function label(): string {
		return __( 'Modules', 'creationell-wp-theme' );
	}

	/**
	 * Returns the backend state of every module with a switch in the registry.
	 *
	 * @since 1.0.0
	 *
	 * @param Export_Request $request Request; the section has no languages.
	 * @return array<string, string|null> State or null by switch key, in catalog order.
	 */
	public function export( Export_Request $request ): array {
		unset( $request );
		$states = array();
		foreach ( $this->slugs() as $slug ) {
			$states[ Transfer_File::module_key( $slug ) ] = $this->stored( $slug );
		}
		return $states;
	}

	/**
	 * Plans the module switches with dry runs of the switcher.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File  $file    File.
	 * @param Import_Options $options Options; the plan never confirms.
	 * @return array<int, Plan_Row> Rows in file order, then the modules the file does not name.
	 * @phpstan-return list<Plan_Row>
	 */
	public function plan( Transfer_File $file, Import_Options $options ): array {
		unset( $options );
		$rows  = array();
		$named = array();
		foreach ( $file->modules as $key => $value ) {
			$slug           = Transfer_File::module_slug( $key );
			$named[ $slug ] = true;
			$result         = $this->switch_module( new Switch_Request( $slug, $this->target( $slug, $value ), 'import', false, true ) );
			$status         = self::status( $result );
			$rows[]         = new Plan_Row( self::ID, $key, null, $this->stored( $slug ), $value, $status, self::message( $status, $result ), $result );
		}
		foreach ( $this->slugs() as $slug ) {
			if ( ! isset( $named[ $slug ] ) ) {
				$rows[] = new Plan_Row( self::ID, Transfer_File::module_key( $slug ), null, $this->stored( $slug ), null, Row_Status::ABSENT, __( 'The file has no state for this module; it stays as it is.', 'creationell-wp-theme' ) );
			}
		}
		return $rows;
	}

	/**
	 * Switches the modules of the rows "module_switch", and of the rows that need a confirmation when the person confirmed.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, Plan_Row> $rows    Rows of this section from a fresh plan.
	 * @param Import_Options       $options Options with the confirmation and the live contents the person saw.
	 * @phpstan-param list<Plan_Row> $rows
	 * @return array<int, Plan_Row> Rows with the result of the switch.
	 * @phpstan-return list<Plan_Row>
	 */
	public function apply( array $rows, Import_Options $options ): array {
		foreach ( $rows as $index => $row ) {
			$confirmed = $options->confirm_modules && Row_Status::MODULE_NEEDS_CONFIRMATION === $row->status;
			if ( self::ID !== $row->section || ( Row_Status::MODULE_SWITCH !== $row->status && ! $confirmed ) ) {
				continue;
			}
			$slug           = Transfer_File::module_slug( $row->key );
			$result         = $this->switch_module( new Switch_Request( $slug, $this->target( $slug, $row->incoming ), 'import', $options->confirm_modules, false, $options->seen( $slug ) ) );
			$status         = $result->switched() ? Row_Status::MODULE_SWITCH : self::status( $result );
			$rows[ $index ] = $row->with_status( $status, self::message( $status, $result ), $result );
		}
		return $rows;
	}

	/**
	 * Returns the slugs of the modules with a switch in the registry.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Slugs in catalog order.
	 * @phpstan-return list<string>
	 */
	private function slugs(): array {
		$registry = Registry::instance();
		$slugs    = array();
		foreach ( $this->state()->catalog()->slugs() as $slug ) {
			if ( $registry->has( Transfer_File::module_key( $slug ) ) ) {
				$slugs[] = $slug;
			}
		}
		return $slugs;
	}

	/**
	 * Returns the target state of a module: the file value, or the base for null.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug  Module slug.
	 * @param mixed  $value File value.
	 * @return string Target state; "off" for null and an unknown module.
	 */
	private function target( string $slug, mixed $value ): string {
		if ( is_string( $value ) ) {
			return $value;
		}
		return $this->state()->catalog()->is_known( $slug ) ? $this->state()->base( $slug ) : 'off';
	}

	/**
	 * Returns the backend state of a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return string|null State, or null without a backend state, a store or for an unknown module.
	 */
	private function stored( string $slug ): ?string {
		$state = $this->state();
		$store = $state->store();
		return null !== $store && $state->catalog()->is_known( $slug ) ? $store->get( $slug ) : null;
	}

	/**
	 * Runs a switch request.
	 *
	 * @since 1.0.0
	 *
	 * @param Switch_Request $request Request.
	 * @return Switch_Result Result.
	 */
	private function switch_module( Switch_Request $request ): Switch_Result {
		if ( null !== $this->switcher ) {
			return ( $this->switcher )( $request );
		}
		return Module_Switcher::instance()->switch( $request );
	}

	/**
	 * Returns the state resolver.
	 *
	 * @since 1.0.0
	 *
	 * @return Module_State State resolver.
	 */
	private function state(): Module_State {
		return $this->state ?? Module_State::instance();
	}

	/**
	 * Maps a switch result to a row status.
	 *
	 * @since 1.0.0
	 *
	 * @param Switch_Result $result Result.
	 * @return Row_Status Status.
	 */
	private static function status( Switch_Result $result ): Row_Status {
		return match ( $result->status ) {
			Switch_Result::DRY_RUN, Switch_Result::SWITCHED => Row_Status::MODULE_SWITCH,
			Switch_Result::UNCHANGED          => Row_Status::MODULE_UNCHANGED,
			Switch_Result::LOCKED             => Row_Status::MODULE_LOCKED,
			Switch_Result::DENIED             => Row_Status::MODULE_DENIED,
			Switch_Result::NEEDS_CONFIRMATION => Row_Status::MODULE_NEEDS_CONFIRMATION,
			default                           => Row_Status::MODULE_INVALID,
		};
	}

	/**
	 * Returns the message of a module row.
	 *
	 * @since 1.0.0
	 *
	 * @param Row_Status    $status Status.
	 * @param Switch_Result $result Result.
	 * @return string Translated message; empty for a switch and an unchanged module.
	 */
	private static function message( Row_Status $status, Switch_Result $result ): string {
		switch ( $status ) {
			case Row_Status::MODULE_LOCKED:
				return __( 'The child theme or a constant holds the state of this module.', 'creationell-wp-theme' );
			case Row_Status::MODULE_DENIED:
				return __( 'You are not allowed to switch modules.', 'creationell-wp-theme' );
			case Row_Status::MODULE_INVALID:
				return __( 'This module or state does not exist on this site.', 'creationell-wp-theme' );
			case Row_Status::MODULE_NEEDS_CONFIRMATION:
				$live = null === $result->scan ? 0 : $result->scan->total_live();
				return sprintf(
					/* translators: %d: number of live contents. */
					_n( 'Switching this module off affects %d live content; confirm the module changes.', 'Switching this module off affects %d live contents; confirm the module changes.', $live, 'creationell-wp-theme' ),
					$live
				);
			default:
				return '';
		}
	}
}
