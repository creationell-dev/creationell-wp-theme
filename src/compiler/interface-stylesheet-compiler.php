<?php
/**
 * Interface of the stylesheet compilers of the Bootstrap lines.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compiles the theme stylesheet of a Bootstrap line from SCSS to minified CSS.
 *
 * Get an instance from Compiler_Factory::for_line(); the classes of a line and the
 * scoped libraries are loaded only then, never on a normal request.
 *
 * @since 1.0.0
 */
interface Stylesheet_Compiler_Interface {

	/**
	 * Returns the Bootstrap lines this compiler builds.
	 *
	 * @since 1.0.0
	 *
	 * @return list<int> Bootstrap lines.
	 */
	public function supported_lines(): array;

	/**
	 * Compiles a request and writes one file per direction into its target folder.
	 *
	 * Errors do not throw; the result carries them, and existing files stay untouched.
	 *
	 * @since 1.0.0
	 *
	 * @param Compile_Request $request What to compile and where to write it.
	 * @return Compile_Result Files, fingerprint, messages or error.
	 */
	public function compile( Compile_Request $request ): Compile_Result;

	/**
	 * Returns the exact versions of the components that shape the output.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Component name and exact version, an input of the fingerprint.
	 */
	public function versions(): array;
}
