#!/bin/bash

echo "=== Configuration serveo.net pour Drupal ==="
echo ""
echo "Votre API est déjà exposée sur : https://24b727fa91d4.ngrok-free.app/"
echo ""
echo "Pour exposer votre site Drupal avec serveo.net :"
echo ""
echo "1. Assurez-vous que votre serveur Drupal fonctionne sur le port 8888"
echo "2. Dans un nouveau terminal, lancez :"
echo ""
echo "   ssh -R 80:localhost:8888 serveo.net"
echo ""
echo "3. serveo.net vous donnera une URL comme : https://abc123.serveo.net"
echo "4. Utilisez cette URL pour accéder à votre site Drupal"
echo ""
echo "=== Test de l'API ==="
echo "Testons d'abord votre API ngrok..."

# Test de l'API
curl -s -X POST https://24b727fa91d4.ngrok-free.app/chat \
  -H "Content-Type: application/json" \
  -H "ngrok-skip-browser-warning: true" \
  -d '{"question": "Test de connectivité"}' \
  --max-time 10

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ API accessible sur ngrok !"
else
    echo ""
    echo "❌ Problème avec l'API ngrok"
fi

echo ""
echo "=== Instructions complètes ==="
echo "1. Terminal 1 : Votre serveur Drupal (déjà en cours)"
echo "2. Terminal 2 : ssh -R 80:localhost:8888 serveo.net"
echo "3. Terminal 3 : Votre API sur ngrok (déjà en cours)"
echo ""
echo "Une fois serveo configuré, vous pourrez accéder à votre site via l'URL serveo"
echo "et le widget utilisera automatiquement votre API ngrok !"
