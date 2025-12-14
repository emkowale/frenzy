/* Overlay drag/resize interactions */
(function (window) {
  const Frenzy = window.Frenzy;
  if (!Frenzy) return;

  function isOverlayCentered() {
    const s = Frenzy.state;
    if (!s.overlay || !s.printBox) return false;
    const boxLeft = parseFloat(s.printBox.style.left) || 0;
    const boxW = s.printBox.offsetWidth;
    const overlayLeft = parseFloat(s.overlay.style.left) || 0;
    const overlayW = s.overlay.offsetWidth;
    const boxCenter = boxLeft + (boxW / 2);
    const overlayCenter = overlayLeft + (overlayW / 2);
    return Math.abs(boxCenter - overlayCenter) <= Frenzy.const.snapThreshold;
  }

  function bindOverlayInteractions() {
    const s = Frenzy.state;
    if (!s.overlay) return;
    let dragging = false, resizing = false;
    let startX = 0, startY = 0, startLeft = 0, startTop = 0, startW = 0, startH = 0;
    let pointerActive = false;
    const getPointer = (e) => (e.touches && e.touches[0]) || (e.changedTouches && e.changedTouches[0]) || e;
    const overlayContains = (target) => s.overlay && (target === s.overlay || s.overlay.contains(target));
    const handleContains = (target) => s.overlayHandle && (target === s.overlayHandle || s.overlayHandle.contains(target));
    const deleteContains = (target) => s.deleteBtn && (target === s.deleteBtn || s.deleteBtn.contains(target));
    const startDragInteraction = (e) => {
      dragging = true;
      resizing = false;
      s.overlay.style.cursor = 'grabbing';
      const pt = getPointer(e);
      startX = pt.clientX; startY = pt.clientY;
      startLeft = parseFloat(s.overlay.style.left) || 0;
      startTop = parseFloat(s.overlay.style.top) || 0;
      e.preventDefault();
    };
    const startResizeInteraction = (e) => {
      resizing = true;
      dragging = false;
      const pt = getPointer(e);
      startX = pt.clientX; startY = pt.clientY;
      startW = s.overlay.offsetWidth; startH = s.overlay.offsetHeight;
      e.stopPropagation();
      e.preventDefault();
    };
    const onMove = (e) => {
      if (!dragging && !resizing) return;
      if (!s.printBox) return;
      const pt = getPointer(e);
      const boxLeft = parseFloat(s.printBox.style.left) || 0;
      const boxTop = parseFloat(s.printBox.style.top) || 0;
      const boxW = s.printBox.offsetWidth;
      const boxH = s.printBox.offsetHeight;
      if (dragging) {
        let nextLeft = startLeft + (pt.clientX - startX);
        let nextTop = startTop + (pt.clientY - startY);
        const boxCenter = boxLeft + (boxW / 2);
        const overlayCenter = nextLeft + (s.overlay.offsetWidth / 2);
        const snapped = Math.abs(overlayCenter - boxCenter) <= Frenzy.const.snapThreshold;
        if (snapped) { nextLeft = boxLeft + ((boxW - s.overlay.offsetWidth) / 2); Frenzy.display.setCenterLineVisible(true); } else { Frenzy.display.setCenterLineVisible(false); }
        nextLeft = Math.max(boxLeft, Math.min(nextLeft, boxLeft + boxW - s.overlay.offsetWidth));
        nextTop = Math.max(boxTop, Math.min(nextTop, boxTop + boxH - s.overlay.offsetHeight));
        s.overlay.style.left = `${nextLeft}px`; s.overlay.style.top = `${nextTop}px`;
      } else if (resizing) {
        const dx = pt.clientX - startX; const dy = pt.clientY - startY;
        const delta = Math.max(dx, dy);
        const ratio = s.artNatural.h / Math.max(s.artNatural.w, 1);
        const maxW = Math.max(20, (boxLeft + boxW) - (parseFloat(s.overlay.style.left) || 0));
        const maxH = Math.max(20, (boxTop + boxH) - (parseFloat(s.overlay.style.top) || 0));
        let newW = Math.max(20, startW + delta); let newH = newW * ratio;
        if (newW > maxW) { newW = maxW; newH = newW * ratio; }
        if (newH > maxH) { newH = maxH; newW = newH / ratio; }
        s.overlay.style.width = `${newW}px`; s.overlay.style.height = `${newH}px`;
        Frenzy.display.setCenterLineVisible(isOverlayCentered());
      }
      Frenzy.actions.saveTransform();
      e.preventDefault();
    };
    let overlayPointerId = null;
    let handlePointerId = null;
    const releaseOverlayCapture = () => {
      if (overlayPointerId !== null && s.overlay.releasePointerCapture) {
        s.overlay.releasePointerCapture(overlayPointerId);
      }
      overlayPointerId = null;
    };
    const releaseHandleCapture = () => {
      if (handlePointerId !== null && s.overlayHandle && s.overlayHandle.releasePointerCapture) {
        s.overlayHandle.releasePointerCapture(handlePointerId);
      }
      handlePointerId = null;
    };
    const onUp = () => {
      dragging = false;
      resizing = false;
      s.overlay.style.cursor = 'grab';
      pointerActive = false;
      releaseOverlayCapture();
      releaseHandleCapture();
    };
    const pointerSupported = !!window.PointerEvent;
    const moveWrapper = (handler, shouldSkip) => (e) => {
      if (shouldSkip && shouldSkip(e)) return;
      handler(e);
    };
    const pointerDownHandler = (e) => {
      if (deleteContains(e.target)) return;
      if (handleContains(e.target)) {
        if (e.pointerType) {
          pointerActive = true;
          handlePointerId = e.pointerId;
          if (s.overlayHandle.setPointerCapture) s.overlayHandle.setPointerCapture(handlePointerId);
        }
        startResizeInteraction(e);
      } else if (overlayContains(e.target)) {
        if (e.pointerType) {
          pointerActive = true;
          overlayPointerId = e.pointerId;
          if (s.overlay.setPointerCapture) s.overlay.setPointerCapture(overlayPointerId);
        }
        startDragInteraction(e);
      }
    };
    const touchDownHandler = (e) => {
      if (pointerActive) return;
      if (deleteContains(e.target)) return;
      if (handleContains(e.target)) return startResizeInteraction(e);
      if (overlayContains(e.target)) startDragInteraction(e);
    };
    const mouseDownHandler = (e) => {
      if (pointerActive) return;
      if (deleteContains(e.target)) return;
      if (handleContains(e.target)) return startResizeInteraction(e);
      if (overlayContains(e.target)) startDragInteraction(e);
    };
    if (pointerSupported) {
      document.addEventListener('pointerdown', pointerDownHandler, { passive: false, capture: true });
      document.addEventListener('pointermove', moveWrapper(onMove, null), { passive: false });
      document.addEventListener('pointerup', () => { onUp(); }, { passive: false });
      document.addEventListener('pointercancel', () => { onUp(); }, { passive: false });
      document.addEventListener('touchstart', touchDownHandler, { passive: false, capture: true });
      document.addEventListener('mousedown', mouseDownHandler, { capture: true });
      document.addEventListener('touchmove', moveWrapper(onMove, () => pointerActive), { passive: false });
      document.addEventListener('touchend', () => { if (pointerActive) return; onUp(); }, { passive: false });
      document.addEventListener('touchcancel', () => { if (pointerActive) return; onUp(); }, { passive: false });
      document.addEventListener('mousemove', moveWrapper(onMove, () => pointerActive));
      document.addEventListener('mouseup', () => { if (pointerActive) return; onUp(); });
    } else {
      document.addEventListener('mousedown', mouseDownHandler);
      document.addEventListener('touchstart', touchDownHandler, { passive: false });
      document.addEventListener('mousemove', onMove);
      document.addEventListener('touchmove', onMove, { passive: false });
      document.addEventListener('mouseup', onUp);
      document.addEventListener('touchend', onUp);
      document.addEventListener('touchcancel', onUp);
    }
    s.deleteBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (s.overlay && s.overlay.parentNode) s.overlay.parentNode.removeChild(s.overlay);
      s.overlayHandle = null; if (s.printBox && !s.canEditGrid) s.printBoxVisible = false;
      s.artNatural = { w: 0, h: 0 }; $('#frenzy_original_url').val(''); $('#frenzy_mockup_url').val(''); $('#frenzy_transform').val('');
      s.overlay = null; s.lastOverlaySrc = ''; s.lastTransform = null;
      Frenzy.display.setCenterLineVisible(false); Frenzy.display.updatePrintBoxDisplay();
      Frenzy.log('Overlay removed by user');
    });
  }

  Frenzy.actions = Object.assign(Frenzy.actions || {}, { bindOverlayInteractions, isOverlayCentered });
})(window);
