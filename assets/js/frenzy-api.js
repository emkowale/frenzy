/* AJAX helpers for mockup endpoints */
(function (window, $) {
  const Frenzy = window.Frenzy;
  if (!Frenzy) return;

  function generateServerMockup() {
    const originalUrl = $('#frenzy_original_url').val();
    const transform = $('#frenzy_transform').val();
    if (!originalUrl || !transform) return Promise.resolve(null);
    Frenzy.log('generateServerMockup start', { originalUrl, transform });
    const data = new FormData();
    data.append('action', 'frenzy_generate_mockup');
    data.append('_ajax_nonce', window.frenzy_ajax.nonce);
    data.append('product_id', window.frenzy_ajax.product_id || '');
    data.append('original_url', originalUrl);
    data.append('transform', transform);
    return fetch(window.frenzy_ajax.ajax_url, { method: 'POST', body: data })
      .then(r => r.json())
      .then(js => {
        Frenzy.log('frenzy_generate_mockup (server) response', js);
        if (js && js.success && js.data && js.data.mockup_url) {
          $('#frenzy_mockup_url').val(js.data.mockup_url);
          $('#frenzy_original_url').val(js.data.original_url || originalUrl);
          if (js.data.transform) $('#frenzy_transform').val(JSON.stringify(js.data.transform));
          return js.data.mockup_url;
        }
        const message = (js && js.data && js.data.message) || 'Mockup failed';
        Frenzy.log('frenzy_generate_mockup (server) missing mockup_url', js);
        throw new Error(message);
      })
      .catch((err) => { Frenzy.log('frenzy_generate_mockup (server) error', err); return null; });
  }

  function saveGalleryImage() {
    const img = document.querySelector('.woocommerce-product-gallery__image img');
    if (!img) return Promise.resolve(null);
    const src = img.currentSrc || img.src || '';
    if (!src) return Promise.resolve(null);
    const fd = new FormData();
    fd.append('action', 'frenzy_save_canvas_mockup');
    fd.append('_ajax_nonce', window.frenzy_ajax.nonce);
    fd.append('product_id', window.frenzy_ajax.product_id || '');
    fd.append('image_url', src);
    return fetch(window.frenzy_ajax.ajax_url, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(js => {
        Frenzy.log('saveGalleryImage response', js);
        if (js && js.success && js.data && js.data.mockup_url) {
          $('#frenzy_mockup_url').val(js.data.mockup_url);
          return js.data.mockup_url;
        }
        return null;
      })
      .catch((err) => { Frenzy.log('saveGalleryImage error', err); return null; });
  }

  function uploadFile(file) {
    const fd = new FormData();
    fd.append('action', 'frenzy_generate_mockup');
    fd.append('_ajax_nonce', window.frenzy_ajax.nonce);
    fd.append('product_id', window.frenzy_ajax.product_id || '');
    fd.append('image', file);
    Frenzy.log('uploadFile start', { name: file && file.name, size: file && file.size });
    return fetch(window.frenzy_ajax.ajax_url, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        Frenzy.log('frenzy_generate_mockup response', resp);
        if (resp && resp.success && resp.data) {
          if (resp.data.original_url) $('#frenzy_original_url').val(resp.data.original_url);
          if (resp.data.mockup_url) $('#frenzy_mockup_url').val(resp.data.mockup_url);
          if (resp.data.transform) {
            $('#frenzy_transform').val(JSON.stringify(resp.data.transform));
            Frenzy.state.lastTransform = resp.data.transform;
          }
          // Immediately build a canvas-based mockup so we have the product+art composite
          Frenzy.canvas.generateCanvasMockup().then((url) => {
            Frenzy.log('uploadFile canvas composite', url);
            if (url) $('#frenzy_mockup_url').val(url);
          });
        }
        return resp;
      })
      .catch((err) => { Frenzy.log('frenzy_generate_mockup error', err); return null; });
  }

  Frenzy.api = { generateServerMockup, saveGalleryImage, uploadFile };
})(window, jQuery);
