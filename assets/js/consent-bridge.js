/*!
 * creationell Theme consent bridge.
 *
 * Plain JavaScript without a build step, printed inline in the page head by
 * Consent_Bridge right after its data (window.creationellWpTheme.consent =
 * { categories, providerId }). It replaces the data with the consent API:
 *
 *   window.creationellWpTheme.consent.allowed('analytics');
 *   window.creationellWpTheme.consent.registerProvider({ id: 'my-banner', allowed: fn });
 *   document.addEventListener('creationell-wp-theme:consent-change', fn);
 *
 * allowed() is true for "necessary"; any other category needs the adapter of
 * the consent provider and a known category, so it fails closed. The bridge
 * runs no scripts: the consent provider does that.
 */
(function () {
  'use strict';

  var NECESSARY = 'necessary';
  var EVENT = 'creationell-wp-theme:consent-change';
  var ns = window.creationellWpTheme = window.creationellWpTheme || {};
  var data = ns.consent;

  if (data && typeof data.registerProvider === 'function') {
    return;
  }
  data = data && typeof data === 'object' ? data : {};

  var categories = [NECESSARY];
  (Array.isArray(data.categories) ? data.categories : []).forEach(function (category) {
    if (typeof category === 'string' && categories.indexOf(category) === -1) {
      categories.push(category);
    }
  });
  Object.freeze(categories);

  var providerId = typeof data.providerId === 'string' && data.providerId !== '' ? data.providerId : null;
  var adapter = null;

  /**
   * Tells whether the visitor allowed a category.
   *
   * @param {string} category Category, e.g. "analytics".
   * @return {boolean} True for "necessary"; otherwise only when the adapter answers true for a known category.
   */
  function allowed(category) {
    if (category === NECESSARY) {
      return true;
    }
    if (adapter === null || categories.indexOf(category) === -1) {
      return false;
    }
    try {
      return adapter.allowed(category) === true;
    } catch (error) {
      return false;
    }
  }

  /**
   * Tells whether the adapter of the consent provider has registered.
   *
   * @return {boolean} True after a successful registerProvider().
   */
  function hasProvider() {
    return adapter !== null;
  }

  /**
   * Registers the adapter of the consent provider; only the provider named by the theme, only once.
   *
   * @param {{id: string, allowed: function(string): boolean}} provider Adapter.
   * @return {boolean} True when registered.
   */
  function registerProvider(provider) {
    if (adapter !== null || providerId === null || !provider || provider.id !== providerId || typeof provider.allowed !== 'function') {
      return false;
    }
    adapter = provider;
    return true;
  }

  /**
   * Sends the event creationell-wp-theme:consent-change with the allowed categories.
   */
  function notify() {
    document.dispatchEvent(new CustomEvent(EVENT, {
      detail: { accepted: categories.filter(allowed) }
    }));
  }

  Object.defineProperty(ns, 'consent', {
    value: Object.freeze({
      categories: categories,
      allowed: allowed,
      hasProvider: hasProvider,
      registerProvider: registerProvider,
      notify: notify
    }),
    enumerable: true,
    writable: false,
    configurable: false
  });
}());
