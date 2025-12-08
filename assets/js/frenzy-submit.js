/* Form submission chain */
(function (window, $) {
  const Frenzy = window.Frenzy;
  if (!Frenzy) return;

  function bindSubmit() {
    $('form.cart').on('submit.frenzyMock', function (e) {
      e.preventDefault();
      const form = this;
      Frenzy.log('Cart submit intercepted; current fields', {
        mockup: $('#frenzy_mockup_url').val(),
        original: $('#frenzy_original_url').val(),
        transform: $('#frenzy_transform').val()
      });
      Frenzy.canvas.generateCanvasMockup()
        .then((canvasUrl) => { Frenzy.log('generateCanvasMockup result', canvasUrl); return canvasUrl || Frenzy.api.generateServerMockup(); })
        .then((maybeUrl) => { Frenzy.log('generateServerMockup result', maybeUrl); const current = $('#frenzy_mockup_url').val() || maybeUrl; return current || Frenzy.api.saveGalleryImage(); })
        .then((finalUrl) => {
          Frenzy.log('Final mockup URL before submit', finalUrl || $('#frenzy_mockup_url').val());
          $(form).off('submit.frenzyMock');
          form.submit();
        })
        .catch(err => { Frenzy.log('Submission error', err); alert(err.message || 'Please upload and position your artwork first.'); });
    });
  }

  Frenzy.submit = { bindSubmit };
})(window, jQuery);
