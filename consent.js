/* Cookie-Banner und Einwilligung für das Meta-Pixel (Facebook-Pixel).
   Das Pixel wird ausschließlich nach Zustimmung geladen. Die Pixel-ID kommt aus
   dem Admin-Backend (prices.php → "pixel"). Auswahl ändern: Element mit
   data-consent-open anklicken (z. B. "Cookie-Einstellungen" im Footer). */
(function () {
  var KEY = "wd_consent", VERSION = 1, pixelLoaded = false, queued = [];

  function read() {
    try {
      var c = JSON.parse(localStorage.getItem(KEY));
      return c && c.v === VERSION ? c : null;
    } catch (e) { return null; }
  }
  function save(marketing) {
    try { localStorage.setItem(KEY, JSON.stringify({ v: VERSION, marketing: marketing, ts: new Date().toISOString() })); } catch (e) {}
  }

  function loadPixel() {
    if (pixelLoaded) return;
    pixelLoaded = true;
    fetch("prices.php", { cache: "no-store" })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        var id = String(d.pixel || "");
        if (!/^\d{5,20}$/.test(id)) return;
        /* Offizieller Meta-Pixel-Basiscode */
        !function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = "2.0"; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, "script", "https://connect.facebook.net/en_US/fbevents.js");
        fbq("init", id);
        fbq("track", "PageView");
        queued.forEach(function (ev) { fbq("track", ev); });
        queued = [];
      })
      .catch(function () {});
  }

  // Ereignis an das Pixel melden – nur mit Einwilligung, ohne Formularinhalte
  window.wdTrack = function (event) {
    var c = read();
    if (!c || !c.marketing) return;
    if (window.fbq) fbq("track", event); else queued.push(event);
  };

  function removeFbCookies() {
    ["_fbp", "_fbc"].forEach(function (n) {
      var host = location.hostname, parts = host.split(".");
      var domains = ["", host, "." + host, parts.length > 1 ? "." + parts.slice(-2).join(".") : host];
      domains.forEach(function (d) {
        document.cookie = n + "=; Max-Age=0; path=/" + (d ? "; domain=" + d : "");
      });
    });
  }

  var css = '' +
    '.wdc{position:fixed;left:16px;right:16px;bottom:16px;z-index:100;max-width:560px;margin-left:auto;background:#fff;color:#000;border:2px solid #000;box-shadow:10px 10px 0 #000;padding:24px;font:15px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI","Helvetica Neue",Arial,sans-serif;animation:wdcIn .5s cubic-bezier(.2,.8,.2,1)}' +
    '@keyframes wdcIn{from{opacity:0;transform:translateY(20px)}}' +
    '.wdc h2{font-size:1.25rem;font-weight:800;letter-spacing:-.02em;line-height:1.15;margin:0 0 8px}' +
    '.wdc p{margin:0 0 16px;color:#444;font-size:.92rem}' +
    '.wdc a{color:#000}' +
    '.wdc-btns{display:grid;grid-template-columns:1fr 1fr;gap:10px}' +
    '.wdc button{font:inherit;font-weight:700;padding:13px 14px;border:2px solid #000;background:#000;color:#fff;cursor:pointer;border-radius:0}' +
    '.wdc button:hover{background:#fff;color:#000}' +
    '.wdc .wdc-link{grid-column:1/-1;background:none;border:0;color:#555;padding:4px 0 0;font-weight:600;text-decoration:underline;text-underline-offset:3px}' +
    '.wdc .wdc-link:hover{background:none;color:#000}' +
    '.wdc-opt{display:flex;gap:12px;align-items:flex-start;border-top:1px solid #ddd;padding:12px 0;font-size:.9rem}' +
    '.wdc-opt:last-of-type{border-bottom:1px solid #ddd;margin-bottom:16px}' +
    '.wdc-opt input{width:20px;height:20px;margin-top:2px;accent-color:#000;flex:none}' +
    '.wdc-opt strong{display:block}.wdc-opt span{color:#555}' +
    '@media(max-width:520px){.wdc{left:10px;right:10px;bottom:10px;padding:18px;box-shadow:6px 6px 0 #000}.wdc-btns{grid-template-columns:1fr}}' +
    '@media(prefers-reduced-motion:reduce){.wdc{animation:none}}';

  var box = null;

  function close() { if (box) { box.remove(); box = null; } }

  function decide(marketing) {
    var before = read();
    save(marketing);
    close();
    if (marketing) loadPixel();
    else if (before && before.marketing) { removeFbCookies(); location.reload(); }
  }

  function open(details) {
    close();
    if (!document.getElementById("wdc-style")) {
      var st = document.createElement("style");
      st.id = "wdc-style"; st.textContent = css;
      document.head.appendChild(st);
    }
    var c = read();
    box = document.createElement("div");
    box.className = "wdc";
    box.setAttribute("role", "dialog");
    box.setAttribute("aria-labelledby", "wdc-title");
    var privacy = '<a href="datenschutz.html#facebook-pixel">Datenschutzerklärung</a>';
    box.innerHTML = details
      ? '<h2 id="wdc-title">Cookie-Einstellungen</h2>' +
        '<p>Wähle aus, welche Dienste du erlaubst. Du kannst deine Auswahl jederzeit über „Cookie-Einstellungen“ unten auf der Seite ändern.</p>' +
        '<label class="wdc-opt"><input type="checkbox" checked disabled><span><strong>Notwendig</strong>Speichert nur deine Cookie-Auswahl. Ohne diese Einstellung funktioniert der Banner nicht.</span></label>' +
        '<label class="wdc-opt"><input type="checkbox" id="wdc-mk"' + (c && c.marketing ? " checked" : "") + '><span><strong>Marketing – Meta-Pixel (Facebook)</strong>Hilft mir, die Wirkung meiner Werbeanzeigen auf Facebook und Instagram zu messen. Dabei werden Daten an Meta übermittelt, auch in die USA. Mehr in der ' + privacy + '.</span></label>' +
        '<div class="wdc-btns"><button type="button" data-a="save">Auswahl speichern</button><button type="button" data-a="all">Alle akzeptieren</button></div>'
      : '<h2 id="wdc-title">Cookies &amp; Datenschutz</h2>' +
        '<p>Ich nutze das Meta-Pixel (Facebook), um die Wirkung meiner Werbung zu messen – aber nur, wenn du zustimmst. Deine Einwilligung kannst du jederzeit widerrufen. Mehr in der ' + privacy + ' und im <a href="impressum.html">Impressum</a>.</p>' +
        '<div class="wdc-btns"><button type="button" data-a="none">Nur notwendige</button><button type="button" data-a="all">Alle akzeptieren</button>' +
        '<button type="button" class="wdc-link" data-a="details">Einstellungen</button></div>';
    box.addEventListener("click", function (e) {
      var a = e.target.getAttribute && e.target.getAttribute("data-a");
      if (a === "all") decide(true);
      else if (a === "none") decide(false);
      else if (a === "save") decide(document.getElementById("wdc-mk").checked);
      else if (a === "details") open(true);
    });
    document.body.appendChild(box);
  }

  document.addEventListener("click", function (e) {
    var t = e.target.closest && e.target.closest("[data-consent-open]");
    if (t) { e.preventDefault(); open(true); }
  });

  function init() {
    var c = read();
    if (!c) open(false);
    else if (c.marketing) loadPixel();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();
