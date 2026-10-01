/**
 * Module contact-form-7: moves the focus to the first invalid field after a failed submission.
 *
 * Contact Form 7 marks invalid fields with aria-invalid="true" and announces the
 * errors in its live region, but leaves the focus on the submit button. After the
 * events wpcf7invalid and wpcf7unaccepted this script focuses the first invalid
 * input, select, textarea or checkbox of that form; without one, and only after
 * wpcf7unaccepted, the first unchecked checkbox of an acceptance field.
 *
 * Plain JavaScript without a build step, without globals and without texts.
 * Validation, the submit button and the spinner stay with Contact Form 7.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */
(() => {
	'use strict';

	const FIELDS = 'input, select, textarea';

	/**
	 * Returns the form of an event target: the form itself or the first form inside the wrapper.
	 *
	 * @param {EventTarget|null} target Target of the Contact Form 7 event.
	 * @return {HTMLFormElement|null} Form, or null.
	 */
	const formOf = ( target ) => {
		if ( ! ( target instanceof Element ) ) {
			return null;
		}
		if ( target instanceof HTMLFormElement ) {
			return target;
		}
		return target.querySelector( 'form.wpcf7-form' ) || target.closest( 'form' );
	};

	/**
	 * Tells whether an element can take the focus as a form field.
	 *
	 * @param {Element|null} element Element.
	 * @return {boolean} True for an enabled input, select or textarea that is not hidden.
	 */
	const focusable = ( element ) =>
		element instanceof HTMLElement &&
		element.matches( FIELDS ) &&
		! element.matches( ':disabled, [type="hidden"]' );

	/**
	 * Finds the field to focus in a form.
	 *
	 * @param {HTMLFormElement} form     Form.
	 * @param {boolean}         accepted False after wpcf7unaccepted.
	 * @return {HTMLElement|null} Field, or null.
	 */
	const target = ( form, accepted ) => {
		for ( const invalid of form.querySelectorAll( '[aria-invalid="true"]' ) ) {
			if ( focusable( invalid ) ) {
				return invalid;
			}
			const inner = Array.from( invalid.querySelectorAll( FIELDS ) ).find( focusable );
			if ( inner ) {
				return inner;
			}
		}
		if ( ! accepted ) {
			const box = Array.from( form.querySelectorAll( '.wpcf7-acceptance input[type="checkbox"]' ) ).find(
				( input ) => focusable( input ) && ! input.checked
			);
			if ( box ) {
				return box;
			}
		}
		return null;
	};

	/**
	 * Moves the focus after Contact Form 7 has updated the form.
	 *
	 * @param {Event} event Event wpcf7invalid or wpcf7unaccepted.
	 * @return {void}
	 */
	const onInvalid = ( event ) => {
		const form = formOf( event.target );
		if ( ! form ) {
			return;
		}
		const accepted = 'wpcf7unaccepted' !== event.type;
		window.requestAnimationFrame( () => {
			const field = target( form, accepted );
			if ( field ) {
				field.focus();
			}
		} );
	};

	document.addEventListener( 'wpcf7invalid', onInvalid );
	document.addEventListener( 'wpcf7unaccepted', onInvalid );
})();
