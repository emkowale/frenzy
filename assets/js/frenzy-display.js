/* Display helpers: print box and background detection */
(function (window, $) {
  const Frenzy = window.Frenzy;
  if (!Frenzy) return;

  function updatePrintBoxColor(color) {
    Frenzy.state.printBorderColor = color;
    const { printBox, resizeHandle, centerLine } = Frenzy.state;
    if (printBox) printBox.style.border = `2px dashed ${color}`;
    if (resizeHandle) {
      const isLight = color.includes('255,255,255') || color === '#fff' || color === 'white';
      resizeHandle.style.borderColor = color;
      resizeHandle.style.background = isLight ? 'rgba(34,34,34,0.85)' : 'rgba(255,255,255,0.85)';
    }
    if (centerLine) {
      centerLine.style.borderLeft = `2px dashed ${color}`;
      centerLine.style.background = 'none';
    }
  }

  function detectBackgroundColor() {
    const imgEl = $('.woocommerce-product-gallery__image img').first()[0];
    if (!imgEl || !imgEl.complete) return;
    try {
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      if (!ctx) return;
      canvas.width = 20; canvas.height = 20;
      ctx.drawImage(imgEl, 0, 0, 20, 20);
      const data = ctx.getImageData(0, 0, 20, 20).data;
      let total = 0;
      for (let i = 0; i < data.length; i += 4) {
        total += (0.299 * data[i]) + (0.587 * data[i + 1]) + (0.114 * data[i + 2]);
      }
      const avg = total / (data.length / 4);
      const color = avg > 180 ? 'rgba(0,0,0,0.9)' : 'rgba(255,255,255,0.9)';
      updatePrintBoxColor(color);
    } catch (e) { /* ignore */ }
  }

  function shouldShowPrintBox() {
    const s = Frenzy.state;
    if (!s.printBox) return false;
    if (!s.hoverDevice) return (s.overlay || s.printBoxVisible || s.isEditingGrid);
    if (!s.hoverInGallery) return false;
    return s.overlay || s.printBoxVisible || s.isEditingGrid;
  }

  function updatePrintBoxDisplay() {
    const s = Frenzy.state;
    if (!s.printBox) return;
    const show = shouldShowPrintBox();
    s.printBox.style.display = show ? 'block' : 'none';
    s.printBox.style.pointerEvents = (s.isEditingGrid ? 'auto' : 'none');
    if (s.centerLine) s.centerLine.style.display = (show && s.centerLineVisible) ? 'block' : 'none';
    const showControls = (!s.hoverDevice || s.hoverInGallery || s.isEditingGrid);
    if (s.deleteBtn) s.deleteBtn.style.display = showControls ? 'flex' : 'none';
    if (s.overlayHandle) s.overlayHandle.style.display = showControls ? 'block' : 'none';
    if (s.resizeHandle) s.resizeHandle.style.display = (s.isEditingGrid && show) ? 'block' : 'none';
    if (s.overlay) s.overlay.style.outline = showControls ? '2px dashed #fff' : 'none';
  }

  function setCenterLineVisible(show) {
    Frenzy.state.centerLineVisible = !!show;
    updatePrintBoxDisplay();
  }

  Frenzy.display = { updatePrintBoxColor, detectBackgroundColor, updatePrintBoxDisplay, setCenterLineVisible };
})(window, jQuery);
