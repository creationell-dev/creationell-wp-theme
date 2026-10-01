/*!
 * creationell Theme script.
 *
 * Plain JavaScript without a build step. The Bootstrap bundle loads before this
 * file; every access to its namespace goes through bs(). The script holds no
 * texts: labels come from the markup.
 */
(function () {
  'use strict';

  /**
   * Returns the namespace of the Bootstrap bundle.
   *
   * @return {Object|null} window.bootstrap, or null when the bundle is missing.
   */
  function bs() {
    return window.bootstrap || null;
  }

  /**
   * Runs a callback once the document is parsed.
   *
   * @param {Function} callback Callback.
   */
  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
    } else {
      callback();
    }
  }

  /**
   * Search collapse of the header: focuses the search field when the collapse
   * opens, closes it on a click outside and on Escape; after Escape the focus
   * returns to the button that opened it.
   */
  function initSearchCollapse() {
    var collapseSearch = document.getElementById('collapse-search');
    if (!collapseSearch) {
      return;
    }

    function hide() {
      var lib = bs();
      var instance = lib ? lib.Collapse.getInstance(collapseSearch) : null;
      if (instance) {
        instance.hide();
      }
    }

    collapseSearch.addEventListener('shown.bs.collapse', function () {
      var input = collapseSearch.querySelector('input:not([type="hidden"])');
      if (input) {
        setTimeout(function () {
          input.focus();
        }, 0);
      }
    });

    collapseSearch.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape' || !collapseSearch.classList.contains('show')) {
        return;
      }
      event.preventDefault();
      hide();
      var toggler = document.querySelector('[data-bs-target="#collapse-search"]');
      if (toggler) {
        toggler.focus();
      }
    });

    document.addEventListener('click', function (event) {
      if (event.target instanceof Element && (event.target.closest('#collapse-search') || event.target.closest('[data-bs-target="#collapse-search"]'))) {
        return;
      }
      hide();
    });
  }

  /**
   * Sticky header: keeps its height in the custom property
   * --creationell-theme-sticky-offset on the root element, so that scroll
   * padding keeps link targets and focused elements out from under it.
   */
  function initStickyOffset() {
    var header = document.getElementById('masthead');
    if (!header) {
      return;
    }

    function update() {
      var sticky = window.getComputedStyle(header).position === 'sticky' || window.getComputedStyle(header).position === 'fixed';
      var height = sticky ? Math.ceil(header.getBoundingClientRect().height) : 0;
      document.documentElement.style.setProperty('--creationell-theme-sticky-offset', height + 'px');
    }

    update();
    if (typeof window.ResizeObserver === 'function') {
      new window.ResizeObserver(update).observe(header);
    } else {
      window.addEventListener('resize', update, { passive: true });
    }
  }

  /**
   * Scroll-to-top button: visible after 500 pixels of scrolling; scrolls up
   * and moves the focus to the start of the page (#to-top).
   */
  function initTopButton() {
    var topButton = document.querySelector('.top-button');
    if (!topButton) {
      return;
    }

    window.addEventListener('scroll', function () {
      topButton.classList.toggle('visible', window.scrollY >= 500);
    }, { passive: true });

    topButton.addEventListener('click', function () {
      var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
      var target = document.getElementById('to-top');
      if (target) {
        target.focus({ preventScroll: true });
      }
    });
  }

  /**
   * Offcanvas panels: the name from aria-labelledby only while Bootstrap shows
   * the panel as dialog. A closed panel has no role, and a name on an element
   * without role is not allowed.
   */
  function initOffcanvasNames() {
    Array.prototype.forEach.call(document.querySelectorAll('.offcanvas[aria-labelledby]'), function (panel) {
      var label = panel.getAttribute('aria-labelledby');
      if (panel.getAttribute('role') !== 'dialog') {
        panel.removeAttribute('aria-labelledby');
      }
      panel.addEventListener('show.bs.offcanvas', function () {
        panel.setAttribute('aria-labelledby', label);
      });
      panel.addEventListener('hidden.bs.offcanvas', function () {
        panel.removeAttribute('aria-labelledby');
      });
    });
  }

  /**
   * Submenus of the navigation: Escape closes the innermost open submenu around
   * the focus and returns the focus to its toggle. Bootstrap picks the dropdown
   * of the focused entry instead; when that entry has a submenu of its own (a
   * closed one, or a toggle without a submenu below the menu depth), the open
   * submenu stays open. The listener runs on window in the capture phase, before
   * the handlers of Bootstrap and of the offcanvas.
   */
  function initSubmenuEscape() {
    window.addEventListener('keydown', function (event) {
      var lib = bs();
      if (event.key !== 'Escape' || !lib || !(event.target instanceof Element)) {
        return;
      }
      var item = event.target.closest('.nav-item-has-toggle');
      if (!item || !item.parentElement) {
        return;
      }
      var menu = item.querySelector(':scope > .dropdown-menu.show') || item.parentElement.closest('.nav-item-has-toggle > .dropdown-menu.show');
      var toggle = menu ? menu.parentElement.querySelector(':scope > [data-bs-toggle="dropdown"]') : null;
      if (!toggle) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      lib.Dropdown.getOrCreateInstance(toggle).hide();
      toggle.focus();
    }, true);
  }

  /**
   * Skip links: the target of an activated skip link takes the focus, so the
   * next Tab goes on inside it. The target gets tabindex="-1" only while it has
   * the focus (pattern of the GOV.UK skip link); a fixed tabindex would let every
   * mouse click into the content focus the whole container and move the start
   * point of Tab back to its beginning. A mouse button pressed inside the focused
   * target takes the tabindex back at once, so Tab goes on from the click. Runs
   * on a click on a skip link (also when the URL already has its hash), on
   * hashchange and for a page opened with the hash of a target; the hashchange
   * that a skip link click causes is left out, so a Tab in between keeps its
   * place. The focus does not scroll: the browser scrolls to the fragment itself,
   * smoothly where Bootstrap asks for it and below the sticky header
   * (scroll-padding-top). Targets are the elements the links of .skip-link point
   * to. Without this script the browser moves only the start point of Tab.
   */
  function initSkipLinks() {
    var links = document.querySelectorAll('a.skip-link[href^="#"]');
    var clicked = null;
    if (!links.length) {
      return;
    }

    function targetOf(hash) {
      var id = (hash || '').replace(/^#/, '');
      if (!id) {
        return null;
      }
      var known = Array.prototype.some.call(links, function (link) {
        return link.getAttribute('href') === '#' + id;
      });
      return known ? document.getElementById(id) : null;
    }

    function focusTarget(target) {
      if (!target) {
        return;
      }
      if (!target.hasAttribute('tabindex')) {
        target.setAttribute('tabindex', '-1');
        var release = function () {
          target.removeAttribute('tabindex');
          target.removeEventListener('blur', release);
          target.removeEventListener('mousedown', release);
        };
        target.addEventListener('blur', release);
        target.addEventListener('mousedown', release);
      }
      target.focus({ preventScroll: true });
    }

    Array.prototype.forEach.call(links, function (link) {
      link.addEventListener('click', function (event) {
        // A click that opens a new tab or window, or that another script stops, leaves the page alone.
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
          return;
        }
        var href = link.getAttribute('href');
        var target = targetOf(href);
        clicked = window.location.hash === href ? null : target;
        focusTarget(target);
      });
    });
    window.addEventListener('hashchange', function () {
      var target = targetOf(window.location.hash);
      var fromClick = target !== null && target === clicked;
      clicked = null;
      if (target && !fromClick && document.activeElement !== target) {
        focusTarget(target);
      }
    });
    focusTarget(targetOf(window.location.hash));
  }

  ready(function () {
    initSkipLinks();
    initOffcanvasNames();
    initSubmenuEscape();
    initSearchCollapse();
    initStickyOffset();
    initTopButton();
  });
}());
