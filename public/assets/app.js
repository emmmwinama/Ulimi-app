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

  /* ---- repeatable line-item rows (activity form) ---- */
  document.querySelectorAll("[data-rowset]").forEach(function (set) {
    var body = set.querySelector("[data-rows]");
    if (!body) return;

    set.addEventListener("click", function (e) {
      var addBtn = e.target.closest("[data-add-row]");
      var rmBtn = e.target.closest("[data-remove-row]");

      if (addBtn) {
        var rows = body.querySelectorAll("[data-row]");
        var template = rows[rows.length - 1];
        var clone = template.cloneNode(true);
        clone.querySelectorAll("input").forEach(function (i) { i.value = ""; });
        clone.querySelectorAll("select").forEach(function (s) { s.selectedIndex = 0; });
        body.appendChild(clone);
      }

      if (rmBtn) {
        var all = body.querySelectorAll("[data-row]");
        if (all.length > 1) {
          rmBtn.closest("[data-row]").remove();
        } else {
          rmBtn.closest("[data-row]").querySelectorAll("input").forEach(function (i) { i.value = ""; });
        }
      }
    });
  });

  /* ---- activity form: suggest activity types from the selected crop ---- */
  var cropSelect = document.getElementById("f_crop_field_id");
  var hint = document.querySelector("[data-type-hint]");
  if (cropSelect && hint) {
    var updateHint = function () {
      var opt = cropSelect.options[cropSelect.selectedIndex];
      var types = opt && opt.dataset.types ? opt.dataset.types.split("|").filter(Boolean) : [];
      hint.textContent = types.length ? "Common for this crop: " + types.join(", ") : "";
    };
    cropSelect.addEventListener("change", updateHint);
    updateHint();
  }

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
