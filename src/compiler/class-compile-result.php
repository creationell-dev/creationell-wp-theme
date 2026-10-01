<?php
/**
 * Output of a stylesheet compilation.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Written files, fingerprint, compiler messages or the error of one compilation.
 *
 * @since 1.0.0
 */
final class Compile_Result {

	/**
	 * Sets the output of the compilation.
	 *
	 * @since 1.0.0
	 *
	 * @param bool                  $ok          True when every requested file was written.
	 * @param array<string, string> $files       Direction and absolute path of the written file.
	 * @param string                $fingerprint SHA-256 of the inputs, see Fingerprint::compute(); empty when the request was invalid.
	 * @param array<int, string>    $messages    Warnings and deprecations of the SCSS compiler.
	 * @param string|null           $error       Reason of the failure, null on success.
	 * @param float                 $seconds     Duration of the compilation.
	 * @param int                   $peak_bytes  Peak memory of the PHP process.
	 */
	public function __construct(
		public readonly bool $ok,
		public readonly array $files,
		public readonly string $fingerprint,
		public readonly array $messages,
		public readonly ?string $error,
		public readonly float $seconds,
		public readonly int $peak_bytes
	) {
	}
}
