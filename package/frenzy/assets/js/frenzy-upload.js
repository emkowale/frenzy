/* Upload button bindings and file handling */
(function (window, $) {
  const Frenzy = window.Frenzy;
  if (!Frenzy) return;

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
  }

  Frenzy.upload = { bindUploadButtons, handleFile };
})(window, jQuery);
