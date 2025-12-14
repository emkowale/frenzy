/* Entry point wiring */
(function (window, $) {
  const Frenzy = window.Frenzy;
  if (!Frenzy) return;

  function disableZoom() {
    const $gal = $('.woocommerce-product-gallery');
    $gal.removeClass('zoom-enabled');
    $gal.find('.woocommerce-product-gallery__trigger, .zoomImg').remove();
    $gal.find('.woocommerce-product-gallery__wrapper a').each(function () { const $a = $(this); const $img = $a.find('img'); if ($img.length) $a.replaceWith($img); });
    $gal.find('.woocommerce-product-gallery__image, .woocommerce-product-gallery__wrapper').css({ cursor: 'default', pointerEvents: 'auto' });
    $gal.find('img').css({ cursor: 'default', pointerEvents: 'auto' }).each(function () { $(this).removeAttr('data-large_image data-large_image_width data-large_image_height data-src data-srcset data-zoom-image'); });
    $gal.find('.flex-viewport, .woocommerce-product-gallery__wrapper, .woocommerce-product-gallery__image').off(); $gal.find('img').off(); $gal.off();
    $(document).off('found_variation.wc-variation-form'); $(document).off('click', '.woocommerce-product-gallery__trigger');
    const obs = new MutationObserver(() => { document.querySelectorAll('.woocommerce-product-gallery__trigger, .zoomImg').forEach(el => el.remove()); });
    const target = document.querySelector('.woocommerce-product-gallery'); if (target) obs.observe(target, { childList: true, subtree: true });
  }

  function bindHoverDisplay() {
    const galleryHoverTarget = document.querySelector('.woocommerce-product-gallery__image');
    if (Frenzy.state.hoverDevice && galleryHoverTarget) {
      galleryHoverTarget.addEventListener('mouseenter', () => { Frenzy.state.hoverInGallery = true; Frenzy.display.updatePrintBoxDisplay(); });
      galleryHoverTarget.addEventListener('mouseleave', () => { Frenzy.state.hoverInGallery = false; if (!Frenzy.state.overlay && !Frenzy.state.isEditingGrid) Frenzy.state.printBoxVisible = false; Frenzy.display.updatePrintBoxDisplay(); });
    }
    // On touch devices, hide after inactivity; bump on any interaction in the gallery
    if (!Frenzy.state.hoverDevice && galleryHoverTarget) {
      ['touchstart', 'touchmove', 'mousedown'].forEach(evt => {
        galleryHoverTarget.addEventListener(evt, () => {
          Frenzy.display.bumpMobileActivity();
          Frenzy.state.printBoxVisible = true;
          Frenzy.display.updatePrintBoxDisplay();
        }, { passive: true });
      });
      ['pointerdown', 'pointermove'].forEach(evt => {
        galleryHoverTarget.addEventListener(evt, (e) => {
          if (e.pointerType === 'mouse') return;
          Frenzy.display.bumpMobileActivity();
          Frenzy.state.printBoxVisible = true;
          Frenzy.display.updatePrintBoxDisplay();
        }, { passive: true });
      });
      Frenzy.display.bumpMobileActivity();
    }
  }

  function reattachAfterVariation() {
    setTimeout(() => {
      Frenzy.ensureContainer();
      Frenzy.ensureFieldsInsideForm();
      Frenzy.actions.restoreOverlayIfLost();
      if (Frenzy.state.overlay && Frenzy.state.lastTransform) Frenzy.actions.applyTransform(Frenzy.state.lastTransform);
      Frenzy.printBox.ensurePrintBox(false);
      Frenzy.display.updatePrintBoxDisplay();
      Frenzy.display.detectBackgroundColor();
    }, 50);
  }

  $(function () {
    Frenzy.log('Frenzy init', { productId: window.frenzy_ajax && window.frenzy_ajax.product_id, region: window.frenzy_ajax && window.frenzy_ajax.region });
    disableZoom();
    Frenzy.upload.bindUploadButtons();
    Frenzy.submit.bindSubmit();
    Frenzy.ensureFieldsInsideForm();
    Frenzy.ensureContainer();
    Frenzy.printBox.ensurePrintBox(false);
    if (Frenzy.state.canEditGrid) {
      Frenzy.grid.bindAdminButtons();
      Frenzy.grid.fetchGrid();
    }
    Frenzy.display.detectBackgroundColor();
    bindHoverDisplay();
    const baseImg = document.querySelector('.woocommerce-product-gallery__image img');
    if (baseImg) {
      baseImg.addEventListener('load', () => { Frenzy.ensureContainer(); Frenzy.printBox.ensurePrintBox(false); Frenzy.display.detectBackgroundColor(); });
      if (baseImg.complete) { Frenzy.ensureContainer(); Frenzy.printBox.ensurePrintBox(false); Frenzy.display.detectBackgroundColor(); }
    }
    window.addEventListener('resize', () => { Frenzy.ensureContainer(); Frenzy.printBox.ensurePrintBox(false); Frenzy.display.detectBackgroundColor(); });
    $(document.body).on('found_variation.wc-variation-form reset_image', reattachAfterVariation);
  });
})(window, jQuery);
