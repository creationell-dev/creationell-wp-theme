<?php
/**
 * The one write path of the theme settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Checks and writes setting values for the settings pages, WP-CLI, the import and the abilities.
 *
 * Order of the checks, each ending with its Set_Status: unknown key; section
 * "modules" (only the module switcher writes switches); capability of the
 * definition for the user of the context; the Bootstrap line; child lock or
 * constant (the stored value stays); language "all"; untranslated key in a
 * secondary language; sanitizer; contrast over all colors with the candidates
 * (colors with the rule "contrast"); comparison with the next lower layer
 * in that language: the same value deletes the rows ("removed", or "unchanged" without
 * rows), another one writes them ("saved"). A dry run checks and writes nothing.
 * A reset takes the same path with the value of the next lower layer instead of
 * the sanitizer, so the contrast rules hold for resets in every channel too.
 *
 * Writes go to the rows at once; readers see them after commit(), which
 * rebuilds the touched snapshots once and fires creationell_wp_theme_settings_saved.
 *
 * Example:
 *
 *     $ctx    = new Write_Context( channel: 'cli', user_id: get_current_user_id() );
 *     $setter = Setter::instance();
 *     $result = $setter->set( 'footer_text', 'Hello', $ctx );
 *     $setter->commit();
 *     if ( ! $result->succeeded() ) {
 *         echo esc_html( $result->message );
 *     }
 *
 * @api
 * @since 1.0.0
 */
final class Setter {

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Row store.
	 *
	 * @var Option_Store
	 */
	private Option_Store $store;

	/**
	 * Sanitizer.
	 *
	 * @var Sanitizer
	 */
	private Sanitizer $sanitizer;

	/**
	 * Snapshot; null uses the shared one.
	 *
	 * @var Snapshot|null
	 */
	private ?Snapshot $snapshot;

	/**
	 * Values written since the last commit, by key and language code ("" for the default language); null for a deleted row.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $written = array();

	/**
	 * Language codes of the writes since the last commit.
	 *
	 * @var array<string, true>
	 */
	private array $langs = array();

	/**
	 * Context of the last write since the last commit.
	 *
	 * @var Write_Context|null
	 */
	private ?Write_Context $context = null;

	/**
	 * Takes store, sanitizer and snapshot; without them it builds its own and uses the shared snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @param Option_Store|null $store     Row store.
	 * @param Sanitizer|null    $sanitizer Sanitizer.
	 * @param Snapshot|null     $snapshot  Snapshot.
	 */
	public function __construct( ?Option_Store $store = null, ?Sanitizer $sanitizer = null, ?Snapshot $snapshot = null ) {
		$this->store     = $store ?? new Option_Store();
		$this->sanitizer = $sanitizer ?? new Sanitizer();
		$this->snapshot  = $snapshot;
	}

	/**
	 * Returns the shared instance.
	 *
	 * @since 1.0.0
	 *
	 * @return self Instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Replaces the shared instance; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $setter Instance.
	 * @return void
	 */
	public static function set_instance( ?self $setter ): void {
		self::$instance = $setter;
	}

	/**
	 * Sets one value.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $key   Setting key.
	 * @param mixed         $value Raw value.
	 * @param Write_Context $ctx   Context.
	 * @return Set_Result Result.
	 */
	public function set( string $key, mixed $value, Write_Context $ctx ): Set_Result {
		return $this->set_many( array( $key => $value ), $ctx )[0];
	}

	/**
	 * Sets several values; the contrast check sees all colors of the call together.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $values Raw values by key.
	 * @param Write_Context        $ctx    Context.
	 * @return array<int, Set_Result> Results in the order of the values.
	 */
	public function set_many( array $values, Write_Context $ctx ): array {
		$operations = array();
		foreach ( $values as $key => $value ) {
			$operations[ (string) $key ] = array(
				'reset' => false,
				'value' => $value,
			);
		}
		return $this->write( $operations, $ctx );
	}

	/**
	 * Removes the backend value of a key, so the next lower layer applies again.
	 *
	 * The same checks as for set() apply, the contrast check included: a reset that
	 * would break a contrast rule is refused and the row stays. reset_many() resets
	 * several colors together.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $key Setting key.
	 * @param Write_Context $ctx Context.
	 * @return Set_Result Result: "removed", "unchanged" without rows, or a refusal.
	 */
	public function reset( string $key, Write_Context $ctx ): Set_Result {
		return $this->reset_many( array( $key ), $ctx )[0];
	}

	/**
	 * Removes the backend values of several keys; the contrast check sees all colors of the call together.
	 *
	 * Resetting every color at once always passes the contrast check, because a rule
	 * whose two colors both come from the PHP layers is not checked (see contrast_failures()).
	 *
	 * Example:
	 *
	 *     $keys    = array_keys( array_filter( Registry::instance()->all(), static fn( Definition $d ): bool => 'contrast' === $d->a11y_rule ) );
	 *     $results = Setter::instance()->reset_many( $keys, $ctx );
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $keys Setting keys.
	 * @param Write_Context      $ctx  Context.
	 * @return array<int, Set_Result> Results in the order of the first appearance of each key.
	 */
	public function reset_many( array $keys, Write_Context $ctx ): array {
		$operations = array();
		foreach ( $keys as $key ) {
			$operations[ $key ] = array(
				'reset' => true,
				'value' => null,
			);
		}
		return $this->write( $operations, $ctx );
	}

	/**
	 * Rebuilds the snapshots of the written languages once and fires creationell_wp_theme_settings_saved.
	 *
	 * Does nothing when nothing was written since the last commit.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function commit(): void {
		if ( array() === $this->written || null === $this->context ) {
			return;
		}
		$keys          = array_keys( $this->written );
		$ctx           = $this->context;
		$langs         = array_keys( $this->langs );
		$this->written = array();
		$this->langs   = array();
		$this->context = null;
		$this->snapshot()->rebuild( $langs );

		/**
		 * Fires after the setter wrote settings and rebuilt the snapshots.
		 *
		 * @since 1.0.0
		 *
		 * @param array<int, string> $keys Keys written since the last commit, in the order of their first write.
		 * @param Write_Context      $ctx  Context of the last write.
		 */
		do_action( 'creationell_wp_theme_settings_saved', $keys, $ctx );
	}

	/**
	 * Returns the keys written since the last commit.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Keys in the order of their first write.
	 */
	public function pending(): array {
		return array_keys( $this->written );
	}

	/**
	 * Checks and writes sets and resets: a reset is a set to the value of the next lower layer.
	 *
	 * A reset without a row ends as "unchanged" before the contrast check, so it
	 * never fails on a rule the current values already break.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array{reset: bool, value: mixed}> $operations Operations by key.
	 * @param Write_Context                                   $ctx        Context.
	 * @return array<int, Set_Result> Results in the order of the operations.
	 */
	private function write( array $operations, Write_Context $ctx ): array {
		$results    = array();
		$candidates = array();
		foreach ( $operations as $key => $operation ) {
			$key        = (string) $key;
			$refusal    = $this->refusal( $key, $ctx );
			$definition = null === $refusal ? Registry::instance()->get( $key ) : null;
			if ( null === $definition ) {
				$results[ $key ] = $refusal;
				continue;
			}
			if ( $operation['reset'] ) {
				$secondary = Language::instance()->secondary( $ctx->lang );
				if ( ! $this->store->has( $definition, $secondary ) ) {
					$results[ $key ] = new Set_Result( $key, Set_Status::UNCHANGED, '', self::lang_data( $secondary ) );
					continue;
				}
				$candidates[ $key ] = $this->lower( $definition, $secondary );
				continue;
			}
			$clean = $this->sanitizer->clean( $definition, $operation['value'] );
			if ( ! $clean['valid'] ) {
				$results[ $key ] = new Set_Result( $key, Set_Status::INVALID, $clean['message'] );
				continue;
			}
			$candidates[ $key ] = $clean['value'];
		}

		// A refused color keeps its old value, so the others are checked again without it.
		do {
			$failures = $this->contrast_failures( $candidates );
			foreach ( $failures as $key => $failed ) {
				$results[ $key ] = new Set_Result( $key, Set_Status::CONTRAST, self::contrast_message( $failed ), array( 'failures' => $failed ) );
				unset( $candidates[ $key ] );
			}
		} while ( array() !== $failures );
		foreach ( $candidates as $key => $clean ) {
			$results[ $key ] = $this->apply( Registry::instance()->get( $key ), $clean, $ctx );
		}

		$ordered = array();
		foreach ( array_keys( $operations ) as $key ) {
			$result = $results[ (string) $key ] ?? null;
			if ( $result instanceof Set_Result ) {
				$ordered[] = $result;
			}
		}
		return $ordered;
	}

	/**
	 * Runs the checks that come before the value: key, section, capability, line, lock, language.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $key Setting key.
	 * @param Write_Context $ctx Context.
	 * @return Set_Result|null Refusal, or null when the value may be checked.
	 */
	private function refusal( string $key, Write_Context $ctx ): ?Set_Result {
		$registry = Registry::instance();
		if ( ! $registry->has( $key ) ) {
			return new Set_Result( $key, Set_Status::UNKNOWN_KEY, __( 'This setting does not exist.', 'creationell-wp-theme' ) );
		}
		$definition = $registry->get( $key );
		if ( 'modules' === $definition->section ) {
			return new Set_Result( $key, Set_Status::MODULE_SWITCH_ONLY, __( 'Modules are switched on the modules page or with the module commands.', 'creationell-wp-theme' ) );
		}
		if ( ! user_can( $ctx->user_id, $definition->capability ) ) {
			return new Set_Result( $key, Set_Status::FORBIDDEN, __( 'You are not allowed to change this setting.', 'creationell-wp-theme' ) );
		}
		if ( 'bootstrap_line' === $key ) {
			return new Set_Result( $key, Set_Status::LINE_SWITCH_UNAVAILABLE, __( 'The Bootstrap line cannot be switched in this theme version.', 'creationell-wp-theme' ) );
		}
		if ( Settings::instance()->is_locked( $key ) ) {
			return new Set_Result( $key, Set_Status::LOCKED, __( 'This setting is locked by the child theme or a constant.', 'creationell-wp-theme' ) );
		}
		if ( Language::ALL === $ctx->lang ) {
			return new Set_Result( $key, Set_Status::LANGUAGE_ALL_UNSUPPORTED, __( 'Choose one language; settings cannot be saved for all languages at once.', 'creationell-wp-theme' ) );
		}
		$secondary = Language::instance()->secondary( $ctx->lang );
		if ( null !== $secondary && ! $definition->translatable ) {
			return new Set_Result( $key, Set_Status::NOT_TRANSLATABLE, __( 'This setting is the same in every language; change it in the default language.', 'creationell-wp-theme' ) );
		}
		if ( null !== $secondary && ! Option_Store::valid_lang( $secondary ) ) {
			return new Set_Result( $key, Set_Status::INVALID, __( 'The language code is invalid.', 'creationell-wp-theme' ) );
		}
		return null;
	}

	/**
	 * Compares a clean value with the next lower layer and writes or deletes the rows.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition    $definition Definition.
	 * @param mixed         $clean      Clean value.
	 * @param Write_Context $ctx        Context.
	 * @return Set_Result Result: saved, removed or unchanged.
	 */
	private function apply( Definition $definition, mixed $clean, Write_Context $ctx ): Set_Result {
		$key       = $definition->key;
		$secondary = Language::instance()->secondary( $ctx->lang );
		$data      = self::lang_data( $secondary );
		$has_row   = $this->store->has( $definition, $secondary );

		if ( $clean === $this->lower( $definition, $secondary ) ) {
			if ( ! $has_row ) {
				return new Set_Result( $key, Set_Status::UNCHANGED, '', $data );
			}
			if ( ! $ctx->dry_run ) {
				$this->store->delete( $definition, $secondary );
				$this->remember( $key, $secondary, null, $ctx );
			}
			return new Set_Result( $key, Set_Status::REMOVED, '', $data );
		}

		if ( $has_row ) {
			$stored = $this->sanitizer->clean( $definition, $this->store->read( $definition, $secondary ) );
			if ( $stored['valid'] && $stored['value'] === $clean ) {
				return new Set_Result( $key, Set_Status::UNCHANGED, '', $data );
			}
		}
		if ( ! $ctx->dry_run ) {
			$this->store->write( $definition, $clean, $secondary );
			$this->remember( $key, $secondary, $clean, $ctx );
		}
		return new Set_Result( $key, Set_Status::SAVED, '', $data );
	}

	/**
	 * Returns the value of the next lower layer, counting the writes of this setter before commit.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string|null $secondary  Code of a secondary language, or null.
	 * @return mixed Value without a backend row in that language.
	 */
	private function lower( Definition $definition, ?string $secondary ): mixed {
		$settings = Settings::instance();
		if ( null === $secondary ) {
			return $settings->below_backend( $definition->key, Language::instance()->default() );
		}
		if ( array_key_exists( '', $this->written[ $definition->key ] ?? array() ) ) {
			return $this->written[ $definition->key ][''] ?? $settings->below_backend( $definition->key, Language::instance()->default() );
		}
		return $settings->below_backend( $definition->key, $secondary );
	}

	/**
	 * Returns the contrast failures per candidate color.
	 *
	 * The set holds every color with the rule "contrast": its current value,
	 * overwritten by the writes of this setter before commit and by the candidates.
	 * A rule whose two colors both come from the PHP layers after the write (registry
	 * or child default, child lock, constant; "white or black" counts as such) is
	 * skipped: the backend did not choose them, the doctor reports them, and
	 * returning to them stays possible.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $candidates Clean values by key.
	 * @return array<string, array<int, array{pair: array{fg: string, bg: string}, ratio: float, min: float, reason: string}>> Failures by candidate key.
	 */
	private function contrast_failures( array $candidates ): array {
		$settings = Settings::instance();
		$default  = Language::instance()->default();
		$checked  = array();
		$colors   = array();
		$php      = array( Contrast_Rules::BLACK_OR_WHITE => true );
		foreach ( Registry::instance()->all() as $key => $definition ) {
			if ( 'contrast' !== $definition->a11y_rule ) {
				continue;
			}
			if ( array_key_exists( $key, $candidates ) ) {
				$checked[]      = $key;
				$colors[ $key ] = $candidates[ $key ];
				$php[ $key ]    = $candidates[ $key ] === $settings->below_backend( $key, $default );
				continue;
			}
			$current        = $this->current_color( $definition );
			$colors[ $key ] = $current['value'];
			$php[ $key ]    = $current['php'];
		}
		if ( array() === $checked ) {
			return array();
		}
		$failures = array();
		foreach ( Contrast_Rules::check( $colors ) as $failure ) {
			$sides = array( $failure['pair']['fg'], $failure['pair']['bg'] );
			if ( ( $php[ $sides[0] ] ?? false ) && ( $php[ $sides[1] ] ?? false ) ) {
				continue;
			}
			foreach ( $sides as $key ) {
				if ( in_array( $key, $checked, true ) ) {
					$failures[ $key ][] = $failure;
				}
			}
		}
		return $failures;
	}

	/**
	 * Returns the current value of a color, counting the writes of this setter before commit.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @return array{value: mixed, php: bool} Color, and whether it comes from a PHP layer instead of a backend row.
	 */
	private function current_color( Definition $definition ): array {
		$settings = Settings::instance();
		$key      = $definition->key;
		if ( ! $settings->is_locked( $key ) && array_key_exists( '', $this->written[ $key ] ?? array() ) ) {
			$written = $this->written[ $key ][''];
			return array(
				'value' => $written ?? $settings->below_backend( $key ),
				'php'   => null === $written,
			);
		}
		$default = Language::instance()->default();
		return array(
			'value' => $settings->get( $key, $default ),
			'php'   => Settings::ORIGIN_BACKEND !== $settings->origin( $key, $default ),
		);
	}

	/**
	 * Remembers a write for commit().
	 *
	 * @since 1.0.0
	 *
	 * @param string        $key       Key.
	 * @param string|null   $secondary Code of a secondary language, or null.
	 * @param mixed         $value     Written value, null for deleted rows.
	 * @param Write_Context $ctx       Context.
	 * @return void
	 */
	private function remember( string $key, ?string $secondary, mixed $value, Write_Context $ctx ): void {
		$this->written[ $key ][ $secondary ?? '' ]                    = $value;
		$this->langs[ $secondary ?? Language::instance()->default() ] = true;
		$this->context = $ctx;
	}

	/**
	 * Returns the snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @return Snapshot Snapshot.
	 */
	private function snapshot(): Snapshot {
		return $this->snapshot ?? Snapshot::instance();
	}

	/**
	 * Returns the details of a write in a language.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $secondary Code of a secondary language, or null.
	 * @return array<string, mixed> "lang" for a secondary language, otherwise empty.
	 */
	private static function lang_data( ?string $secondary ): array {
		return null === $secondary ? array() : array( 'lang' => $secondary );
	}

	/**
	 * Builds the message of a contrast refusal: pairs with their ratio and minimum.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array{pair: array{fg: string, bg: string}, ratio: float, min: float, reason: string}> $failures Failures.
	 * @return string Translated message.
	 */
	private static function contrast_message( array $failures ): string {
		$registry = Registry::instance();
		$parts    = array();
		foreach ( $failures as $failure ) {
			$names = array();
			foreach ( array( $failure['pair']['fg'], $failure['pair']['bg'] ) as $side ) {
				if ( Contrast_Rules::BLACK_OR_WHITE === $side ) {
					$names[] = __( 'white or black text', 'creationell-wp-theme' );
					continue;
				}
				$label   = $registry->has( $side ) ? $registry->get( $side )->label_text() : '';
				$names[] = '' !== $label ? $label : $side;
			}
			$parts[] = sprintf(
				/* translators: 1: foreground color name, 2: background color name, 3: contrast ratio, 4: minimum ratio. */
				__( '%1$s on %2$s has a contrast of %3$s:1, at least %4$s:1 is needed.', 'creationell-wp-theme' ),
				$names[0],
				$names[1],
				number_format( floor( $failure['ratio'] * 100 ) / 100, 2, '.', '' ),
				rtrim( rtrim( number_format( $failure['min'], 2, '.', '' ), '0' ), '.' )
			);
		}
		return implode( ' ', $parts );
	}
}
