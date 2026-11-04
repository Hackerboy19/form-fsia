/* Runs before first paint so the page never flashes the wrong theme. */
(function () {
  var theme = 'system', density = 'comfortable';
  try {
    theme = localStorage.getItem('fsia-theme') || 'system';
    density = localStorage.getItem('fsia-density') || 'comfortable';
  } catch (e) {}
  var dark = theme === 'dark' || (theme === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
  document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
  document.documentElement.setAttribute('data-density', density);
})();
