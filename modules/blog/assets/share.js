/*!
 * creationell Theme blog module: copy and share buttons of the share links.
 *
 * Plain JavaScript without a build step and without dependencies, loaded
 * deferred in the footer on a single post with the own share links. The
 * template part template-parts/blog/share.php prints the copy button and the
 * button for the share dialog of the system in hidden list items. The script
 * shows the copy item only in a secure context with the clipboard
 * (navigator.clipboard.writeText) and the share item only with the share
 * dialog (navigator.share). The script carries no texts: the messages come
 * from the data-msg-ok and data-msg-error attributes of the buttons. On a
 * click the status line of the section is emptied; the message follows about
 * 100 ms later as plain text, so screen readers announce it again. A cancelled
 * share dialog (AbortError) says nothing.
 */
( function () {
	'use strict';

	var DELAY = 100;

	/**
	 * Returns the status line of the share section of a button and empties it.
	 *
	 * @param {Element} button Copy or share button.
	 * @return {Element|null} Status line.
	 */
	function clearStatus( button ) {
		var section = button.closest( '.share' );
		var status = section ? section.querySelector( '[data-creationell-share-status]' ) : null;
		if ( status ) {
			status.textContent = '';
		}
		return status;
	}

	/**
	 * Writes a message of a button into the status line after a short delay.
	 *
	 * @param {Element|null} status Status line.
	 * @param {Element}      button Button with the message attribute.
	 * @param {string}       name   data-msg-ok or data-msg-error.
	 * @return {void}
	 */
	function announce( status, button, name ) {
		var text = button.getAttribute( name ) || '';
		if ( ! status || '' === text ) {
			return;
		}
		window.setTimeout( function () {
			status.textContent = text;
		}, DELAY );
	}

	/**
	 * Shows the hidden items of a kind and binds a click handler to their buttons.
	 *
	 * @param {string}   item    Attribute of the list items.
	 * @param {string}   control Attribute of the buttons inside.
	 * @param {Function} handler Click handler, called with the button.
	 * @return {void}
	 */
	function reveal( item, control, handler ) {
		Array.prototype.forEach.call( document.querySelectorAll( '[' + item + ']' ), function ( element ) {
			var button = element.querySelector( '[' + control + ']' );
			if ( ! button ) {
				return;
			}
			button.addEventListener( 'click', function () {
				handler( button );
			} );
			element.removeAttribute( 'hidden' );
		} );
	}

	/**
	 * Copies the permalink of a copy button to the clipboard.
	 *
	 * @param {Element} button Copy button.
	 * @return {void}
	 */
	function copy( button ) {
		var status = clearStatus( button );
		var written;
		try {
			written = window.navigator.clipboard.writeText( button.getAttribute( 'data-creationell-share-url' ) || '' );
		} catch ( error ) {
			announce( status, button, 'data-msg-error' );
			return;
		}
		Promise.resolve( written ).then( function () {
			announce( status, button, 'data-msg-ok' );
		}, function () {
			announce( status, button, 'data-msg-error' );
		} );
	}

	/**
	 * Opens the share dialog of the system with the title and the permalink of a share button.
	 *
	 * @param {Element} button Share button.
	 * @return {void}
	 */
	function share( button ) {
		var status = clearStatus( button );
		var data = { url: button.getAttribute( 'data-creationell-share-url' ) || '' };
		var title = button.getAttribute( 'data-creationell-share-title' ) || '';
		var failed = function ( error ) {
			if ( ! error || 'AbortError' !== error.name ) {
				announce( status, button, 'data-msg-error' );
			}
		};
		if ( '' !== title ) {
			data.title = title;
		}
		try {
			Promise.resolve( window.navigator.share( data ) ).then( null, failed );
		} catch ( error ) {
			failed( error );
		}
	}

	if ( window.isSecureContext && window.navigator.clipboard && 'function' === typeof window.navigator.clipboard.writeText ) {
		reveal( 'data-creationell-share-copy-item', 'data-creationell-share-copy', copy );
	}
	if ( 'function' === typeof window.navigator.share ) {
		reveal( 'data-creationell-share-native-item', 'data-creationell-share-native', share );
	}
}() );
