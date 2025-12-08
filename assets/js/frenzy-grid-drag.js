/* Document-level handlers for grid drag/resize */
(function (window) {
  const Frenzy = window.Frenzy;
  if (!Frenzy || !Frenzy.state.canEditGrid) return;

  function onMove(e) {
    const s = Frenzy.state;
    if (!s.start || !s.printBox) return;
    const dx = (e.touches ? e.touches[0].clientX : e.clientX) - s.start.x;
    const dy = (e.touches ? e.touches[0].clientY : e.clientY) - s.start.y;
    if (s.dragMode === 'move') {
      let newLeft = s.start.left + dx - s.start.wrapLeft;
      let newTop = s.start.top + dy - s.start.wrapTop;
      newLeft = Math.max(s.start.offsetLeft, Math.min(newLeft, s.start.offsetLeft + s.start.imgWidth - s.printBox.offsetWidth));
      newTop = Math.max(s.start.offsetTop, Math.min(newTop, s.start.offsetTop + s.start.imgHeight - s.printBox.offsetHeight));
      s.printBox.style.left = `${newLeft}px`;
      s.printBox.style.top = `${newTop}px`;
    } else if (s.dragMode === 'resize') {
      let newW = Math.max(Frenzy.const.minBoxPx, s.start.width + dx);
      let newH = Math.max(Frenzy.const.minBoxPx, s.start.height + dy);
      newW = Math.min(newW, s.start.offsetLeft + s.start.imgWidth - (s.start.left - s.start.wrapLeft));
      newH = Math.min(newH, s.start.offsetTop + s.start.imgHeight - (s.start.top - s.start.wrapTop));
      s.printBox.style.width = `${newW}px`;
      s.printBox.style.height = `${newH}px`;
    }
  }

  function onUp() {
    const s = Frenzy.state;
    s.start = null; s.dragMode = null; if (s.printBox) s.printBox.style.cursor = 'grab';
    Frenzy.display.updatePrintBoxDisplay();
  }

  document.addEventListener('mousemove', onMove);
  document.addEventListener('mouseup', onUp);
  document.addEventListener('touchmove', onMove, { passive: false });
  document.addEventListener('touchend', onUp, { passive: false });
})(window);
