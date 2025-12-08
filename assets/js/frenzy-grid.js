/* Admin grid adjust UI */
(function (window, $) {
  const Frenzy = window.Frenzy;
  if (!Frenzy || !Frenzy.state.canEditGrid) return;
  let statusEl = null;
  let regionApplyRetries = 0;

  function setStatus(msg) {
    if (statusEl) statusEl.textContent = msg || '';
  }

  function applyRegionToBox(showHandle) {
    // Try to apply the current region to the print box; retry if image dims not ready
    const applied = Frenzy.printBox.ensurePrintBox(!!showHandle);
    if (!applied) {
      if (regionApplyRetries < 5) {
        regionApplyRetries += 1;
        setTimeout(() => applyRegionToBox(showHandle), 200);
      }
      return;
    }
    regionApplyRetries = 0;
    Frenzy.display.updatePrintBoxDisplay();
  }

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

  function fetchGrid() {
    const fd = new FormData();
    fd.append('action', 'frenzy_get_grid');
    fd.append('_ajax_nonce', (window.frenzy_ajax && window.frenzy_ajax.nonce) || '');
    fd.append('product_id', (window.frenzy_ajax && window.frenzy_ajax.product_id) || '');
    setStatus('Loading saved boundaries…');
    return fetch((window.frenzy_ajax && window.frenzy_ajax.ajax_url) || '/wp-admin/admin-ajax.php', { method: 'POST', body: fd })
      .then(async (r) => {
        if (!r.ok) {
          throw new Error('HTTP ' + r.status);
        }
        return r.json();
      })
      .then(resp => {
        if (resp && resp.success && resp.data && resp.data.grid) {
          const region = {
            x: Number(resp.data.grid.x) || Frenzy.state.region.x,
            y: Number(resp.data.grid.y) || Frenzy.state.region.y,
            w: Number(resp.data.grid.w) || Frenzy.state.region.w,
            h: Number(resp.data.grid.h) || Frenzy.state.region.h
          };
          if (region.w < 20 || region.h < 20) {
            region.x = 320; region.y = 250; region.w = 560; region.h = 650;
          }
          Frenzy.state.region = region;
          if (window.frenzy_ajax) window.frenzy_ajax.region = Frenzy.state.region;
          applyRegionToBox(false);
          setStatus('Boundaries loaded');
          setTimeout(() => { setStatus(''); }, 1500);
        } else {
          setStatus('No saved boundaries');
        }
      })
      .catch((err) => { setStatus('Error loading grid'); Frenzy.log('Grid fetch error', err); });
  }

  function bindAdminButtons() {
    const galleryWrapper = document.querySelector('.woocommerce-product-gallery');
    if (!galleryWrapper) return;
    if (!window.frenzy_ajax || !window.frenzy_ajax.nonce || !window.frenzy_ajax.product_id) {
      setStatus('Grid editing not available (missing product/nonce).');
      return;
    }
    if (document.getElementById('frenzy-save-grid')) { Frenzy.printBox.ensurePrintBox(true); return; }

    const btn = document.createElement('button');
    btn.id = 'frenzy-save-grid';
    btn.type = 'button';
    btn.textContent = 'Adjust Boundaries';
    btn.className = 'button alt';
    btn.style.marginTop = '8px'; btn.style.marginRight = '8px';

    const status = document.createElement('div');
    status.id = 'frenzy-save-grid-status';
    status.style.marginLeft = '0';
    status.style.marginTop = '6px';
    status.style.fontSize = '12px';
    status.style.color = '#111';
    statusEl = status;

    const holder = document.createElement('div');
    holder.style.marginTop = '8px';
    holder.appendChild(btn);
    holder.appendChild(status);
    galleryWrapper.parentNode.insertBefore(holder, galleryWrapper.nextSibling);

    let editing = false;
    btn.addEventListener('click', function () {
      const s = Frenzy.state;
      if (!editing) {
        Frenzy.log('Grid edit start');
        Frenzy.ensureContainer();
        Frenzy.printBox.ensurePrintBox(true);
      if (!s.printBox) { status.textContent = 'No image found'; Frenzy.log('No print box/container available'); return; }
        editing = true; s.isEditingGrid = true; s.printBoxVisible = true;
        applyRegionToBox(true);
        btn.textContent = 'Save Boundaries'; Frenzy.display.updatePrintBoxDisplay(); bindPrintBoxDragHandlers(); return;
      }
      if (!s.printBox) return;
      s.printBoxVisible = true;
      s.isEditingGrid = true;
      s.printBox.style.display = 'block';
      // Do NOT re-apply region here; it would overwrite the user's drag
      Frenzy.display.updatePrintBoxDisplay();
      const dims = Frenzy.getImageRects(); if (!dims || !dims.imgRect.width || !dims.imgRect.height) return;
      const rect = dims.imgRect; const scaleX = Frenzy.const.BASE_W / rect.width; const scaleY = Frenzy.const.BASE_H / rect.height;
      const boxRect = s.printBox.getBoundingClientRect();
      const styleLeft = parseFloat(s.printBox.style.left) || 0;
      const styleTop = parseFloat(s.printBox.style.top) || 0;
      const boxOffsetLeft = s.printBox.offsetLeft || styleLeft;
      const boxOffsetTop = s.printBox.offsetTop || styleTop;
      const left = (boxOffsetLeft - (dims.offsetLeft || 0));
      const top = (boxOffsetTop - (dims.offsetTop || 0));
      const boxW = Math.max(s.printBox.offsetWidth || 0, boxRect.width || 0, parseFloat(s.printBox.style.width) || 0);
      const boxH = Math.max(s.printBox.offsetHeight || 0, boxRect.height || 0, parseFloat(s.printBox.style.height) || 0);
      const newRegion = {
        x: Math.max(0, Math.round(left * scaleX)),
        y: Math.max(0, Math.round(top * scaleY)),
        w: Math.max(20, Math.round(boxW * scaleX)),
        h: Math.max(20, Math.round(boxH * scaleY))
      };
      status.textContent = 'Saving…';
      const fd = new FormData();
      fd.append('action', 'frenzy_save_grid'); fd.append('_ajax_nonce', (window.frenzy_ajax && window.frenzy_ajax.nonce) || ''); fd.append('product_id', (window.frenzy_ajax && window.frenzy_ajax.product_id) || '');
      fd.append('x', newRegion.x); fd.append('y', newRegion.y); fd.append('w', newRegion.w); fd.append('h', newRegion.h);
      fetch((window.frenzy_ajax && window.frenzy_ajax.ajax_url) || '/wp-admin/admin-ajax.php', { method: 'POST', body: fd })
        .then(async (r) => {
          if (!r.ok) { throw new Error('HTTP ' + r.status); }
          return r.json();
        })
        .then(resp => {
          if (!resp || !resp.success || !resp.data || !resp.data.grid) {
            const msg = resp && resp.data && resp.data.message ? resp.data.message : 'Save failed';
            throw new Error(msg);
          }
          const region = {
            x: Number(resp.data.grid.x) || newRegion.x,
            y: Number(resp.data.grid.y) || newRegion.y,
            w: Number(resp.data.grid.w) || newRegion.w,
            h: Number(resp.data.grid.h) || newRegion.h
          };
          Frenzy.state.region = region;
          if (window.frenzy_ajax) window.frenzy_ajax.region = Frenzy.state.region;
          applyRegionToBox(true);
          Frenzy.state.printBoxVisible = true;
          setStatus('Saved');
          setTimeout(() => { setStatus(''); }, 1500);
        })
        .catch((err) => {
          const msg = err && err.message ? err.message : 'Save failed';
          setStatus('Error: ' + msg);
          Frenzy.log('Grid save error', err);
        })
        .finally(() => {
          editing = false; Frenzy.state.isEditingGrid = false; btn.textContent = 'Adjust Boundaries'; Frenzy.display.updatePrintBoxDisplay();
        });
    });
  }

  Frenzy.grid = { bindAdminButtons, bindPrintBoxDragHandlers, fetchGrid, setStatus };
})(window, jQuery);
