/* Schwebender WhatsApp-Button auf allen Seiten.
   Reiner Link zu wa.me – es werden erst beim Anklicken Daten an WhatsApp übertragen. */
(function () {
  var NUMMER = "4915221950840";
  var TEXT = "Hallo Dennis, ich interessiere mich für eine Website vom Website Discounter.";

  var css = '' +
    '.wa{position:fixed;right:20px;bottom:20px;z-index:90;display:flex;align-items:center;gap:10px;text-decoration:none;color:#000;font:700 14px/1 -apple-system,BlinkMacSystemFont,"Segoe UI","Helvetica Neue",Arial,sans-serif}' +
    '.wa-icon{position:relative;width:58px;height:58px;border-radius:50%;background:#000;border:2px solid #fff;box-shadow:0 8px 24px rgba(0,0,0,.25);display:grid;place-items:center;transition:transform .25s cubic-bezier(.2,.8,.2,1)}' +
    '.wa-icon svg{width:30px;height:30px;fill:#fff}' +
    '.wa-icon:before{content:"";position:absolute;inset:-2px;border-radius:50%;border:2px solid #000;animation:waPulse 2.6s ease-out infinite}' +
    '@keyframes waPulse{0%{transform:scale(1);opacity:.6}80%,100%{transform:scale(1.55);opacity:0}}' +
    '.wa-label{background:#fff;border:2px solid #000;padding:10px 14px;white-space:nowrap;opacity:0;transform:translateX(8px);transition:opacity .25s,transform .25s;pointer-events:none}' +
    '.wa:hover .wa-icon,.wa:focus-visible .wa-icon{transform:scale(1.08)}' +
    '.wa:hover .wa-label,.wa:focus-visible .wa-label{opacity:1;transform:none}' +
    '.wa:focus-visible{outline:none}.wa:focus-visible .wa-icon{outline:3px solid #000;outline-offset:3px}' +
    '@media(max-width:600px){.wa{right:14px;bottom:14px}.wa-icon{width:54px;height:54px}.wa-label{display:none}footer{padding-bottom:84px!important}}' +
    '@media(prefers-reduced-motion:reduce){.wa-icon:before{animation:none;display:none}.wa-icon,.wa-label{transition:none}}' +
    '@media print{.wa{display:none}}';

  var icon = '<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16.04 3C8.9 3 3.1 8.78 3.1 15.9c0 2.28.6 4.5 1.74 6.46L3 29l6.83-1.79a12.9 12.9 0 0 0 6.2 1.58h.01c7.13 0 12.93-5.79 12.93-12.9C28.97 8.78 23.17 3 16.04 3zm0 23.6h-.01a10.7 10.7 0 0 1-5.45-1.49l-.39-.23-4.05 1.06 1.08-3.94-.25-.4a10.66 10.66 0 0 1-1.64-5.7c0-5.9 4.81-10.7 10.72-10.7 5.9 0 10.71 4.8 10.71 10.7 0 5.91-4.8 10.7-10.72 10.7zm5.88-8.01c-.32-.16-1.91-.94-2.2-1.05-.3-.1-.51-.16-.73.16-.21.32-.83 1.05-1.02 1.26-.19.21-.37.24-.7.08-.32-.16-1.36-.5-2.59-1.6-.96-.85-1.6-1.9-1.79-2.22-.19-.32-.02-.5.14-.66.15-.14.32-.37.48-.56.16-.18.21-.32.32-.53.1-.21.05-.4-.03-.56-.08-.16-.72-1.74-.99-2.38-.26-.62-.53-.54-.72-.55h-.62c-.21 0-.56.08-.85.4-.3.32-1.12 1.09-1.12 2.66 0 1.57 1.15 3.09 1.31 3.3.16.21 2.26 3.44 5.47 4.83.77.33 1.36.52 1.83.67.77.24 1.47.21 2.02.13.62-.09 1.91-.78 2.18-1.53.27-.75.27-1.4.19-1.53-.08-.13-.29-.21-.61-.37z"/></svg>';

  function init() {
    var st = document.createElement("style");
    st.textContent = css;
    document.head.appendChild(st);
    var a = document.createElement("a");
    a.className = "wa";
    a.href = "https://wa.me/" + NUMMER + "?text=" + encodeURIComponent(TEXT);
    a.target = "_blank";
    a.rel = "noopener";
    a.setAttribute("aria-label", "Per WhatsApp schreiben");
    a.innerHTML = '<span class="wa-label">Schreib mir auf WhatsApp</span><span class="wa-icon">' + icon + "</span>";
    document.body.appendChild(a);
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();
