(function () {
  "use strict";

  var CONFIG = window.WD_CONFIG || {};

  var PACKAGES = [
    {
      id: "start",
      name: "Start",
      price: 500,
      tagline: "Für Selbstständige, Vereine & den schnellen Start",
      features: [
        "Bis zu 3 Seiten (z. B. Start, Über mich, Kontakt)",
        "Modernes Design – perfekt auf dem Handy",
        "Kontaktformular & Google Maps",
        "Impressum & Datenschutz eingebunden",
        "Online in 1–2 Wochen",
      ],
    },
    {
      id: "business",
      name: "Business",
      price: 1200,
      tagline: "Für Unternehmen, die Kunden gewinnen wollen",
      popular: true,
      features: [
        "Bis zu 8 Seiten",
        "Individuelles Design passend zu deiner Marke",
        "Blog / News oder Bildergalerie",
        "Grundlegende Suchmaschinen-Optimierung (SEO)",
        "Einweisung, damit du Inhalte selbst pflegen kannst",
      ],
    },
    {
      id: "premium",
      name: "Premium",
      price: 1650,
      tagline: "Für große Websites mit vielen Inhalten & Extras",
      features: [
        "Unbegrenzt viele Seiten",
        "Premium-Design mit Animationen",
        "Terminbuchung, Mehrsprachigkeit oder Produktkatalog",
        "Erweiterte SEO & Ladezeit-Optimierung",
        "Bevorzugter Support nach dem Start",
      ],
    },
  ];

  // Punkte aus den Antworten → Paket
  function packageForScore(score) {
    if (score <= 2) return PACKAGES[0];
    if (score <= 6) return PACKAGES[1];
    return PACKAGES[2];
  }

  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };
  var euro = function (n) { return n.toLocaleString("de-DE"); };

  // ---------- Grunddaten ----------
  $$(".js-firma").forEach(function (el) { el.textContent = CONFIG.firma || el.textContent; });
  $("#year").textContent = new Date().getFullYear();
  var footerMail = $("#footerMail");
  if (CONFIG.email && CONFIG.email !== "deine@email.de") {
    footerMail.textContent = CONFIG.email;
    footerMail.href = "mailto:" + CONFIG.email;
  } else {
    footerMail.parentNode.remove();
  }

  // ---------- Paketkarten ----------
  var packagesEl = $("#packages");
  PACKAGES.forEach(function (p) {
    var card = document.createElement("article");
    card.className = "pkg" + (p.popular ? " pkg-popular" : "");
    card.innerHTML =
      (p.popular ? '<span class="pkg-badge">Beliebt bei Unternehmen</span>' : "") +
      "<h3>" + p.name + "</h3>" +
      '<p class="pkg-tag">' + p.tagline + "</p>" +
      '<div class="pkg-price">' + euro(p.price) + " <span>€</span></div>" +
      '<p class="pkg-once">einmalig, Festpreis</p>' +
      "<ul>" + p.features.map(function (f) { return "<li>" + f + "</li>"; }).join("") + "</ul>" +
      '<a href="#rechner" class="btn ' + (p.popular ? "" : "btn-outline") + ' btn-full" data-pick="' + p.id + '">' + p.name + " anfragen</a>";
    packagesEl.appendChild(card);
  });

  // ---------- Rechner ----------
  var steps = $$(".q");
  var total = steps.length;
  var current = 1;
  var chosen = null;
  var nextBtn = $("#nextBtn");
  var backBtn = $("#backBtn");
  var calcForm = $("#calcForm");
  var result = $("#result");

  function stepValid(n) {
    var fs = steps[n - 1];
    if ($$("input[type=checkbox]", fs).length) return true; // Extras sind optional
    return !!$("input:checked", fs);
  }

  function render() {
    steps.forEach(function (fs, i) { fs.classList.toggle("active", i + 1 === current); });
    backBtn.hidden = current === 1;
    nextBtn.disabled = !stepValid(current);
    nextBtn.textContent = current === total ? "Preis anzeigen ✨" : "Weiter →";
    $("#progressBar").style.width = ((current - 1) / total) * 100 + "%";
    $("#progressLabel").textContent = "Frage " + current + " von " + total;
  }

  function score() {
    return $$("input:checked", calcForm).reduce(function (sum, el) { return sum + Number(el.value); }, 0);
  }

  function animatePrice(to) {
    var el = $("#resultPrice");
    var from = Number(el.dataset.value || 0);
    var start = null;
    var dur = 700;
    el.dataset.value = to;
    function tick(t) {
      if (!start) start = t;
      var k = Math.min((t - start) / dur, 1);
      var eased = 1 - Math.pow(1 - k, 3);
      el.textContent = euro(Math.round(from + (to - from) * eased));
      if (k < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  function showPackage(p) {
    chosen = p;
    $("#resultName").textContent = p.name;
    $("#resultFeatures").innerHTML = p.features.map(function (f) { return "<li>" + f + "</li>"; }).join("");
    $$("#switchBtns button").forEach(function (b) { b.classList.toggle("active", b.dataset.id === p.id); });
    animatePrice(p.price);
  }

  function showResult(p) {
    calcForm.hidden = true;
    $(".calc-head").hidden = true;
    result.hidden = false;
    $("#leadForm").hidden = false;
    $("#success").hidden = true;
    showPackage(p);
    result.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  PACKAGES.forEach(function (p) {
    var b = document.createElement("button");
    b.type = "button";
    b.dataset.id = p.id;
    b.textContent = p.name + " · " + euro(p.price) + " €";
    b.addEventListener("click", function () { showPackage(p); });
    $("#switchBtns").appendChild(b);
  });

  calcForm.addEventListener("change", function (e) {
    nextBtn.disabled = !stepValid(current);
    // Bei Einzelauswahl automatisch weiter – spart einen Klick
    if (e.target.type === "radio" && current < total) {
      var step = current;
      setTimeout(function () { if (current === step) { current++; render(); } }, 250);
    }
  });

  nextBtn.addEventListener("click", function () {
    if (!stepValid(current)) return;
    if (current < total) { current++; render(); }
    else { showResult(packageForScore(score())); }
  });

  backBtn.addEventListener("click", function () {
    if (current > 1) { current--; render(); }
  });

  function restart() {
    calcForm.reset();
    current = 1;
    calcForm.hidden = false;
    $(".calc-head").hidden = false;
    result.hidden = true;
    $("#formMsg").textContent = "";
    render();
  }
  $("#restartBtn").addEventListener("click", restart);

  // Paket direkt aus den Karten wählen
  packagesEl.addEventListener("click", function (e) {
    var a = e.target.closest("[data-pick]");
    if (!a) return;
    e.preventDefault();
    var p = PACKAGES.filter(function (x) { return x.id === a.dataset.pick; })[0];
    showResult(p);
  });

  // ---------- Anfrage senden ----------
  var leadForm = $("#leadForm");
  var formMsg = $("#formMsg");
  var sendBtn = $("#sendBtn");

  function answersSummary() {
    var lines = [];
    steps.forEach(function (fs) {
      var picked = $$("input:checked", fs).map(function (el) { return el.dataset.label; });
      lines.push($("legend", fs).firstChild.textContent.trim() + " " + (picked.length ? picked.join(", ") : "–"));
    });
    return lines.join("\n");
  }

  function buildMessage(data) {
    return [
      "Neue Website-Anfrage",
      "",
      "Paket: " + chosen.name + " (" + euro(chosen.price) + " €)",
      "",
      "Name: " + data.name,
      "E-Mail: " + data.email,
      "Telefon: " + (data.telefon || "–"),
      "Firma/Projekt: " + (data.firma || "–"),
      "",
      "Antworten im Rechner:",
      calcForm.hidden && !$("input:checked", calcForm) ? "(Paket direkt gewählt)" : answersSummary(),
      "",
      "Nachricht:",
      data.nachricht || "–",
    ].join("\n");
  }

  function openMailto(data) {
    var subject = "Website-Anfrage: Paket " + chosen.name + " (" + euro(chosen.price) + " €)";
    window.location.href = "mailto:" + encodeURIComponent(CONFIG.email || "") +
      "?subject=" + encodeURIComponent(subject) +
      "&body=" + encodeURIComponent(buildMessage(data));
  }

  function done() {
    leadForm.hidden = true;
    $("#success").hidden = false;
  }

  leadForm.addEventListener("submit", function (e) {
    e.preventDefault();
    formMsg.textContent = "";
    formMsg.className = "form-msg";

    if (!leadForm.checkValidity()) {
      formMsg.textContent = "Bitte fülle Name, E-Mail und die Einwilligung aus.";
      formMsg.classList.add("error");
      leadForm.reportValidity();
      return;
    }

    var fd = new FormData(leadForm);
    var data = {};
    fd.forEach(function (v, k) { data[k] = String(v).trim(); });

    if (CONFIG.versand !== "formsubmit" || !CONFIG.email || CONFIG.email === "deine@email.de") {
      openMailto(data);
      done();
      return;
    }

    sendBtn.disabled = true;
    sendBtn.textContent = "Wird gesendet …";

    fetch("https://formsubmit.co/ajax/" + encodeURIComponent(CONFIG.email), {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({
        _subject: "Website-Anfrage: " + chosen.name + " (" + euro(chosen.price) + " €) von " + data.name,
        _replyto: data.email,
        _template: "box",
        _captcha: "false",
        Paket: chosen.name + " – " + euro(chosen.price) + " €",
        Name: data.name,
        "E-Mail": data.email,
        Telefon: data.telefon || "–",
        "Firma/Projekt": data.firma || "–",
        Antworten: answersSummary(),
        Nachricht: data.nachricht || "–",
      }),
    })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function (json) {
        if (json && (json.success === false || json.success === "false")) throw new Error(json.message);
        done();
      })
      .catch(function () {
        formMsg.innerHTML = "Das hat leider nicht geklappt. <a href=\"#\" id=\"mailFallback\">Per E-Mail senden</a>";
        formMsg.classList.add("error");
        $("#mailFallback").addEventListener("click", function (ev) { ev.preventDefault(); openMailto(data); done(); });
      })
      .finally(function () {
        sendBtn.disabled = false;
        sendBtn.textContent = "Anfrage kostenlos senden";
      });
  });

  render();
})();
