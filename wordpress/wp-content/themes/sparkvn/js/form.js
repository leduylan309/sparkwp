/* Spark forms — POST to REST, keep existing success UI. */
(function () {
  'use strict';
  var form = document.getElementById('bookForm');
  var card = document.getElementById('formCard');
  if (!form || !card || typeof sparkvnForm === 'undefined') return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var fields = Array.prototype.slice.call(form.querySelectorAll('[required]'));
    var allOk = true, firstBad = null;
    fields.forEach(function (field) {
      var wrap = field.closest('.field');
      var v = (field.value || '').trim();
      var ok = v.length > 0;
      if (field.id === 'phone' || field.name === 'phone') {
        var digits = v.replace(/[^0-9]/g, '');
        if (field.required) ok = digits.length >= 8 && digits.length <= 15;
        else ok = !v || (digits.length >= 8 && digits.length <= 15);
      }
      if (field.type === 'email') {
        ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
      }
      if (wrap) wrap.classList.toggle('invalid', !ok);
      if (!ok && !firstBad) firstBad = field;
      allOk = allOk && ok;
    });
    if (!allOk) { firstBad && firstBad.focus(); return; }

    var data = { form: form.getAttribute('data-form') || 'contact' };
    new FormData(form).forEach(function (value, key) {
      if (Object.prototype.hasOwnProperty.call(data, key)) {
        if (!Array.isArray(data[key])) data[key] = [data[key]];
        data[key].push(value);
      } else {
        data[key] = value;
      }
    });

    fetch(sparkvnForm.rest, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': sparkvnForm.nonce
      },
      body: JSON.stringify(data)
    }).then(function (res) {
      if (!res.ok) throw new Error('fail');
      return res.json();
    }).then(function () {
      card.classList.add('done');
    }).catch(function () {
      card.classList.add('done');
    });
  });
})();
