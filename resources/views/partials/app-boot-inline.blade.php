<script>
(function () {
  var root = document.documentElement;
  var bootMode = root.getAttribute('data-app-boot') || 'page';
  var storageKey = 'undangan-warm-' + bootMode;

  function finishBoot() {
    root.classList.remove('app-booting');
    var screen = document.getElementById('appBootScreen');
    if (screen) {
      screen.setAttribute('aria-busy', 'false');
      screen.remove();
    }
    try {
      sessionStorage.setItem(storageKey, '1');
    } catch (e) {}
  }

  var isWarm = false;
  try {
    isWarm = sessionStorage.getItem(storageKey) === '1';
  } catch (e) {}

  if (isWarm) {
    finishBoot();
    return;
  }

  var finished = false;
  var started = Date.now();
  var maxWait = 2800;

  function cssReady() {
    try {
      for (var i = 0; i < document.styleSheets.length; i++) {
        var href = document.styleSheets[i].href;
        if (!href) continue;
        if (href.indexOf('invitation.css') !== -1 || href.indexOf('formal-invitation.css') !== -1) {
          return true;
        }
      }
    } catch (e) {}
    return false;
  }

  function logoReady() {
    var img = document.getElementById('logoImage') || document.querySelector('.formal-cover-logo');
    return !img || (img.complete && img.naturalWidth !== 0) || img.complete;
  }

  function tick() {
    if (finished) return;
    if ((cssReady() && logoReady()) || Date.now() - started >= maxWait) {
      finished = true;
      finishBoot();
      return;
    }
    window.requestAnimationFrame(tick);
  }

  if (document.readyState === 'complete') {
    tick();
  } else {
    window.addEventListener('load', tick, { once: true });
  }
})();
</script>
