// Load the formations widget script (protection contre double chargement)
(function() {
  // Ne pas charger sur les pages admin
  if (location.pathname.indexOf('/admin') === 0) return;
  
  // Ne pas charger si déjà présent
  if (document.getElementById('fw-chat-root') || window.fwWidgetLoaded) return;
  
  // Charger le script directement
  const script = document.createElement('script');
  script.src = '/formations-widget/widget.js';
  script.async = true;
  script.defer = true;
  document.head.appendChild(script);
})();
