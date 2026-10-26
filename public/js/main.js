/* OnePager Ingenieur-Büro Komp: Sticky-Header, Karte nach Zustimmung, Schadensformular */
(function () {
  'use strict';

  // Header bekommt nach dem Scrollen einen Schatten
  var header = document.querySelector('.k-header');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 40); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Karte erst nach Klick laden (DSGVO)
  var map = document.querySelector('.op-map[data-map-src]');
  var mapLink = map && map.querySelector('[data-map-load]');
  if (mapLink) {
    mapLink.addEventListener('click', function (e) {
      e.preventDefault();
      var iframe = document.createElement('iframe');
      iframe.src = map.getAttribute('data-map-src');
      iframe.title = 'Karte: Ingenieur-Büro Komp, Gartenstraße 115, 63225 Langen';
      iframe.loading = 'lazy';
      iframe.referrerPolicy = 'no-referrer-when-downgrade';
      map.replaceChildren(iframe);
      map.classList.add('op-map--loaded');
    });
  }

  // Schadensformular per fetch senden; ohne JavaScript funktioniert es als normales POST
  var form = document.getElementById('schadenformular');
  if (!form) return;
  var status = form.querySelector('.op-form-status');
  var button = form.querySelector('button[type="submit"]');
  var geladen = Date.now();

  function showStatus(ok, title, text) {
    status.className = 'op-form-status ' + (ok ? 'op-form-status--ok' : 'op-form-status--error');
    var icon = document.createElement('span');
    icon.className = 'k-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = ok ? 'check_circle' : 'error';
    var body = document.createElement('span');
    var strong = document.createElement('strong');
    strong.textContent = title;
    body.append(strong, text);
    status.replaceChildren(icon, body);
    status.hidden = false;
    status.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var invalid = null;
    Array.prototype.forEach.call(form.querySelectorAll('input, textarea'), function (field) {
      var bad = !field.checkValidity();
      if (bad) field.setAttribute('aria-invalid', 'true'); else field.removeAttribute('aria-invalid');
      if (bad && !invalid) invalid = field;
    });
    if (invalid) {
      showStatus(false, 'Bitte prüfen Sie Ihre Angaben.', ' Name, Telefon und die Einwilligung sind Pflichtfelder; die E-Mail-Adresse muss gültig sein.');
      invalid.focus();
      return;
    }

    // Ausfülldauer in ms für den Spamschutz (Bots schicken sofort ab)
    form.elements.t.value = String(Date.now() - geladen);
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'Accept': 'application/json' }
    })
      .then(function (res) {
        return res.json().catch(function () {
          // Keine JSON-Antwort: meist ein PHP-Fehler auf dem Server (z. B. zu alte PHP-Version)
          return { ok: false, fehler: 'Bitte versuchen Sie es erneut oder rufen Sie uns an: 06103 / 22011. (Serverfehler HTTP ' + res.status + ')' };
        });
      })
      .then(function (data) {
        if (data.ok) {
          form.reset();
          geladen = Date.now();
          showStatus(true, 'Vielen Dank, Ihre Schadensmeldung ist bei uns eingegangen.', data.bestaetigung
            ? ' Eine Bestätigung ist an Ihre E-Mail-Adresse unterwegs. Wir melden uns innerhalb unserer Öffnungszeiten.'
            : ' Wir melden uns innerhalb unserer Öffnungszeiten.');
        } else {
          showStatus(false, 'Die Meldung konnte nicht gesendet werden.', ' ' + (data.fehler || 'Bitte versuchen Sie es erneut oder rufen Sie uns an: 06103 / 22011.'));
        }
      })
      .catch(function () {
        showStatus(false, 'Die Meldung konnte nicht gesendet werden.', ' Bitte prüfen Sie Ihre Internetverbindung oder rufen Sie uns an: 06103 / 22011.');
      })
      .then(function () {
        button.disabled = false;
        button.removeAttribute('aria-busy');
      });
  });
})();
