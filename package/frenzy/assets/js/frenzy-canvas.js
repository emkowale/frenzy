/* Canvas capture of gallery + overlay */
(function (window, $) {
  const Frenzy = window.Frenzy;
  if (!Frenzy) return;

  function generateCanvasMockup() {
    const baseImg = document.querySelector('.woocommerce-product-gallery__image img');
    if (!baseImg) return Promise.resolve(null);
    Frenzy.actions.restoreOverlayIfLost();
    const s = Frenzy.state;
    const baseSrc = baseImg.currentSrc || baseImg.src || '';
    const baseW = baseImg.naturalWidth || Frenzy.const.BASE_W;
    const baseH = baseImg.naturalHeight || Frenzy.const.BASE_H;
    const scaleX = baseW / Frenzy.const.BASE_W;
    const scaleY = baseH / Frenzy.const.BASE_H;

    let tf = null;
    const transform = $('#frenzy_transform').val();
    if (transform) { try { tf = JSON.parse(transform); } catch (e) { tf = null; } }
    if (!tf && s.overlay) {
      const dims = Frenzy.getImageRects();
      if (dims) {
        const rect = s.overlay.getBoundingClientRect();
        const imgRect = dims.imgRect;
        tf = {
          x: Math.round(Math.max(0, rect.left - imgRect.left) * (Frenzy.const.BASE_W / imgRect.width)),
          y: Math.round(Math.max(0, rect.top - imgRect.top) * (Frenzy.const.BASE_H / imgRect.height)),
          w: Math.round(rect.width * (Frenzy.const.BASE_W / imgRect.width)),
          h: Math.round(rect.height * (Frenzy.const.BASE_H / imgRect.height)),
        };
        s.lastTransform = tf;
      }
    }

    const canvas = document.createElement('canvas');
    canvas.width = baseW; canvas.height = baseH;
    const ctx = canvas.getContext('2d');
    if (!ctx) return Promise.resolve(null);

    return new Promise((resolve) => {
      const base = new Image();
      base.crossOrigin = 'anonymous';
      base.onload = () => {
        ctx.drawImage(base, 0, 0, baseW, baseH);
        const finish = () => {
          canvas.toBlob((blob) => {
            if (!blob) return resolve(null);
            const fd = new FormData();
            fd.append('action', 'frenzy_save_canvas_mockup');
            fd.append('_ajax_nonce', window.frenzy_ajax.nonce);
            fd.append('product_id', window.frenzy_ajax.product_id || '');
            fd.append('image', blob, 'mockup.png');
            fetch(window.frenzy_ajax.ajax_url, { method: 'POST', body: fd })
              .then(r => r.json())
              .then(js => {
                Frenzy.log('frenzy_save_canvas_mockup response', js);
                if (js && js.success && js.data && js.data.mockup_url) {
                  $('#frenzy_mockup_url').val(js.data.mockup_url);
                  resolve(js.data.mockup_url); return;
                }
                resolve(null);
              })
              .catch((err) => { Frenzy.log('frenzy_save_canvas_mockup error', err); resolve(null); });
          }, 'image/png', 0.92);
        };

        if (s.overlay && tf) {
          const art = new Image();
          art.crossOrigin = 'anonymous';
          const bg = s.overlay.style.backgroundImage || '';
          const match = bg.match(/url\\(["']?(.*?)["']?\\)/i);
          const artUrl = match && match[1] ? match[1] : '';
          if (!artUrl) return finish();
          art.onload = () => {
            ctx.drawImage(art, Math.round(tf.x * scaleX), Math.round(tf.y * scaleY), Math.round(tf.w * scaleX), Math.round(tf.h * scaleY));
            finish();
          };
          art.onerror = () => finish();
          art.src = artUrl;
        } else {
          finish();
        }
      };
      base.onerror = (err) => { Frenzy.log('Base image load error', err); resolve(null); };
      if (baseSrc) { base.src = baseSrc; } else { resolve(null); }
    });
  }

  Frenzy.canvas = { generateCanvasMockup };
})(window, jQuery);
