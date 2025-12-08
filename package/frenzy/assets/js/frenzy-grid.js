/* Admin grid adjust UI */
(function (window, $) {
  const Frenzy = window.Frenzy;
  if (!Frenzy || !Frenzy.state.canEditGrid) return;

  function bindPrintBoxDragHandlers() {
    const s = Frenzy.state;
    if (!s.printBox) return;
    s.printBox.onmousedown = function (e) {
      if (!s.canEditGrid || !s.isEditingGrid) return;
      e.preventDefault();
      s.dragMode = (s.resizeHandle && (e.target === s.resizeHandle || s.resizeHandle.contains(e.target))) ? 'resize' : 'move';
      const rect = s.printBox.getBoundingClientRect();
      const wrapRect = s.printBox.parentNode.getBoundingClientRect();
      const imgRect = document.querySelector('.woocommerce-product-gallery__image img').getBoundingClientRect();
      s.start = { x: e.clientX, y: e.clientY, left: rect.left, top: rect.top, width: rect.width, height: rect.height, wrapLeft: wrapRect.left, wrapTop: wrapRect.top, offsetLeft: imgRect.left - wrapRect.left, offsetTop: imgRect.top - wrapRect.top, imgWidth: imgRect.width, imgHeight: imgRect.height };
    };
  }

  function bindAdminButtons() {
    const galleryWrapper = document.querySelector('.woocommerce-product-gallery');
    if (!galleryWrapper) return;
    if (document.getElementById('frenzy-save-grid')) { Frenzy.printBox.ensurePrintBox(true); return; }

    const btn = document.createElement('button');
    btn.id = 'frenzy-save-grid';
    btn.type = 'button';
    btn.textContent = 'Adjust Boundaries';
    btn.className = 'button alt';
    btn.style.marginTop = '8px'; btn.style.marginRight = '8px';

    const status = document.createElement('span');
    status.id = 'frenzy-save-grid-status';
    status.style.marginLeft = '8px'; status.style.fontSize = '12px'; status.style.color = '#111';

    const holder = document.createElement('div');
    holder.style.marginTop = '8px';
    holder.appendChild(btn); holder.appendChild(status);
    galleryWrapper.parentNode.insertBefore(holder, galleryWrapper.nextSibling);

    let editing = false;
    btn.addEventListener('click', function () {
      const s = Frenzy.state;
      if (!editing) {
        Frenzy.ensureContainer(); Frenzy.printBox.ensurePrintBox(true);
        if (!s.printBox) return;
        editing = true; s.isEditingGrid = true; btn.textContent = 'Save Boundaries'; Frenzy.display.updatePrintBoxDisplay(); bindPrintBoxDragHandlers(); return;
      }
      if (!s.printBox) return;
      const dims = Frenzy.getImageRects(); if (!dims || !dims.imgRect.width || !dims.imgRect.height) return;
      const rect = dims.imgRect; const scaleX = Frenzy.const.BASE_W / rect.width; const scaleY = Frenzy.const.BASE_H / rect.height;
      const left = (parseFloat(s.printBox.style.left) || 0) - dims.offsetLeft;
      const top = (parseFloat(s.printBox.style.top) || 0) - dims.offsetTop;
      const newRegion = { x: Math.max(0, Math.round(left * scaleX)), y: Math.max(0, Math.round(top * scaleY)), w: Math.max(1, Math.round(s.printBox.offsetWidth * scaleX)), h: Math.max(1, Math.round(s.printBox.offsetHeight * scaleY)) };
      status.textContent = 'Saving…';
      const fd = new FormData();
      fd.append('action', 'frenzy_save_grid'); fd.append('_ajax_nonce', (window.frenzy_ajax && window.frenzy_ajax.nonce) || ''); fd.append('product_id', (window.frenzy_ajax && window.frenzy_ajax.product_id) || '');
      fd.append('x', newRegion.x); fd.append('y', newRegion.y); fd.append('w', newRegion.w); fd.append('h', newRegion.h);
      fetch((window.frenzy_ajax && window.frenzy_ajax.ajax_url) || '/wp-admin/admin-ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
          if (resp && resp.success && resp.data && resp.data.grid) {
            Frenzy.state.region = { x: Number(resp.data.grid.x) || newRegion.x, y: Number(resp.data.grid.y) || newRegion.y, w: Number(resp.data.grid.w) || newRegion.w, h: Number(resp.data.grid.h) || newRegion.h };
            if (window.frenzy_ajax) window.frenzy_ajax.region = Frenzy.state.region;
            status.textContent = 'Saved';
            setTimeout(() => { status.textContent = ''; }, 1500);
          } else {
            status.textContent = resp && resp.data && resp.data.message ? resp.data.message : 'Save failed';
          }
        })
        .catch(() => { status.textContent = 'Save failed'; })
        .finally(() => {
          editing = false; Frenzy.state.isEditingGrid = false; btn.textContent = 'Adjust Boundaries'; if (Frenzy.state.printBox) Frenzy.state.printBoxVisible = false; Frenzy.display.updatePrintBoxDisplay();
        });
    });
  }

  Frenzy.grid = { bindAdminButtons, bindPrintBoxDragHandlers };
})(window, jQuery);
