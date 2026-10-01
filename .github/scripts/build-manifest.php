<?php
/**
 * Release check and manifest build of the creationell theme: tag, version places,
 * requirements, changelog, Bootstrap lines, the version and line rule against the
 * published manifest, manifest v1 and the details page.
 *
 * Usage:
 *   php build-manifest.php check --theme=<dir> --tag=<tag> [--previous=<file|https-url|none>]
 *   php build-manifest.php build --theme=<dir> --tag=<tag> --zip=<zip> --out=<dir>
 *
 * --theme=<dir>     theme folder with style.css, README.md and phpdoc.xml
 * --tag=<tag>       release tag vX.Y.Z (digits only, no pre-release)
 * --previous=<src>  published manifest: a JSON file, an https URL (15 s, at most
 *                   1 MiB; 404 means first publication) or "none"
 * --zip=<zip>       release ZIP; an existing <zip>.sha256 must hold its checksum
 * --out=<dir>       pages folder: theme_creationell-wp-theme.json and index.html
 *
 * "build" runs "check" without a previous manifest, then writes the manifest (at
 * most 512 KiB) and the details page. last_updated comes from SOURCE_DATE_EPOCH
 * (default 315532800 = 1980-01-01 with a notice).
 *
 * The script runs without Composer and without any other file of the repository
 * (PHP 8.3 core only), so the public repository can run it as it is. Tests load it
 * with require_once; main() runs only when the script is called directly.
 * Exit codes: 0 ok, 1 finding, 2 usage, read, write or network error.
 *
 * @package Creationell\WpTheme\Dev
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Release;

use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Release version X.Y.Z: digits only, no leading zeros, no pre-release or build part.
 */
const VERSION_PATTERN = '~^(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)$~';

/**
 * Bootstrap lines a package can carry.
 */
const BOOTSTRAP_LINES = array( 5, 6 );

/**
 * Size limit of a previous manifest in bytes (1 MiB).
 */
const PREVIOUS_MAX_BYTES = 1048576;

/**
 * Theme slug: root folder of the ZIP and part of every public name.
 */
const SLUG = 'creationell-wp-theme';

/**
 * File name of the release ZIP.
 */
const ZIP_NAME = 'creationell-wp-theme.zip';

/**
 * File name of the manifest on the details site.
 */
const MANIFEST_FILE = 'theme_creationell-wp-theme.json';

/**
 * Prefix of every download URL: the release assets of the public repository.
 */
const PACKAGE_URL_PREFIX = 'https://github.com/creationell-dev/creationell-wp-theme/releases/download/';

/**
 * Details page of the theme; also the Update URI of style.css.
 */
const UPDATE_URI = 'https://creationell-dev.github.io/creationell-wp-theme/';

/**
 * Size limit of the manifest in bytes (512 KiB).
 */
const MANIFEST_MAX_BYTES = 524288;

/**
 * Number of changelog entries in the manifest.
 */
const CHANGELOG_ENTRIES = 5;

/**
 * Earliest SOURCE_DATE_EPOCH and its default: 1980-01-01, the first ZIP date.
 */
const EPOCH_DEFAULT = 315532800;

/**
 * Flags of the manifest JSON.
 */
const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

/**
 * Reads the release metadata of a theme folder.
 *
 * The style.css headers are read like WordPress does (first 8 KiB, case-insensitive
 * field names). A missing value is an empty string. README.md and phpdoc.xml may be
 * missing; their values are empty then.
 *
 * Example:
 *
 *     $meta = read_theme_meta( 'creationell-wp-theme' );
 *     echo $meta['version'], ' ', $meta['changelog'][0]['version'];
 *
 * @param string $theme_dir Theme folder.
 * @return array{name: string, version: string, requires: string, tested: string, requires_php: string, readme: array{version: string, requires: string, tested: string, requires_php: string}, readme_markdown: string, phpdoc_version: string, changelog: list<array{version: string, markdown: string}>} Metadata.
 * @throws RuntimeException When style.css cannot be read.
 */
function read_theme_meta( string $theme_dir ): array {
	$style_file = $theme_dir . '/style.css';
	$style      = is_file( $style_file ) ? file_get_contents( $style_file, false, null, 0, 8192 ) : false;
	if ( false === $style ) {
		throw new RuntimeException( 'Cannot read ' . $style_file );
	}
	$style  = str_replace( "\r", "\n", $style );
	$header = static function ( string $field ) use ( $style ): string {
		if ( 1 !== preg_match( '/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote( $field, '/' ) . ':(.*)$/mi', $style, $matches ) ) {
			return '';
		}
		return trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $matches[1] ) ?? '' );
	};

	$readme_file = $theme_dir . '/README.md';
	$readme      = is_file( $readme_file ) ? file_get_contents( $readme_file ) : false;
	$readme      = false === $readme ? '' : str_replace( "\r\n", "\n", $readme );
	$readme_line = static function ( string $label ) use ( $readme ): string {
		return 1 === preg_match( '~^\*\*' . preg_quote( $label, '~' ) . ':\*\*[ \t]*(\S+)[ \t]*$~m', $readme, $matches ) ? $matches[1] : '';
	};

	$phpdoc_file = $theme_dir . '/phpdoc.xml';
	$phpdoc      = is_file( $phpdoc_file ) ? file_get_contents( $phpdoc_file ) : false;
	$phpdoc      = false === $phpdoc ? '' : $phpdoc;

	$versions = array();
	$bodies   = array();
	$inside   = false;
	$fenced   = false;
	foreach ( explode( "\n", $readme ) as $line ) {
		if ( ! $fenced ) {
			if ( 1 === preg_match( '~^##[ \t]+Changelog[ \t]*$~', $line ) ) {
				$inside = true;
				continue;
			}
			if ( $inside && 1 === preg_match( '~^#{1,2}(?:[ \t]|$)~', $line ) ) {
				$inside = false;
				continue;
			}
			if ( $inside && 1 === preg_match( '~^###[ \t]+(\S+)~', $line, $matches ) ) {
				$versions[] = $matches[1];
				$bodies[]   = '';
				continue;
			}
		}
		if ( 1 === preg_match( '/^\s*(?:```|~~~)/', $line ) ) {
			$fenced = ! $fenced;
		}
		if ( $inside && array() !== $bodies ) {
			$bodies[ count( $bodies ) - 1 ] .= $line . "\n";
		}
	}
	$changelog = array();
	foreach ( $versions as $index => $entry_version ) {
		$markdown    = trim( $bodies[ $index ], "\n" );
		$changelog[] = array(
			'version'  => $entry_version,
			'markdown' => '' === $markdown ? '' : $markdown . "\n",
		);
	}

	return array(
		'name'            => $header( 'Theme Name' ),
		'version'         => $header( 'Version' ),
		'requires'        => $header( 'Requires at least' ),
		'tested'          => $header( 'Tested up to' ),
		'requires_php'    => $header( 'Requires PHP' ),
		'readme'          => array(
			'version'      => $readme_line( 'Version' ),
			'requires'     => $readme_line( 'Requires at least' ),
			'tested'       => $readme_line( 'Tested up to' ),
			'requires_php' => $readme_line( 'Requires PHP' ),
		),
		'readme_markdown' => $readme,
		'phpdoc_version'  => 1 === preg_match( '~<version\s+number="([^"]+)"~', $phpdoc, $matches ) ? $matches[1] : '',
		'changelog'       => $changelog,
	);
}

/**
 * Checks a theme folder for a release with the given tag.
 *
 * Rules: (1) the tag is vX.Y.Z and equals the version of style.css, README.md and
 * phpdoc.xml; (2) style.css has the manifest headers and the README requirement
 * lines, where present, equal them; (3) the first changelog entry is the version;
 * (4) the Bootstrap lines follow from the package content; (5) the version and line
 * rule holds against the published manifest.
 *
 * @param string                    $theme_dir Theme folder.
 * @param string                    $tag       Release tag.
 * @param array<string, mixed>|null $previous  Decoded published manifest, or null for none.
 * @return list<string> Findings; empty when the release may be published.
 * @throws RuntimeException When style.css cannot be read or the previous manifest has no valid version and lines.
 */
function check_release( string $theme_dir, string $tag, ?array $previous = null ): array {
	$previous_release = null;
	if ( null !== $previous ) {
		$version = $previous['version'] ?? null;
		$raw     = $previous['bootstrap_lines'] ?? null;
		$lines   = array();
		if ( is_array( $raw ) && array_is_list( $raw ) ) {
			foreach ( $raw as $line ) {
				if ( ! is_int( $line ) ) {
					$lines = array();
					break;
				}
				$lines[] = $line;
			}
		}
		if ( ! is_string( $version ) || 1 !== preg_match( VERSION_PATTERN, $version ) || array() === $lines ) {
			throw new RuntimeException( 'The previous manifest has no valid "version" (X.Y.Z) and "bootstrap_lines" (list of integers).' );
		}
		sort( $lines );
		$previous_release = array(
			'version'         => $version,
			'bootstrap_lines' => $lines,
		);
	}

	$meta     = read_theme_meta( $theme_dir );
	$version  = $meta['version'];
	$findings = array();
	$headers  = array(
		'Theme Name'        => $meta['name'],
		'Version'           => $version,
		'Requires at least' => $meta['requires'],
		'Tested up to'      => $meta['tested'],
		'Requires PHP'      => $meta['requires_php'],
	);
	foreach ( $headers as $field => $value ) {
		if ( '' === $value ) {
			$findings[] = sprintf( 'style.css: header "%s" missing or empty', $field );
		}
	}

	$tag_version = 1 === preg_match( VERSION_PATTERN, substr( $tag, 1 ) ) && str_starts_with( $tag, 'v' ) ? substr( $tag, 1 ) : '';
	if ( '' === $tag_version ) {
		$findings[] = sprintf( 'tag "%s" does not match vX.Y.Z (digits only, no pre-release)', $tag );
	} elseif ( '' !== $version && $version !== $tag_version ) {
		$findings[] = sprintf( 'style.css: Version %s differs from tag %s', $version, $tag );
	}
	$reference = '' !== $tag_version ? 'tag ' . $tag : 'style.css ' . $version;
	$expected  = '' !== $tag_version ? $tag_version : $version;

	$has_readme = is_file( $theme_dir . '/README.md' );
	if ( ! $has_readme ) {
		$findings[] = 'README.md: file not found';
	} elseif ( '' === $meta['readme']['version'] ) {
		$findings[] = 'README.md: line **Version:** not found';
	} elseif ( '' !== $expected && $meta['readme']['version'] !== $expected ) {
		$findings[] = sprintf( 'README.md: **Version:** %s differs from %s', $meta['readme']['version'], $reference );
	}
	if ( '' === $meta['phpdoc_version'] ) {
		$findings[] = 'phpdoc.xml: version not found';
	} elseif ( '' !== $expected && $meta['phpdoc_version'] !== $expected ) {
		$findings[] = sprintf( 'phpdoc.xml: version %s differs from %s', $meta['phpdoc_version'], $reference );
	}

	$requirements = array(
		'requires'     => 'Requires at least',
		'tested'       => 'Tested up to',
		'requires_php' => 'Requires PHP',
	);
	foreach ( $requirements as $key => $label ) {
		$readme_value = $meta['readme'][ $key ];
		if ( '' !== $readme_value && '' !== $meta[ $key ] && $readme_value !== $meta[ $key ] ) {
			$findings[] = sprintf( 'README.md: **%s:** %s differs from style.css %s', $label, $readme_value, $meta[ $key ] );
		}
	}

	if ( $has_readme && array() === $meta['changelog'] ) {
		$findings[] = 'README.md: no "## Changelog" section with a "### X.Y.Z" entry';
	} elseif ( $has_readme && '' !== $version && $meta['changelog'][0]['version'] !== $version ) {
		$findings[] = sprintf( 'README.md: first changelog entry is %s, expected ### %s', $meta['changelog'][0]['version'], $version );
	}

	$package  = package_lines( $theme_dir );
	$findings = array_merge( $findings, $package['findings'] );

	if ( null !== $previous_release && array() !== $package['lines'] && 1 === preg_match( VERSION_PATTERN, $version ) ) {
		$rule = line_rule(
			$previous_release,
			array(
				'version'         => $version,
				'bootstrap_lines' => $package['lines'],
			)
		);
		if ( null !== $rule ) {
			$findings[] = $rule;
		}
	}
	return $findings;
}

/**
 * Derives the Bootstrap lines from the package content.
 *
 * Line n ships when assets/css/bootstrap-<n>/theme.min.css and at least one file in
 * assets/vendor/bootstrap-<n>/ exist. Only one of the two is a finding, and so is a
 * package without any line.
 *
 * @param string $theme_dir Theme or staging folder.
 * @return array{lines: list<int>, findings: list<string>} Lines in ascending order and findings.
 */
function package_lines( string $theme_dir ): array {
	$lines    = array();
	$findings = array();
	foreach ( BOOTSTRAP_LINES as $line ) {
		$css    = 'assets/css/bootstrap-' . $line . '/theme.min.css';
		$vendor = 'assets/vendor/bootstrap-' . $line . '/';
		$has    = array(
			'css'    => is_file( $theme_dir . '/' . $css ),
			'vendor' => false,
		);
		if ( is_dir( $theme_dir . '/' . $vendor ) ) {
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $theme_dir . '/' . $vendor, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $iterator as $ignored ) {
				$has['vendor'] = true;
				break;
			}
		}
		if ( $has['css'] && $has['vendor'] ) {
			$lines[] = $line;
		} elseif ( $has['css'] ) {
			$findings[] = sprintf( 'line %d: %s without %s', $line, $css, $vendor );
		} elseif ( $has['vendor'] ) {
			$findings[] = sprintf( 'line %d: %s without %s', $line, $vendor, $css );
		}
	}
	if ( array() === $lines ) {
		$findings[] = 'no bootstrap line: the package needs assets/css/bootstrap-<n>/theme.min.css and assets/vendor/bootstrap-<n>/ for n = ' . implode( ' or ', BOOTSTRAP_LINES );
	}
	return array(
		'lines'    => $lines,
		'findings' => $findings,
	);
}

/**
 * Applies the version and line rule of a new release against the published one.
 *
 * A version must not be older than the published one; the same version must carry
 * the same lines (re-sync); removing a line needs a new major version; adding a line
 * needs at least a new minor version.
 *
 * Example:
 *
 *     line_rule( array( 'version' => '1.0.0', 'bootstrap_lines' => array( 5 ) ), array( 'version' => '1.0.1', 'bootstrap_lines' => array( 5, 6 ) ) );
 *     // "line 6 added in a patch release (1.0.0 -> 1.0.1); use a new minor or major version"
 *
 * @param array{version: string, bootstrap_lines: array<int, int>}|null $prev Published release, or null for the first publication.
 * @param array{version: string, bootstrap_lines: array<int, int>}      $next New release; both versions X.Y.Z.
 * @return string|null Finding, or null when the rule holds.
 */
function line_rule( ?array $prev, array $next ): ?string {
	if ( null === $prev ) {
		return null;
	}
	$old_lines = $prev['bootstrap_lines'];
	$new_lines = $next['bootstrap_lines'];
	sort( $old_lines );
	sort( $new_lines );
	if ( version_compare( $next['version'], $prev['version'], '<' ) ) {
		return sprintf( 'version %s is older than published %s', $next['version'], $prev['version'] );
	}
	if ( version_compare( $next['version'], $prev['version'], '==' ) ) {
		if ( $old_lines === $new_lines ) {
			return null;
		}
		return sprintf( 'version %s is published with lines [%s]; the same version cannot ship lines [%s]', $next['version'], implode( ',', $old_lines ), implode( ',', $new_lines ) );
	}
	$old     = array_map( 'intval', explode( '.', $prev['version'] ) );
	$new     = array_map( 'intval', explode( '.', $next['version'] ) );
	$step    = sprintf( '(%s -> %s)', $prev['version'], $next['version'] );
	$removed = array_values( array_diff( $old_lines, $new_lines ) );
	if ( array() !== $removed && $old[0] === $new[0] ) {
		return sprintf( '%s %s removed without a new major version %s', 1 === count( $removed ) ? 'line' : 'lines', implode( ', ', $removed ), $step );
	}
	$added = array_values( array_diff( $new_lines, $old_lines ) );
	if ( array() !== $added && $old[0] === $new[0] && ( $old[1] ?? 0 ) === ( $new[1] ?? 0 ) ) {
		return sprintf( '%s %s added in a patch release %s; use a new minor or major version', 1 === count( $added ) ? 'line' : 'lines', implode( ', ', $added ), $step );
	}
	return null;
}

/**
 * Builds manifest v1 of a release.
 *
 * The theme folder must pass check_release() for the tag; the values come from
 * style.css (name, version, requirements), the tag (download URL), the ZIP
 * checksum, SOURCE_DATE_EPOCH (last_updated), the five newest changelog entries
 * of README.md and the package content (Bootstrap lines).
 *
 * Example:
 *
 *     $manifest = build_manifest( 'creationell-wp-theme', 'v1.2.0', hash_file( 'sha256', 'creationell-wp-theme.zip' ), time() );
 *     echo $manifest['download_url'];
 *     // https://github.com/creationell-dev/creationell-wp-theme/releases/download/v1.2.0/creationell-wp-theme.zip
 *
 * @param string $theme_dir Theme folder.
 * @param string $tag       Release tag vX.Y.Z.
 * @param string $sha256    SHA-256 of the ZIP, 64 lowercase hex digits.
 * @param int    $epoch     Release time as Unix time.
 * @return array{name: string, slug: string, version: string, download_url: string, checksum_sha256: string, requires: string, requires_php: string, tested: string, details_url: string, last_updated: string, changelog: string, bootstrap_lines: list<int>} Manifest in key order.
 * @throws RuntimeException When style.css cannot be read.
 */
function build_manifest( string $theme_dir, string $tag, string $sha256, int $epoch ): array {
	$meta      = read_theme_meta( $theme_dir );
	$changelog = '';
	foreach ( array_slice( $meta['changelog'], 0, CHANGELOG_ENTRIES ) as $entry ) {
		$changelog .= '### ' . $entry['version'] . "\n\n" . $entry['markdown'] . "\n";
	}
	return array(
		'name'            => $meta['name'],
		'slug'            => SLUG,
		'version'         => $meta['version'],
		'download_url'    => PACKAGE_URL_PREFIX . $tag . '/' . ZIP_NAME,
		'checksum_sha256' => strtolower( $sha256 ),
		'requires'        => $meta['requires'],
		'requires_php'    => $meta['requires_php'],
		'tested'          => $meta['tested'],
		'details_url'     => UPDATE_URI,
		'last_updated'    => gmdate( 'Y-m-d\TH:i:s\Z', $epoch ),
		'changelog'       => markdown_lite( $changelog ),
		'bootstrap_lines' => package_lines( $theme_dir )['lines'],
	);
}

/**
 * Renders the files of the details site: the manifest and index.html.
 *
 * The page index.html is English, has one h1, a main landmark, a table of the manifest
 * values with the download link and the README (headings one level down); its
 * CSS is embedded, and it loads no script and no external resource.
 *
 * Example:
 *
 *     foreach ( render_pages( $manifest, file_get_contents( 'creationell-wp-theme/README.md' ) ) as $name => $content ) {
 *         file_put_contents( 'out/pages/' . $name, $content );
 *     }
 *
 * @param array<string, mixed> $manifest        Manifest from build_manifest().
 * @param string               $readme_markdown README.md of the theme.
 * @return array<string, string> File name => content: the manifest file, then index.html.
 * @throws JsonException When the manifest cannot be encoded.
 */
function render_pages( array $manifest, string $readme_markdown ): array {
	$escape = static function ( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	};
	$value  = static function ( string $key ) use ( $manifest, $escape ): string {
		$raw = $manifest[ $key ] ?? '';
		if ( is_array( $raw ) ) {
			$raw = implode( ', ', array_filter( $raw, 'is_scalar' ) );
		}
		return is_scalar( $raw ) ? $escape( strval( $raw ) ) : '';
	};
	$rows   = array(
		'Theme'             => $value( 'name' ),
		'Version'           => $value( 'version' ),
		'Download'          => '<a href="' . $value( 'download_url' ) . '">' . $escape( ZIP_NAME ) . '</a>',
		'SHA-256'           => '<code>' . $value( 'checksum_sha256' ) . '</code>',
		'Requires at least' => 'WordPress ' . $value( 'requires' ),
		'Tested up to'      => 'WordPress ' . $value( 'tested' ),
		'Requires PHP'      => $value( 'requires_php' ),
		'Bootstrap lines'   => $value( 'bootstrap_lines' ),
		'Last updated'      => $value( 'last_updated' ),
		'Manifest'          => '<a href="' . $escape( MANIFEST_FILE ) . '">' . $escape( MANIFEST_FILE ) . '</a>',
	);
	$table  = '';
	foreach ( $rows as $label => $cell ) {
		$table .= '<tr><th scope="row">' . $escape( $label ) . '</th><td>' . $cell . "</td></tr>\n";
	}
	$title = 'creationell Theme ' . $value( 'version' );
	$html  = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$title}</title>
<style>
body {
	margin: 0;
	background: #fff;
	color: #1a1a1a;
	font: 1rem/1.6 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
}
main {
	max-width: 48rem;
	margin: 0 auto;
	padding: 1.5rem 1rem 3rem;
}
a {
	color: #0b57d0;
	text-decoration: underline;
}
a:focus,
a:focus-visible {
	outline: 2px solid #0b57d0;
	outline-offset: 2px;
}
table {
	border-collapse: collapse;
	width: 100%;
	margin: 1rem 0 2rem;
}
th,
td {
	border: 1px solid #767676;
	padding: 0.4rem 0.6rem;
	text-align: left;
	vertical-align: top;
}
code,
pre {
	font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
	font-size: 0.9em;
	background: #f3f3f3;
}
pre {
	padding: 0.75rem;
	white-space: pre-wrap;
	overflow-wrap: anywhere;
}
td code {
	word-break: break-all;
}
</style>
</head>
<body>
<main>
<h1>{$title}</h1>
<h2>Download</h2>
<table>
{$table}</table>

HTML;
	return array(
		MANIFEST_FILE => json_encode( $manifest, JSON_FLAGS | JSON_THROW_ON_ERROR ) . "\n",
		'index.html'  => $html . markdown_lite( $readme_markdown, 1 ) . "</main>\n</body>\n</html>\n",
	);
}

/**
 * Converts a small Markdown subset to HTML and escapes everything else.
 *
 * Known: headings # to #### (with an id), paragraphs, one-level lists (-, *, +
 * or 1. and 1); an ordered list keeps its start number, and only an item numbered
 * 1 interrupts a paragraph), **bold**, `code`, fenced code blocks (``` or ~~~) and
 * links to https:// URLs and #anchors; an image ![x](https://...) renders as "!"
 * and a link. Any other text, raw HTML and other links stay text and are escaped
 * with htmlspecialchars( ENT_QUOTES | ENT_SUBSTITUTE ).
 *
 * Example:
 *
 *     echo markdown_lite( "## Notes\n\n- See [docs](https://example.org/).", 1 );
 *     // <h3 id="notes">Notes</h3>
 *     // <ul>
 *     // <li>See <a href="https://example.org/">docs</a>.</li>
 *     // </ul>
 *
 * @param string $markdown       Markdown text.
 * @param int    $heading_offset Levels added to every heading; the result stops at h6.
 * @return string HTML, one block per line group, ending with LF; empty for empty input.
 */
function markdown_lite( string $markdown, int $heading_offset = 0 ): string {
	$escape = static function ( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	};
	$inline = null;
	$inline = static function ( string $text ) use ( &$inline, $escape ): string {
		$pattern = '~`([^`\n]+)`|\[([^\]\n]+)\]\(([^()\s]+)\)|\*\*(?=\S)(.+?)(?<=\S)\*\*~';
		if ( false === preg_match_all( $pattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL ) ) {
			return $escape( $text );
		}
		$html   = '';
		$offset = 0;
		foreach ( $matches as $match ) {
			$whole  = $match[0][0] ?? '';
			$html  .= $escape( substr( $text, $offset, $match[0][1] - $offset ) );
			$offset = $match[0][1] + strlen( $whole );
			if ( null !== $match[1][0] ) {
				$html .= '<code>' . $escape( $match[1][0] ) . '</code>';
			} elseif ( null !== $match[2][0] && null !== $match[3][0] ) {
				$html .= 1 === preg_match( '~^(?:https://[^\s"\'<>\\\\`]+|#[A-Za-z0-9][A-Za-z0-9_-]*)$~', $match[3][0] )
					? '<a href="' . $escape( $match[3][0] ) . '">' . $inline( $match[2][0] ) . '</a>'
					: $escape( $whole );
			} elseif ( null !== ( $match[4][0] ?? null ) ) {
				$html .= '<strong>' . $inline( $match[4][0] ) . '</strong>';
			}
		}
		return $html . $escape( substr( $text, $offset ) );
	};

	// First pass: group the lines into blocks. $current is the paragraph or list that
	// further lines may extend; a blank line ends a paragraph and pauses a list.
	// "level" is the heading level, or the start number of an ordered list.
	$blocks  = array();
	$current = null;
	$open    = false;
	$lines   = explode( "\n", str_replace( array( "\r\n", "\r" ), "\n", $markdown ) );
	$count   = count( $lines );
	for ( $index = 0; $index < $count; $index++ ) {
		$line = rtrim( $lines[ $index ] );
		if ( '' === trim( $line ) ) {
			$open = false;
			continue;
		}
		$block = null;
		if ( 1 === preg_match( '/^ {0,3}(`{3,}|~{3,})/', $line, $matches ) ) {
			$fence = $matches[1];
			$code  = array();
			++$index;
			while ( $index < $count ) {
				$candidate = rtrim( $lines[ $index ] );
				if ( str_starts_with( ltrim( $candidate ), $fence ) && '' === trim( $candidate, " \t" . $fence[0] ) ) {
					break;
				}
				$code[] = $candidate . "\n";
				++$index;
			}
			$block = array(
				'type'  => 'pre',
				'level' => 0,
				'lines' => $code,
			);
		} elseif ( 1 === preg_match( '~^(#{1,4})[ \t]+(.*?)(?:[ \t]+#+)?$~', $line, $matches ) ) {
			$block = array(
				'type'  => 'h',
				'level' => min( 6, strlen( $matches[1] ) + $heading_offset ),
				'lines' => array( $matches[2] ),
			);
		} elseif (
			1 === preg_match( '~^([-*+]|([0-9]{1,9})[.)])[ \t]+(.*)$~', $line, $matches )
			// Like CommonMark, only an ordered item starting at 1 interrupts a paragraph.
			&& ! ( $open && null !== $current && 'p' === $current['type'] && '' !== $matches[2] && 1 !== intval( $matches[2] ) )
		) {
			$tag  = '' === $matches[2] ? 'ul' : 'ol';
			$open = true;
			if ( null !== $current && $tag === $current['type'] ) {
				$current['lines'][] = $matches[3];
				continue;
			}
			if ( null !== $current ) {
				$blocks[] = $current;
			}
			$current = array(
				'type'  => $tag,
				'level' => 'ol' === $tag ? intval( $matches[2] ) : 0,
				'lines' => array( $matches[3] ),
			);
			continue;
		} elseif ( $open && null !== $current ) {
			if ( 'p' === $current['type'] ) {
				$current['lines'][] = trim( $line );
			} else {
				$current['lines'][] = array_pop( $current['lines'] ) . "\n" . trim( $line );
			}
			continue;
		}

		if ( null !== $current ) {
			$blocks[] = $current;
		}
		$current = null;
		$open    = false;
		if ( null !== $block ) {
			$blocks[] = $block;
			continue;
		}
		$current = array(
			'type'  => 'p',
			'level' => 0,
			'lines' => array( trim( $line ) ),
		);
		$open    = true;
	}
	if ( null !== $current ) {
		$blocks[] = $current;
	}

	// Second pass: render the blocks.
	$html = array();
	$ids  = array();
	foreach ( $blocks as $block ) {
		if ( 'pre' === $block['type'] ) {
			$html[] = '<pre><code>' . $escape( implode( '', $block['lines'] ) ) . '</code></pre>';
		} elseif ( 'h' === $block['type'] ) {
			$text = $block['lines'][0];
			$base = trim( strtolower( preg_replace( '~[^A-Za-z0-9]+~', '-', $text ) ?? '' ), '-' );
			$base = '' === $base ? 'section' : $base;
			$id   = $base;
			for ( $number = 2; isset( $ids[ $id ] ); $number++ ) {
				$id = $base . '-' . $number;
			}
			$ids[ $id ] = true;
			$html[]     = sprintf( '<h%1$d id="%2$s">%3$s</h%1$d>', $block['level'], $escape( $id ), $inline( $text ) );
		} elseif ( 'p' === $block['type'] ) {
			$html[] = '<p>' . $inline( implode( "\n", $block['lines'] ) ) . '</p>';
		} else {
			$start = 'ol' === $block['type'] && 1 !== $block['level'] ? ' start="' . $block['level'] . '"' : '';
			$list  = '<' . $block['type'] . $start . ">\n";
			foreach ( $block['lines'] as $item ) {
				$list .= '<li>' . $inline( $item ) . "</li>\n";
			}
			$html[] = $list . '</' . $block['type'] . '>';
		}
	}
	return array() === $html ? '' : implode( "\n", $html ) . "\n";
}

/**
 * Runs the command line interface.
 *
 * @param array<int, string>                                                       $argv  Arguments; index 0 is the script.
 * @param (callable(string): array{status: int, body: string, error: string})|null $fetch Fetches an https URL; status 0 means a transport error. Tests replace it.
 * @return int Exit code: 0 ok, 1 finding, 2 usage, read, write or network error.
 */
function main( array $argv, ?callable $fetch = null ): int {
	$error    = static function ( string $message ): int {
		fwrite( STDERR, '::error::' . $message . PHP_EOL );
		return 2;
	};
	$notice   = static function ( string $message ): void {
		fwrite( STDOUT, '::notice::' . $message . PHP_EOL );
	};
	$findings = static function ( array $messages ): int {
		foreach ( $messages as $message ) {
			fwrite( STDERR, '::error::' . $message . PHP_EOL );
		}
		return 1;
	};
	$usage    = 'Usage: php build-manifest.php check --theme=<dir> --tag=<tag> [--previous=<file|https-url|none>]' . PHP_EOL
		. '       php build-manifest.php build --theme=<dir> --tag=<tag> --zip=<zip> --out=<dir>';
	$commands = array(
		'check' => array( 'theme', 'tag', 'previous' ),
		'build' => array( 'theme', 'tag', 'zip', 'out' ),
	);

	$command = $argv[1] ?? '';
	if ( ! isset( $commands[ $command ] ) ) {
		fwrite( STDERR, $usage . PHP_EOL );
		return $error( 'Unknown or missing command: ' . $command );
	}
	$options = array();
	foreach ( array_slice( $argv, 2 ) as $argument ) {
		if ( 1 !== preg_match( '~^--([a-z]+)=(.*)$~s', $argument, $matches ) || ! in_array( $matches[1], $commands[ $command ], true ) ) {
			fwrite( STDERR, $usage . PHP_EOL );
			return $error( 'Unknown argument: ' . $argument );
		}
		$options[ $matches[1] ] = $matches[2];
	}
	if ( 'build' === $command && ! isset( $options['theme'], $options['tag'], $options['zip'], $options['out'] ) ) {
		fwrite( STDERR, $usage . PHP_EOL );
		return $error( '--theme, --tag, --zip and --out are required.' );
	}
	if ( ! isset( $options['theme'], $options['tag'] ) ) {
		fwrite( STDERR, $usage . PHP_EOL );
		return $error( '--theme and --tag are required.' );
	}
	if ( ! is_dir( $options['theme'] ) ) {
		return $error( 'Theme folder not found: ' . $options['theme'] );
	}

	if ( 'build' === $command ) {
		$zip = $options['zip'];
		$out = $options['out'];
		if ( ! is_file( $zip ) ) {
			return $error( 'ZIP not found: ' . $zip );
		}
		$raw_epoch = getenv( 'SOURCE_DATE_EPOCH' );
		$epoch     = EPOCH_DEFAULT;
		if ( false === $raw_epoch || '' === $raw_epoch ) {
			$notice( 'SOURCE_DATE_EPOCH not set, using 315532800 (1980-01-01).' );
		} elseif ( 1 === preg_match( '~^[0-9]{1,12}$~', $raw_epoch ) && intval( $raw_epoch ) >= EPOCH_DEFAULT ) {
			$epoch = intval( $raw_epoch );
		} else {
			return $error( 'SOURCE_DATE_EPOCH must be a Unix time from 315532800 on (1980-01-01, the first ZIP date): ' . $raw_epoch );
		}

		try {
			$problems = check_release( $options['theme'], $options['tag'] );
			$readme   = read_theme_meta( $options['theme'] )['readme_markdown'];
		} catch ( RuntimeException $exception ) {
			return $error( $exception->getMessage() );
		}
		if ( array() !== $problems ) {
			return $findings( $problems );
		}

		$sha256 = hash_file( 'sha256', $zip );
		if ( false === $sha256 ) {
			return $error( 'Cannot read the ZIP: ' . $zip );
		}
		$sums = $zip . '.sha256';
		if ( file_exists( $sums ) ) {
			$content = is_file( $sums ) && is_readable( $sums ) ? file_get_contents( $sums, false, null, 0, 4096 ) : false;
			if ( false === $content ) {
				return $error( 'Cannot read ' . $sums );
			}
			if ( 1 !== preg_match( '~^([0-9A-Fa-f]{64})(?:\s|$)~', $content, $matches ) ) {
				return $findings( array( basename( $sums ) . ': no SHA-256 checksum found' ) );
			}
			if ( strtolower( $matches[1] ) !== $sha256 ) {
				return $findings( array( sprintf( '%s: checksum %s differs from the ZIP %s', basename( $sums ), strtolower( $matches[1] ), $sha256 ) ) );
			}
		}

		try {
			$manifest = build_manifest( $options['theme'], $options['tag'], $sha256, $epoch );
			$pages    = render_pages( $manifest, $readme );
		} catch ( RuntimeException | JsonException $exception ) {
			return $error( 'Cannot build the manifest: ' . $exception->getMessage() );
		}
		$size = strlen( $pages[ MANIFEST_FILE ] );
		if ( $size > MANIFEST_MAX_BYTES ) {
			return $findings( array( sprintf( 'The manifest has %d bytes; the limit is %d bytes. Shorten the %d newest changelog entries in README.md.', $size, MANIFEST_MAX_BYTES, CHANGELOG_ENTRIES ) ) );
		}
		if ( ! is_dir( $out ) && ! mkdir( $out, 0777, true ) && ! is_dir( $out ) ) {
			return $error( 'Cannot create the pages folder: ' . $out );
		}
		foreach ( $pages as $name => $content ) {
			if ( false === file_put_contents( $out . '/' . $name, $content ) ) {
				return $error( 'Cannot write ' . $out . '/' . $name );
			}
		}
		fwrite( STDOUT, sprintf( 'Pages OK: %s (version %s, sha256 %s, bootstrap lines [%s]).', $out, $manifest['version'], $sha256, implode( ',', $manifest['bootstrap_lines'] ) ) . PHP_EOL );
		return 0;
	}

	$source = $options['previous'] ?? '';
	$json   = null;
	if ( isset( $options['previous'] ) && '' === $source ) {
		return $error( '--previous must not be empty; use --previous=none for a first publication.' );
	}
	if ( 'none' === $source ) {
		$notice( 'No previous manifest (--previous=none): first publication, line rule skipped.' );
	} elseif ( 1 === preg_match( '~^[A-Za-z][A-Za-z0-9+.-]*://~', $source ) ) {
		if ( ! str_starts_with( strtolower( $source ), 'https://' ) ) {
			return $error( 'Previous manifest: only https URLs are allowed, got ' . $source );
		}
		$fetch    = $fetch ?? static function ( string $url ): array {
			$context = stream_context_create(
				array(
					'http' => array(
						'method'          => 'GET',
						'timeout'         => 15,
						'ignore_errors'   => true,
						'follow_location' => 0,
						'user_agent'      => 'creationell-wp-theme-release-check',
					),
					'ssl'  => array(
						'verify_peer'      => true,
						'verify_peer_name' => true,
					),
				)
			);
			$failure = 'request failed';
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- The warning of a failed request becomes the error message.
			set_error_handler(
				static function ( int $errno, string $message ) use ( &$failure ): bool {
					$failure = $message;
					return true;
				}
			);
			$handle = fopen( $url, 'rb', false, $context );
			$body   = false === $handle ? false : stream_get_contents( $handle, PREVIOUS_MAX_BYTES + 1 );
			$meta   = false === $handle ? array() : stream_get_meta_data( $handle );
			if ( false !== $handle ) {
				fclose( $handle );
			}
			restore_error_handler();
			if ( false === $body ) {
				return array(
					'status' => 0,
					'body'   => '',
					'error'  => $failure,
				);
			}
			$status  = 0;
			$headers = $meta['wrapper_data'] ?? array();
			foreach ( is_array( $headers ) ? $headers : array() as $header ) {
				if ( is_string( $header ) && 1 === preg_match( '~^HTTP/\S+\s+([0-9]{3})~', $header, $matches ) ) {
					$status = intval( $matches[1] );
				}
			}
			return array(
				'status' => $status,
				'body'   => $body,
				'error'  => 0 === $status ? 'no HTTP status line' : '',
			);
		};
		$response = $fetch( $source );
		if ( 404 === $response['status'] ) {
			$notice( 'No published manifest at ' . $source . ' (404): first publication, line rule skipped.' );
		} elseif ( 0 === $response['status'] ) {
			return $error( 'Cannot fetch the previous manifest ' . $source . ': ' . $response['error'] );
		} elseif ( 200 !== $response['status'] ) {
			return $error( 'Cannot fetch the previous manifest ' . $source . ': HTTP ' . $response['status'] );
		} else {
			$json = $response['body'];
		}
	} elseif ( '' !== $source ) {
		$content = is_file( $source ) && is_readable( $source ) ? file_get_contents( $source, false, null, 0, PREVIOUS_MAX_BYTES + 1 ) : false;
		if ( false === $content ) {
			return $error( 'Cannot read the previous manifest: ' . $source );
		}
		$json = $content;
	}

	$previous = null;
	if ( null !== $json ) {
		if ( strlen( $json ) > PREVIOUS_MAX_BYTES ) {
			return $error( 'The previous manifest is larger than 1 MiB: ' . $source );
		}
		$previous = json_decode( $json, true );
		if ( ! is_array( $previous ) ) {
			return $error( 'The previous manifest is not a JSON object: ' . $source );
		}
	}

	try {
		$problems = check_release( $options['theme'], $options['tag'], $previous );
	} catch ( RuntimeException $exception ) {
		return $error( $exception->getMessage() );
	}
	if ( array() !== $problems ) {
		return $findings( $problems );
	}
	fwrite( STDOUT, sprintf( 'Release check OK: %s, bootstrap lines [%s].', $options['tag'], implode( ',', package_lines( $options['theme'] )['lines'] ) ) . PHP_EOL );
	return 0;
}

if ( 'cli' === PHP_SAPI && realpath( get_included_files()[0] ) === __FILE__ ) {
	exit( main( $argv ) );
}
