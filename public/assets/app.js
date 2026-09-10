/* AgriVault — progressive enhancement only. The app works without this file. */
(function () {
  "use strict";

  /* ---- mobile nav toggle ---- */
  var shell = document.querySelector("[data-shell]");
  var toggle = document.querySelector("[data-shell-toggle]");
  if (shell && toggle) {
    toggle.addEventListener("click", function () {
      shell.classList.toggle("nav-open");
    });
    shell.addEventListener("click", function (e) {
      // tap the scrim (the ::after overlay) to close
      if (e.target === shell && shell.classList.contains("nav-open")) {
        shell.classList.remove("nav-open");
      }
    });
  }

  /* ---- auto-dismiss flash messages ---- */
  document.querySelectorAll("[data-flash]").forEach(function (el) {
    setTimeout(function () {
      el.style.transition = "opacity .3s ease";
      el.style.opacity = "0";
      setTimeout(function () { el.remove(); }, 320);
    }, 5000);
  });

  /* ---- close any open <details> menu on outside click / Escape ---- */
  document.addEventListener("click", function (e) {
    document.querySelectorAll("details.chip-select[open]").forEach(function (d) {
      if (!d.contains(e.target)) d.removeAttribute("open");
    });
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      document.querySelectorAll("details.chip-select[open]").forEach(function (d) {
        d.removeAttribute("open");
      });
    }
  });

  /* ---- guard against double form submission ---- */
  document.addEventListener("submit", function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.noguard) return;
    var btn = form.querySelector('button[type="submit"], button:not([type])');
    if (btn) {
      btn.classList.add("is-loading");
      btn.disabled = true;
      setTimeout(function () { btn.disabled = false; btn.classList.remove("is-loading"); }, 8000);
    }
  });
})();
