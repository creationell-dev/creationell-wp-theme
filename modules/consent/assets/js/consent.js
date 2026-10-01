/*!
 * creationell Theme consent module.
 *
 * Plain JavaScript without a build step, loaded deferred in the footer after
 * CookieConsent (handle creationell-wp-theme-cookieconsent). It starts
 * CookieConsent with window.creationellWpThemeConsentConfig, which the theme
 * prints inline before this script, and registers CookieConsent as provider of
 * the consent bridge (window.creationellWpTheme.consent). Without CookieConsent
 * or without configuration nothing starts, and marked scripts stay blocked.
 *
 * The script carries no texts: all texts are in the configuration. Named
 * corrections at the end make CookieConsent 3.1.0 fully usable with keyboard
 * and screen readers (decision B of the spike report S-CC; F-n names its
 * findings).
 */
( function () {
	'use strict';

	var cc = window.CookieConsent;
	var config = window.creationellWpThemeConsentConfig;
	var html = document.documentElement;
	var returnTarget = null;
	var observer = null;

	// 1. Without CookieConsent or configuration nothing starts; marked scripts stay blocked.
	if ( ! cc || ! config || 'object' !== typeof config ) {
		return;
	}

	/**
	 * Turns the cookie names of a category into what CookieConsent deletes.
	 *
	 * Names with is_regex become regular expressions. A pattern that PHP accepts
	 * but JavaScript does not is dropped, so it cannot stop the banner.
	 *
	 * @param {Array} cookies Cookies with name and is_regex.
	 * @return {Array} Cookies with a string or RegExp name.
	 */
	function clearList( cookies ) {
		var list = [];
		cookies.forEach( function ( cookie ) {
			if ( ! cookie || 'string' !== typeof cookie.name ) {
				return;
			}
			if ( ! cookie.is_regex ) {
				list.push( { name: cookie.name } );
				return;
			}
			try {
				list.push( { name: new RegExp( cookie.name ) } );
			} catch ( error ) {
				// Invalid pattern: the cookie is not deleted on withdrawal.
			}
		} );
		return list;
	}

	// 2. Regular expressions arrive as strings with is_regex.
	Object.keys( config.categories || {} ).forEach( function ( name ) {
		var autoClear = config.categories[ name ] && config.categories[ name ].autoClear;
		if ( autoClear && Array.isArray( autoClear.cookies ) ) {
			autoClear.cookies = clearList( autoClear.cookies );
		}
	} );

	// 3. CookieConsent answers the consent bridge.
	var bridge = window.creationellWpTheme && window.creationellWpTheme.consent;
	if ( bridge && 'function' === typeof bridge.registerProvider ) {
		bridge.registerProvider( {
			id: 'cookieconsent',
			allowed: function ( category ) {
				return cc.acceptedCategory( category );
			},
		} );
	}

	// 4. Consent and changes reach the bridge, which sends creationell-wp-theme:consent-change.
	function notify() {
		if ( bridge && 'function' === typeof bridge.notify ) {
			bridge.notify();
		}
	}
	window.addEventListener( 'cc:onConsent', notify );
	window.addEventListener( 'cc:onChange', notify );

	/**
	 * Returns the element that opened a Bootstrap offcanvas or modal.
	 *
	 * @param {Element} container Offcanvas or modal.
	 * @return {Element|null} Toggler.
	 */
	function toggler( container ) {
		if ( ! container.id ) {
			return null;
		}
		return document.querySelector( '[data-bs-target="#' + container.id + '"], [href="#' + container.id + '"]' );
	}

	/**
	 * Focuses the preferences dialog again; Bootstrap returns the focus to its toggler after hiding.
	 *
	 * @return {void}
	 */
	function refocusDialog() {
		var dialog = document.querySelector( '#cc-main .pm' );
		if ( ! html.classList.contains( 'show--preferences' ) || ! dialog || dialog.contains( document.activeElement ) ) {
			return;
		}
		var target = dialog.querySelector( '[tabindex="-1"]' ) || dialog.querySelector( 'button' );
		if ( target ) {
			target.focus();
		}
	}

	// 5. The dialog closes open offcanvas and modals (one focus trap at a time) and returns the focus.
	window.addEventListener( 'cc:onModalShow', function ( event ) {
		if ( 'preferencesModal' !== event.detail.modalName ) {
			return;
		}
		returnTarget = null;
		var bs = window.bootstrap;
		document.querySelectorAll( '.offcanvas.show, .modal.show' ).forEach( function ( container ) {
			var isModal = container.classList.contains( 'modal' );
			var component = bs && ( isModal ? bs.Modal : bs.Offcanvas );
			if ( ! component ) {
				return;
			}
			returnTarget = returnTarget || toggler( container );
			container.addEventListener( isModal ? 'hidden.bs.modal' : 'hidden.bs.offcanvas', refocusDialog, { once: true } );
			component.getOrCreateInstance( container ).hide();
		} );
	} );
	window.addEventListener( 'cc:onModalHide', function ( event ) {
		if ( 'preferencesModal' !== event.detail.modalName ) {
			return;
		}
		var active = document.activeElement;
		var lost = ! active || active === document.body || null === active.offsetParent;
		if ( returnTarget && lost ) {
			returnTarget.focus();
		}
		returnTarget = null;
	} );

	// 6. The banner height keeps focused elements and the page end visible (consent.css).
	function trackOffset() {
		var banner = document.querySelector( '#cc-main .cm' );
		if ( ! banner || ! window.ResizeObserver ) {
			return;
		}
		observer = observer || new window.ResizeObserver( function () {
			if ( html.classList.contains( 'show--consent' ) ) {
				html.style.setProperty( '--creationell-theme-consent-offset', banner.offsetHeight + 'px' );
			} else {
				html.style.removeProperty( '--creationell-theme-consent-offset' );
			}
		} );
		observer.observe( banner );
	}
	window.addEventListener( 'cc:onModalShow', function ( event ) {
		if ( 'consentModal' === event.detail.modalName ) {
			trackOffset();
		}
	} );
	window.addEventListener( 'cc:onModalHide', function ( event ) {
		if ( 'consentModal' === event.detail.modalName ) {
			html.style.removeProperty( '--creationell-theme-consent-offset' );
		}
	} );

	// 8. Named corrections of CookieConsent 3.1.0 (decision B, spike report S-CC, section 4).

	/**
	 * F-2: the banner is not modal (disablePageInteraction false), so it is a
	 * named region instead of a modal dialog, as upstream commit 772b737 does.
	 */
	function bannerAsRegion( banner ) {
		banner.setAttribute( 'role', 'region' );
		banner.removeAttribute( 'aria-modal' );
	}

	/**
	 * F-4: the settings button of the banner announces that it opens a dialog,
	 * as upstream commit 706f25c does.
	 */
	function announcePreferencesPopup( banner ) {
		var button = banner.querySelector( '[data-role="show"]' );
		if ( button ) {
			button.setAttribute( 'aria-haspopup', 'dialog' );
		}
	}

	/**
	 * F-10: the SVG icons of the close button and the section arrows are
	 * decorative, as upstream commit 772b737 marks them.
	 */
	function hideDecorativeIcons( modal ) {
		modal.querySelectorAll( 'svg' ).forEach( function ( icon ) {
			icon.setAttribute( 'aria-hidden', 'true' );
		} );
	}

	/**
	 * F-3: Tab and Shift+Tab may leave the non-modal banner. CookieConsent traps
	 * the focus in the banner with a bubbling keydown listener on #cc-main; a
	 * capturing listener on the root element runs before it and stops Tab for
	 * the banner only, while the dialog keeps its trap.
	 */
	function releaseBannerFocusTrap( root ) {
		root.addEventListener( 'keydown', function ( event ) {
			if ( 'Tab' !== event.key || html.classList.contains( 'show--preferences' ) ) {
				return;
			}
			if ( event.target && event.target.closest && null !== event.target.closest( '#cc-main .cm' ) ) {
				event.stopPropagation();
			}
		}, true );
	}

	/**
	 * F-11: CookieConsent focuses a tabindex="-1" element when the dialog opens;
	 * Shift+Tab from there leaves the dialog, because the trap only reacts on its
	 * first and last focusable element (upstream issue 828, fixed on dev in
	 * c0a6c51). A capturing listener on document wraps the focus first.
	 */
	function keepTabInDialog() {
		document.addEventListener( 'keydown', function ( event ) {
			var dialog = document.querySelector( '#cc-main .pm' );
			var active = document.activeElement;
			if ( 'Tab' !== event.key || ! dialog || ! html.classList.contains( 'show--preferences' ) || ! dialog.contains( active ) || active.tabIndex >= 0 ) {
				return;
			}
			var focusables = Array.prototype.filter.call( dialog.querySelectorAll( 'button, a[href], input, [tabindex]:not([tabindex="-1"])' ), function ( element ) {
				return ! element.disabled && element.getClientRects().length > 0;
			} );
			if ( 0 === focusables.length ) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			focusables[ event.shiftKey ? focusables.length - 1 : 0 ].focus();
		}, true );
	}

	/**
	 * F-1: CookieConsent 3.1.0 focuses the banner 100 ms after showing it, while
	 * it is still hidden, so the focus stays on the page. Once the banner is
	 * visible it takes the focus, unless the visitor already focused something,
	 * as upstream commit 772b737 does later. A skip link target that the theme
	 * script focused for the hash of the URL (#primary, #footer) counts like the
	 * body: the visitor did not choose it.
	 */
	function focusBannerWhenVisible() {
		var tries = 0;
		function attempt() {
			var banner = document.querySelector( '#cc-main .cm' );
			if ( ! banner || ! html.classList.contains( 'show--consent' ) ) {
				return;
			}
			if ( 'visible' !== window.getComputedStyle( banner ).visibility && tries++ < 20 ) {
				window.setTimeout( attempt, 50 );
				return;
			}
			var active = document.activeElement;
			if ( null === active || active === document.body || ( active.matches( '#primary, #footer' ) && '#' + active.id === window.location.hash ) ) {
				banner.setAttribute( 'tabindex', '-1' );
				banner.focus();
			}
		}
		window.setTimeout( attempt, 150 );
	}

	window.addEventListener( 'cc:onModalReady', function ( event ) {
		if ( 'consentModal' === event.detail.modalName ) {
			bannerAsRegion( event.detail.modal );
			announcePreferencesPopup( event.detail.modal );
		}
		hideDecorativeIcons( event.detail.modal );
	} );
	window.addEventListener( 'cc:onModalShow', function ( event ) {
		if ( 'consentModal' === event.detail.modalName ) {
			focusBannerWhenVisible();
		}
	} );
	keepTabInDialog();
	var root = document.querySelector( config.root || 'body' );
	if ( root ) {
		releaseBannerFocusTrap( root );
	}

	// 7. The consent links become visible once CookieConsent has bound data-cc.
	cc.run( config ).then( function () {
		trackOffset();
		document.querySelectorAll( '.creationell-theme-consent-link' ).forEach( function ( link ) {
			link.hidden = false;
		} );
	} );
}() );
