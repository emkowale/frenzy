/* Upload button bindings and file handling */
(function (window, $) {
  const Frenzy = window.Frenzy;
  if (!Frenzy) return;

  const uploadButtonId = 'frenzy-upload-button';
  const uploadDisabledTitle = 'You have already uploaded an image. To select a different image, delete the first by clicking or touching the red X in the top left corner of the image.';
  let observer = null;

  function setUploadButtonAvailability(enabled) {
    const btn = document.getElementById(uploadButtonId);
    if (!btn) return;
    btn.disabled = !enabled;
    if (enabled) {
      btn.removeAttribute('title');
    } else {
      btn.setAttribute('title', uploadDisabledTitle);
    }
  }

  function disableUploadButton() {
    setUploadButtonAvailability(false);
  }

  function enableUploadButton() {
    setUploadButtonAvailability(true);
  }

  function syncUploadButtonWithOverlay() {
    const hasOverlay = !!document.querySelector('.frenzy-art');
    setUploadButtonAvailability(!hasOverlay);
  }

  function observeOverlayChanges() {
    if (observer) return;
    const gallery = document.querySelector('.woocommerce-product-gallery');
    if (!gallery) {
      requestAnimationFrame(observeOverlayChanges);
      return;
    }
    observer = new MutationObserver(syncUploadButtonWithOverlay);
    observer.observe(gallery, { childList: true, subtree: true });
    syncUploadButtonWithOverlay();
  }

  function handleFile(file) {
    if (!file) return;
    const typeOk = /^image\/(png|jpe?g|webp)$/i.test(file.type);
    if (!typeOk) { alert('Please upload a PNG, JPG, or WebP image.'); return; }

    const reader = new FileReader();
    reader.onload = function (ev) {
      const url = ev.target.result;
      const img = new Image();
      img.onload = function () {
        Frenzy.state.artNatural = { w: img.naturalWidth, h: img.naturalHeight };
        Frenzy.actions.createOverlay(url);
        if (window.matchMedia('(max-width: 768px)').matches) window.scrollTo({ top: 0, behavior: 'smooth' });
      };
      img.src = url;
    };
    reader.readAsDataURL(file);
    Frenzy.api.uploadFile(file);
  }

  function bindUploadButtons() {
    $(document).off('.frenzyUpload');
    $(document).on('click.frenzyUpload', '#frenzy-upload-button', function (e) {
      e.preventDefault();
      const input = document.getElementById('frenzy-upload');
      if (input) input.click();
    });
    $(document).on('change.frenzyUpload', '#frenzy-upload', function () {
      const file = this.files && this.files[0];
      handleFile(file);
      this.value = '';
    });
    observeOverlayChanges();
  }

  Frenzy.upload = { bindUploadButtons, handleFile };
  Frenzy.uploadButton = { disable: disableUploadButton, enable: enableUploadButton, tooltip: uploadDisabledTitle };
})(window, jQuery);
