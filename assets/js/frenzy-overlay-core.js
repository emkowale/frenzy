/* Overlay creation, transforms, restore */
(function (window, $) {
  const Frenzy = window.Frenzy;
  if (!Frenzy) return;

  function saveTransform() {
    const s = Frenzy.state;
    if (!s.overlay) return;
    const dims = Frenzy.getImageRects();
    if (!dims || !dims.imgRect.width || !dims.imgRect.height) return;
    const rect = dims.imgRect;
    const scaleX = Frenzy.const.BASE_W / rect.width;
    const scaleY = Frenzy.const.BASE_H / rect.height;
    const left = (parseFloat(s.overlay.style.left) || 0) - dims.offsetLeft;
    const top = (parseFloat(s.overlay.style.top) || 0) - dims.offsetTop;
    const data = { x: Math.round(Math.max(0, left) * scaleX), y: Math.round(Math.max(0, top) * scaleY), w: Math.round(s.overlay.offsetWidth * scaleX), h: Math.round(s.overlay.offsetHeight * scaleY) };
    $('#frenzy_transform').val(JSON.stringify(data));
    s.lastTransform = data;
  }

  function applyTransform(tf) {
    const s = Frenzy.state;
    if (!s.overlay || !tf) return;
    const dims = Frenzy.getImageRects();
    if (!dims || !dims.imgRect.width || !dims.imgRect.height) return;
    const scaleX = dims.imgRect.width / Frenzy.const.BASE_W;
    const scaleY = dims.imgRect.height / Frenzy.const.BASE_H;
    Object.assign(s.overlay.style, {
      width: `${Math.max(1, tf.w * scaleX)}px`,
      height: `${Math.max(1, tf.h * scaleY)}px`,
      left: `${dims.offsetLeft + (tf.x * scaleX)}px`,
      top: `${dims.offsetTop + (tf.y * scaleY)}px`,
    });
    Frenzy.display.setCenterLineVisible(Frenzy.actions.isOverlayCentered());
    Frenzy.display.updatePrintBoxDisplay();
  }

  function createOverlay(imgUrl) {
    if (!Frenzy.ensureContainer()) return;
    Frenzy.printBox.ensurePrintBox(false);
    const s = Frenzy.state;
    const overlay = document.createElement('div');
    overlay.className = 'frenzy-art';
    Object.assign(overlay.style, {
      position: 'absolute',
      outline: '2px dashed #fff',
      boxSizing: 'border-box',
      cursor: 'grab',
      userSelect: 'none',
      WebkitUserSelect: 'none',
      MozUserSelect: 'none',
      WebkitTouchCallout: 'none',
      WebkitUserDrag: 'none',
      pointerEvents: 'auto',
      maxWidth: `${(s.region.w / Frenzy.const.BASE_W) * (s.container.clientWidth || 1)}px`,
      overflow: 'visible',
      touchAction: 'none',
      backgroundImage: `url(${imgUrl})`,
      backgroundRepeat: 'no-repeat',
      backgroundSize: 'contain',
      backgroundPosition: 'center',
    });
    s.container.appendChild(overlay);
    s.overlay = overlay;
    overlay.setAttribute('draggable', 'false');

    const del = document.createElement('div');
    del.className = 'frenzy-delete-btn';
    del.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><rect x="0" y="0" width="18" height="18" rx="3" fill="#c00"/><path d="M4 4l10 10M14 4L4 14" stroke="#fff" stroke-width="2.6" stroke-linecap="round"/></svg>';
    Object.assign(del.style, { position: 'absolute', width: '20px', height: '20px', top: '-14px', left: '-14px', cursor: 'pointer', pointerEvents: 'auto', display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'transparent' });
    overlay.appendChild(del);
    s.deleteBtn = del;

    if (!s.overlayHandle) {
      const oh = document.createElement('div');
      oh.className = 'frenzy-art-resize';
      Object.assign(oh.style, {
        position: 'absolute',
        width: '16px',
        height: '16px',
        right: '-12px',
        bottom: '-12px',
        border: '2px solid #fff',
        background: 'rgba(34,34,34,0.8)',
        cursor: 'nwse-resize',
        pointerEvents: 'auto',
        boxSizing: 'border-box',
        borderRadius: '2px',
        touchAction: 'none',
        userSelect: 'none',
        WebkitUserSelect: 'none',
        MozUserSelect: 'none',
        WebkitTouchCallout: 'none',
        WebkitUserDrag: 'none',
      });
      overlay.appendChild(oh);
      s.overlayHandle = oh;
      oh.setAttribute('draggable', 'false');
    }

    const dims = Frenzy.getImageRects();
    const scaleX = dims ? (dims.imgRect.width / Frenzy.const.BASE_W) : 1;
    const scaleY = dims ? (dims.imgRect.height / Frenzy.const.BASE_H) : 1;
    const boxWPx = (s.region.w * scaleX) || Frenzy.const.minBoxPx;
    const boxHPx = (s.region.h * scaleY) || Frenzy.const.minBoxPx;
    const ratio = s.artNatural.h / Math.max(s.artNatural.w, 1);
    let initW = boxWPx; let initH = initW * ratio;
    if (initH > boxHPx) { initH = boxHPx; initW = initH / ratio; }
    overlay.style.width = `${initW}px`; overlay.style.height = `${initH}px`;
    if (dims) {
      const boxLeftPx = dims.offsetLeft + (s.region.x * scaleX);
      const boxTopPx = dims.offsetTop + (s.region.y * scaleY);
      overlay.style.left = `${boxLeftPx + (boxWPx - initW) / 2}px`;
      overlay.style.top = `${boxTopPx + (boxHPx - initH) / 2}px`;
    }
    Frenzy.display.setCenterLineVisible(true);
    Frenzy.display.detectBackgroundColor();
    s.printBoxVisible = true;
    Frenzy.display.updatePrintBoxDisplay();
    Frenzy.actions.bindOverlayInteractions();
    saveTransform();
    s.lastOverlaySrc = imgUrl;
  }

  function restoreOverlayIfLost() {
    const s = Frenzy.state;
    if (s.overlay && document.body.contains(s.overlay)) return;
    if (!s.lastOverlaySrc) return;
    createOverlay(s.lastOverlaySrc);
    let tf = s.lastTransform;
    const stored = $('#frenzy_transform').val();
    if (!tf && stored) { try { tf = JSON.parse(stored); } catch (e) {} }
    if (tf) applyTransform(tf);
  }

  Frenzy.actions = Object.assign(Frenzy.actions || {}, { createOverlay, applyTransform, restoreOverlayIfLost, saveTransform });
})(window, jQuery);
