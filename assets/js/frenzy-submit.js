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
        .then((maybeUrl) => {
          Frenzy.log('generateServerMockup result', maybeUrl);
          return $('#frenzy_mockup_url').val() || maybeUrl;
        })
        .then((finalUrl) => {
          const url = finalUrl || $('#frenzy_mockup_url').val();
          if (!url) {
            throw new Error('We could not build your mockup. Please upload again.');
          }
          Frenzy.log('Final mockup URL before submit', url);
          Frenzy.log('Submitting with fields', {
            mockup: $('#frenzy_mockup_url').val(),
            original: $('#frenzy_original_url').val(),
            transform: $('#frenzy_transform').val(),
            colorCount: $('#frenzy_color_count').val(),
            colorHexes: $('#frenzy_color_hexes').val()
          });
          $(form).off('submit.frenzyMock');
          form.submit();
        })
        .catch(err => { Frenzy.log('Submission error', err); alert(err.message || 'We could not build your mockup. Please upload and try again.'); });
    });
  }

  Frenzy.submit = { bindSubmit };
})(window, jQuery);
