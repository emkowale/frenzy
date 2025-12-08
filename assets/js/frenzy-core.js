/* Core Frenzy namespace and shared state */
(function (window, $) {
  if (!window.frenzy_ajax || !document.body.classList.contains('single-product')) return;

  const Frenzy = window.Frenzy || {};

  Frenzy.const = {
    BASE_W: 1200,
    BASE_H: 1200,
    minBoxPx: 20,
    snapThreshold: 10,
  };

  Frenzy.state = {
    region: window.frenzy_ajax.region || { x: 320, y: 250, w: 560, h: 650 },
    canEditGrid: !!window.frenzy_ajax.can_edit_grid,
    container: null,
    printBox: null,
    resizeHandle: null,
    overlayHandle: null,
    overlay: null,
    deleteBtn: null,
    centerLine: null,
    artNatural: { w: 0, h: 0 },
    dragMode: null,
    start: null,
    printBoxVisible: false,
    printBorderColor: 'rgba(255,255,255,0.9)',
    hoverDevice: window.matchMedia && window.matchMedia('(hover: hover)').matches,
    hoverInGallery: false,
    isEditingGrid: false,
    lastOverlaySrc: '',
    lastTransform: null,
  };

  Frenzy.log = (...args) => {
    try { console.debug('[Frenzy]', ...args); } catch (e) {}
  };

  Frenzy.getImageRects = function () {
    const imgEl = $('.woocommerce-product-gallery__image img').first()[0];
    if (!imgEl) return null;
    const wrap = imgEl.closest('.woocommerce-product-gallery__image');
    if (!wrap) return null;
    const imgRect = imgEl.getBoundingClientRect();
    const wrapRect = wrap.getBoundingClientRect();
    return {
      imgRect,
      wrapRect,
      offsetLeft: imgRect.left - wrapRect.left,
      offsetTop: imgRect.top - wrapRect.top,
    };
  };

  Frenzy.ensureContainer = function () {
    const $img = $('.woocommerce-product-gallery__image img').first();
    if (!$img.length) return false;
    const parent = $img.closest('.woocommerce-product-gallery__image').get(0);
    if (!parent) return false;
    parent.style.position = 'relative';
    if (!Frenzy.state.container) {
      const c = document.createElement('div');
      c.className = 'frenzy-overlay-container';
      Object.assign(c.style, { position: 'absolute', inset: 0, zIndex: 30, pointerEvents: 'auto' });
      parent.appendChild(c);
      Frenzy.state.container = c;
    }
    return true;
  };

  Frenzy.ensureFieldsInsideForm = function () {
    const form = document.querySelector('form.cart');
    const block = document.querySelector('.frenzy-cart-block');
    if (form && block && !form.contains(block)) {
      form.appendChild(block);
      Frenzy.log('Moved Frenzy block into form.cart');
    }
  };

  window.Frenzy = Frenzy;
})(window, jQuery);
