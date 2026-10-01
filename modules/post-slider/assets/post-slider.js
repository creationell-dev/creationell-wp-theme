/*!
 * creationell Theme post slider module.
 *
 * Plain JavaScript without a build step, loaded deferred in the footer after
 * Swiper (handle creationell-wp-theme-swiper). It starts Swiper once on every
 * element with data-creationell-slider, whose JSON the theme prints from
 * Slider_Config, and marks it with data-initialized. The script carries no
 * texts: the a11y texts of Swiper and the labels of the pause button come with
 * the JSON. Without Swiper, or with broken JSON, a slider stays as it is and
 * the stylesheet shows its slides as a grid.
 *
 * The keyboard module of Swiper stays off, since it would catch the arrow keys
 * of the whole page (FS-10); arrows and dots are buttons. With
 * prefers-reduced-motion: reduce the slides change without animation and the
 * slideshow does not start by itself; the pause button then offers to play.
 * While focus is inside the slider (except on the pause button) or the pointer
 * is over the slides, the slideshow holds; it goes on afterwards unless the
 * pause button stopped it. The slides region is live only while the slideshow
 * stands still.
 */
( function () {
	'use strict';

	var SELECTOR = '[data-creationell-slider]';

	/**
	 * Reads the options of a slider.
	 *
	 * @param {Element} section Slider element.
	 * @return {Object|null} Options, or null for missing or broken JSON.
	 */
	function readOptions( section ) {
		var options;
		try {
			options = JSON.parse( section.getAttribute( 'data-creationell-slider' ) || '' );
		} catch ( error ) {
			return null;
		}
		return options && 'object' === typeof options ? options : null;
	}

	/**
	 * Tells whether the visitor asks for less motion.
	 *
	 * @return {boolean} True with prefers-reduced-motion: reduce.
	 */
	function prefersReducedMotion() {
		return 'function' === typeof window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	/**
	 * Builds the Swiper parameters from the options of a slider.
	 *
	 * @param {Element} section Slider element.
	 * @param {Object}  options Options of Slider_Config.
	 * @param {boolean} reduced Whether the visitor asks for less motion.
	 * @return {Object} Swiper parameters.
	 */
	function parameters( section, options, reduced ) {
		var fade = 'fade' === options.effect;
		var prev = section.querySelector( '.creationell-theme-post-slider__prev' );
		var next = section.querySelector( '.creationell-theme-post-slider__next' );
		var dots = section.querySelector( '.swiper-pagination' );
		var delay = options.autoplay && 'object' === typeof options.autoplay ? Number( options.autoplay.delay ) : 0;
		var params = {
			speed: reduced ? 0 : Number( options.speed ) || 0,
			effect: fade ? 'fade' : 'slide',
			loop: true === options.loop,
			slidesPerView: 1,
			spaceBetween: Number( options.spaceBetween ) || 0,
			breakpoints: options.breakpoints && 'object' === typeof options.breakpoints ? options.breakpoints : {},
			keyboard: { enabled: false },
			a11y: Object.assign( { enabled: true }, options.a11y && 'object' === typeof options.a11y ? options.a11y : {} ),
			navigation: false,
			pagination: false,
			autoplay: false
		};
		if ( fade ) {
			params.fadeEffect = { mode: 'cross-fade' };
		}
		if ( true === options.navigation && prev && next ) {
			params.navigation = { prevEl: prev, nextEl: next, addIcons: false };
		}
		if ( true === options.pagination && dots ) {
			params.pagination = { el: dots, clickable: true, bulletElement: 'button' };
		}
		if ( delay > 0 ) {
			params.autoplay = { enabled: ! reduced, delay: delay, disableOnInteraction: false };
		}
		return params;
	}

	/**
	 * Wires the pause button, focus and pointer to the slideshow of a slider.
	 *
	 * @param {Element} section Slider element.
	 * @param {Object}  swiper  Swiper instance.
	 * @param {Object}  labels  Labels pause and play of the button.
	 * @param {boolean} reduced Whether the slideshow waits for the button.
	 * @return {void}
	 */
	function controlAutoplay( section, swiper, labels, reduced ) {
		var button = section.querySelector( '.creationell-theme-post-slider__pause' );
		var slides = section.querySelector( '.swiper' );
		var wrapper = section.querySelector( '.swiper-wrapper' );
		var state = { stopped: reduced, focus: false, pointer: false };

		function update() {
			var run = ! state.stopped && ! state.focus && ! state.pointer;
			if ( run && ! swiper.autoplay.running ) {
				swiper.autoplay.start();
			} else if ( ! run && swiper.autoplay.running ) {
				swiper.autoplay.stop();
			}
			if ( wrapper ) {
				wrapper.setAttribute( 'aria-live', swiper.autoplay.running ? 'off' : 'polite' );
			}
			if ( button && labels ) {
				button.textContent = String( state.stopped ? labels.play : labels.pause );
			}
		}

		section.addEventListener( 'focusin', function ( event ) {
			state.focus = event.target !== button;
			update();
		} );
		section.addEventListener( 'focusout', function ( event ) {
			var to = event.relatedTarget;
			state.focus = Boolean( to ) && to !== button && section.contains( to );
			update();
		} );
		if ( slides ) {
			slides.addEventListener( 'mouseenter', function () {
				state.pointer = true;
				update();
			} );
			slides.addEventListener( 'mouseleave', function () {
				state.pointer = false;
				update();
			} );
		}
		if ( button ) {
			button.addEventListener( 'click', function () {
				state.stopped = ! state.stopped;
				update();
			} );
		}
		update();
	}

	/**
	 * Starts Swiper on one slider, once.
	 *
	 * @param {Element} section Slider element.
	 * @return {void}
	 */
	function start( section ) {
		var options;
		var reduced;
		var params;
		var swiper;
		var el = section.querySelector( '.swiper' );
		if ( section.hasAttribute( 'data-initialized' ) || ! el ) {
			return;
		}
		options = readOptions( section );
		if ( ! options ) {
			return;
		}
		reduced = prefersReducedMotion();
		params = parameters( section, options, reduced );
		try {
			swiper = new window.Swiper( el, params );
		} catch ( error ) {
			return;
		}
		section.setAttribute( 'data-initialized', 'true' );
		if ( params.autoplay ) {
			controlAutoplay( section, swiper, options.labels, reduced );
		}
	}

	/**
	 * Starts every slider of the page.
	 *
	 * @return {void}
	 */
	function startAll() {
		var sections = document.querySelectorAll( SELECTOR );
		var i;
		for ( i = 0; i < sections.length; i++ ) {
			start( sections[ i ] );
		}
	}

	// Without Swiper the sliders stay a grid.
	if ( 'function' !== typeof window.Swiper ) {
		return;
	}
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', startAll );
	} else {
		startAll();
	}
}() );
