<?php
/**
 * Interface of a consent provider.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Consent;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * A consent provider knows which categories a visitor may allow, e.g. the consent module with its banner.
 *
 * This theme version has no provider; Consent_Gate then allows only "necessary".
 *
 * @since 1.0.0
 */
interface Consent_Provider_Interface {

	/**
	 * Returns the ID of the provider.
	 *
	 * @since 1.0.0
	 *
	 * @return string ID, lower case letters, digits and hyphens.
	 */
	public function id(): string;

	/**
	 * Returns the categories the provider asks the visitor about.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Categories, including "necessary".
	 */
	public function categories(): array;
}
