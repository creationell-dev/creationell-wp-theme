/*!
 * creationell Theme blog module: profile image of the author page.
 *
 * Plain JavaScript without a build step, loaded deferred in the footer of the
 * screens Profile and Edit User after the media library (media-editor). The
 * section "Author page" prints the attachment ID as a number field and the
 * buttons "Choose image" and "Remove image" hidden. The script hides the
 * number field, shows the buttons and lets the media library pick one image;
 * it writes the ID into the field and updates the preview. Without the media
 * library the number field stays. Every show and hide sets the inline display
 * next to the attribute hidden, because the admin CSS (.wp-core-ui .button,
 * input.small-text on small screens) overrides the attribute alone. The script carries no texts: the title and
 * the button of the media frame come from the data-frame-title and
 * data-frame-button attributes of the field. With data-library-author the
 * media library lists only the uploads of that user. The label of the field
 * moves from the hidden number field to the button "Choose image".
 */
( function () {
	'use strict';

	/**
	 * Wires one image field.
	 *
	 * @param {Element} field Container with the number field, the preview and the buttons.
	 * @return {void}
	 */
	function setup( field ) {
		var input = field.querySelector( 'input[type="number"]' );
		var preview = field.querySelector( '[data-creationell-author-image="preview"]' );
		var choose = field.querySelector( '[data-creationell-author-image="choose"]' );
		var remove = field.querySelector( '[data-creationell-author-image="remove"]' );
		var label = input ? document.querySelector( 'label[for="' + input.id + '"]' ) : null;
		var frame = null;

		if ( ! input || ! preview || ! choose || ! remove || ! window.wp || ! window.wp.media ) {
			return;
		}

		/**
		 * Shows or hides an element, also against admin CSS that sets its display.
		 *
		 * @param {HTMLElement} element Element.
		 * @param {boolean}     visible Whether to show it.
		 * @return {void}
		 */
		function toggle( element, visible ) {
			element.hidden = ! visible;
			element.style.display = visible ? '' : 'none';
		}

		/**
		 * Replaces the preview with the image at the URL, or empties it.
		 *
		 * @param {string} url Image URL; '' for none.
		 * @return {void}
		 */
		function show( url ) {
			var image;
			preview.textContent = '';
			if ( url ) {
				image = document.createElement( 'img' );
				image.src = url;
				image.alt = '';
				preview.appendChild( image );
			}
		}

		/**
		 * Shows the remove button only while an image is set.
		 *
		 * @return {void}
		 */
		function sync() {
			toggle( remove, '' !== input.value && '0' !== input.value );
		}

		/**
		 * Opens the media frame, built on the first click, with the current image selected.
		 *
		 * @return {void}
		 */
		function open() {
			var frameTitle = field.getAttribute( 'data-frame-title' ) || '';
			var frameButton = field.getAttribute( 'data-frame-button' ) || '';
			var author;
			var library;
			if ( ! frame ) {
				author = parseInt( field.getAttribute( 'data-library-author' ), 10 );
				library = { type: 'image' };
				if ( author > 0 ) {
					library.author = author;
				}
				frame = window.wp.media( {
					title: frameTitle,
					button: { text: frameButton },
					library: library,
					multiple: false
				} );
				frame.on( 'open', function () {
					var selection = frame.state().get( 'selection' );
					var id = parseInt( input.value, 10 );
					var attachment;
					if ( id > 0 ) {
						attachment = window.wp.media.attachment( id );
						attachment.fetch();
						selection.reset( [ attachment ] );
					} else {
						selection.reset( [] );
					}
				} );
				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					var sizes = attachment.sizes || {};
					var size = sizes.thumbnail || sizes.medium || sizes.full;
					input.value = String( attachment.id );
					show( size ? size.url : attachment.url );
					sync();
				} );
			}
			frame.open();
		}

		toggle( input, false );
		toggle( choose, true );
		if ( label && choose.id ) {
			label.htmlFor = choose.id;
		}
		sync();
		choose.addEventListener( 'click', open );
		remove.addEventListener( 'click', function () {
			input.value = '';
			show( '' );
			sync();
			choose.focus();
		} );
	}

	document.querySelectorAll( '[data-creationell-author-image="field"]' ).forEach( setup );
}() );
