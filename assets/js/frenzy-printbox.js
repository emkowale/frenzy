/* Print box creation and positioning */
(function (window, $) {
  const Frenzy = window.Frenzy;
  if (!Frenzy) return;

  function ensurePrintBox(showHandle) {
    if (!Frenzy.ensureContainer()) return;
    const dims = Frenzy.getImageRects();
    if (!dims || !dims.imgRect.width || !dims.imgRect.height) return false;
    const { imgRect, offsetLeft, offsetTop } = dims;
    const scaleX = imgRect.width / Frenzy.const.BASE_W;
    const scaleY = imgRect.height / Frenzy.const.BASE_H;
    const s = Frenzy.state;
    let boxW = (s.region.w || Frenzy.const.minBoxPx) * scaleX;
    let boxH = (s.region.h || Frenzy.const.minBoxPx) * scaleY;
    boxW = Math.max(Frenzy.const.minBoxPx, boxW);
    boxH = Math.max(Frenzy.const.minBoxPx, boxH);
    let left = (s.region.x || 0) * scaleX;
    let top = (s.region.y || 0) * scaleY;
    left = Math.max(0, Math.min(left, imgRect.width - boxW));
    top = Math.max(0, Math.min(top, imgRect.height - boxH));

    if (!s.printBox) {
      const pb = document.createElement('div');
      pb.className = 'frenzy-print-box';
      Object.assign(pb.style, { position: 'absolute', border: `2px dashed ${s.printBorderColor}`, pointerEvents: 'none', boxSizing: 'border-box', zIndex: 31, display: 'none', cursor: 'default' });
      s.container.appendChild(pb);
      s.printBox = pb;
    }
    if (!s.centerLine) {
      const cl = document.createElement('div');
      cl.className = 'frenzy-print-box-center';
      Object.assign(cl.style, { position: 'absolute', top: 0, bottom: 0, left: '50%', transform: 'translateX(-50%)', borderLeft: `2px dashed ${s.printBorderColor}`, pointerEvents: 'none', opacity: 0.6, display: 'none' });
      s.printBox.appendChild(cl);
      s.centerLine = cl;
    }

    Object.assign(s.printBox.style, {
      left: `${offsetLeft + left}px`,
      top: `${offsetTop + top}px`,
      width: `${boxW}px`,
      height: `${boxH}px`,
    });
    if (showHandle) {
      s.printBox.style.pointerEvents = 'auto';
      s.printBox.style.zIndex = '9999';
      s.printBoxVisible = true;
      s.printBox.style.cursor = 'grab';
    }

    if (showHandle && !s.resizeHandle) {
      const rh = document.createElement('div');
      rh.className = 'frenzy-resize-handle';
      Object.assign(rh.style, { position: 'absolute', width: '18px', height: '18px', border: `2px solid ${s.printBorderColor}`, background: 'rgba(34,34,34,0.8)', right: '-14px', bottom: '-14px', cursor: 'nwse-resize', pointerEvents: 'auto', boxSizing: 'border-box', touchAction: 'none' });
      s.printBox.appendChild(rh);
      s.resizeHandle = rh;
    }
    if (!showHandle && s.resizeHandle) {
      s.resizeHandle.style.display = 'none';
    }
    Frenzy.display.updatePrintBoxDisplay();
    Frenzy.display.updatePrintBoxColor(s.printBorderColor);
    return true;
  }

  Frenzy.printBox = { ensurePrintBox };
})(window, jQuery);
