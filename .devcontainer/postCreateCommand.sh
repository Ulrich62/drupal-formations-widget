#!/bin/bash

# Script de configuration automatique pour Codespaces
echo "🚀 Configuration de l'environnement Drupal..."

# Installer Composer si pas déjà installé
if ! command -v composer &> /dev/null; then
    echo "📦 Installation de Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Installer les dépendances
echo "📦 Installation des dépendances Composer..."
composer install --no-interaction --prefer-dist --no-dev

echo "📦 Installation des dépendances NPM..."
npm install

# Configurer les permissions
echo "🔧 Configuration des permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Démarrer Apache
echo "🌐 Démarrage d'Apache..."
service apache2 start

# Créer un lien symbolique pour le site
echo "🔗 Configuration du site Drupal..."
ln -sf /var/www/html/web /var/www/html/public_html

# Configurer Drupal pour le développement
echo "⚙️ Configuration Drupal..."
if [ ! -f /var/www/html/web/sites/default/settings.php ]; then
    cp /var/www/html/web/sites/default/default.settings.php /var/www/html/web/sites/default/settings.php
fi

echo "✅ Configuration terminée !"
echo "🌐 Votre site Drupal est accessible sur : http://localhost:8080"
echo "📱 Codespace URL sera disponible dans l'onglet 'Ports'"
echo "🔧 Pour configurer Drupal, visitez : http://localhost:8080/install.php"
