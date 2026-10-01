<?php
/**
 * Input of a stylesheet compilation.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Entry file, import paths, variables, target and directions of one compilation.
 *
 * The compiler writes <target_dir>/<target_basename>.min.css for "ltr" and
 * <target_dir>/<target_basename>-rtl.min.css for "rtl".
 *
 * @since 1.0.0
 */
final class Compile_Request {

	/**
	 * Sets the input of the compilation.
	 *
	 * @since 1.0.0
	 *
	 * @param string                    $entry_file      Absolute path of the SCSS entry file.
	 * @param array<int, string>        $import_paths    Absolute import folders, searched in this order.
	 * @param array<string, Scss_Value> $variables       SCSS variables without "$", they replace the defaults.
	 * @param string                    $target_dir      Absolute folder of the output files.
	 * @param string                    $target_basename File name without ".min.css", letters, digits and hyphens.
	 * @param array<int, string>        $directions      Directions "ltr" and "rtl" to build.
	 */
	public function __construct(
		public readonly string $entry_file,
		public readonly array $import_paths,
		public readonly array $variables,
		public readonly string $target_dir,
		public readonly string $target_basename,
		public readonly array $directions = array( 'ltr', 'rtl' )
	) {
	}
}
