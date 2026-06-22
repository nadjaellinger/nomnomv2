(function () {
  var methods = document.querySelectorAll(".re-import .re-method");
  var panels = document.querySelectorAll(".re-import .re-panel");
  methods.forEach(function (m) {
    m.addEventListener("click", function () {
      var key = m.getAttribute("data-method");
      methods.forEach(function (x) {
        var on = x === m;
        x.classList.toggle("is-active", on);
        x.setAttribute("aria-selected", on);
      });
      panels.forEach(function (p) {
        p.classList.toggle("is-active", p.getAttribute("data-panel") === key);
      });
    });
  });
  var drop = document.getElementById("reDrop"),
    file = document.getElementById("image"),
    name = document.getElementById("reDropFile");
  if (drop && file) {
    function show() {
      if (file.files && file.files.length) {
        drop.classList.add("has-file");
        name.textContent = "✓ " + file.files[0].name;
      }
    }
    file.addEventListener("change", show);
    ["dragenter", "dragover"].forEach(function (e) {
      drop.addEventListener(e, function (ev) {
        ev.preventDefault();
        drop.classList.add("is-drag");
      });
    });
    ["dragleave", "drop"].forEach(function (e) {
      drop.addEventListener(e, function (ev) {
        ev.preventDefault();
        drop.classList.remove("is-drag");
      });
    });
    drop.addEventListener("drop", function (ev) {
      if (ev.dataTransfer.files.length) {
        file.files = ev.dataTransfer.files;
        show();
      }
    });
  }
})();
