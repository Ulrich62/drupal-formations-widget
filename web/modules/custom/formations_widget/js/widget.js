/**
 * Formations Widget JavaScript
 */
(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.formationsWidget = {
    attach: function (context, settings) {
      const container = document.getElementById('formations-widget-container');
      if (!container) return;

      // Initialize widget
      initializeWidget(container);
    }
  };

  function initializeWidget(container) {
    // Show loading state
    container.innerHTML = `
      <div class="widget-loading">
        <h3>🔧 Widget Formations</h3>
        <p>Chargement du widget formations...</p>
      </div>
    `;

    // Simulate loading and show widget
    setTimeout(() => {
      container.innerHTML = `
        <h3>🎯 Widget Formations Actif</h3>
        <div class="widget-content">
          <p>Le widget formations est maintenant chargé et prêt à être utilisé.</p>
          <div class="widget-status">
            <strong>Status:</strong> Connecté à l'API OO2
          </div>
          <button class="widget-button" onclick="testFormationsWidget()">
            📚 Voir les Formations
          </button>
          <button class="widget-button secondary" onclick="testSessionsWidget()">
            🎓 Voir les Sessions
          </button>
          <button class="widget-button secondary" onclick="testChatWidget()">
            💬 Chat Assistant
          </button>
        </div>
      `;
    }, 1500);
  }

  // Global functions for widget interactions
  window.testFormationsWidget = function() {
    showWidgetModal('Formations', 'Chargement des formations disponibles...');
    
    // Simulate API call
    setTimeout(() => {
      showWidgetModal('Formations', `
        <h4>📚 Formations Disponibles</h4>
        <ul>
          <li>Formation Drupal 10</li>
          <li>Formation Symfony 6</li>
          <li>Formation React.js</li>
          <li>Formation Node.js</li>
        </ul>
        <p><em>Widget formations fonctionnel ! 🎉</em></p>
      `);
    }, 1000);
  };

  window.testSessionsWidget = function() {
    showWidgetModal('Sessions', 'Chargement des sessions disponibles...');
    
    setTimeout(() => {
      showWidgetModal('Sessions', `
        <h4>🎓 Sessions Disponibles</h4>
        <ul>
          <li>Session Drupal - 15 Janvier 2024</li>
          <li>Session Symfony - 22 Janvier 2024</li>
          <li>Session React - 29 Janvier 2024</li>
        </ul>
        <p><em>Widget sessions fonctionnel ! 🎉</em></p>
      `);
    }, 1000);
  };

  window.testChatWidget = function() {
    showWidgetModal('Chat Assistant', `
      <div style="height: 300px; border: 1px solid #ddd; border-radius: 5px; padding: 10px; background: #f8f9fa;">
        <div style="margin-bottom: 10px;">
          <strong>Assistant:</strong> Bonjour ! Comment puis-je vous aider avec nos formations ?
        </div>
        <div style="margin-bottom: 10px;">
          <strong>Vous:</strong> Je voudrais en savoir plus sur Drupal
        </div>
        <div style="margin-bottom: 10px;">
          <strong>Assistant:</strong> Drupal est un CMS puissant. Nous avons une formation complète disponible !
        </div>
        <input type="text" placeholder="Tapez votre message..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
      </div>
      <p><em>Widget chat fonctionnel ! 🎉</em></p>
    `);
  };

  function showWidgetModal(title, content) {
    // Create modal
    const modal = document.createElement('div');
    modal.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 10000;
      display: flex;
      align-items: center;
      justify-content: center;
    `;
    
    modal.innerHTML = `
      <div style="background: white; border-radius: 10px; padding: 30px; max-width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
        <h2 style="margin-top: 0; color: #333;">${title}</h2>
        <div>${content}</div>
        <button onclick="this.closest('.modal').remove()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-top: 20px;">
          Fermer
        </button>
      </div>
    `;
    
    modal.className = 'modal';
    document.body.appendChild(modal);
    
    // Close on background click
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.remove();
      }
    });
  }

})(Drupal, drupalSettings);
