#!/bin/bash

echo "=== Démarrage de serveo.net pour Drupal ==="
echo ""
echo "Votre configuration actuelle :"
echo "✅ API ngrok : https://24b727fa91d4.ngrok-free.app/"
echo "✅ Serveur Drupal : localhost:8888"
echo ""
echo "Pour exposer Drupal avec serveo.net, lancez cette commande :"
echo ""
echo "ssh -R 80:localhost:8888 serveo.net"
echo ""
echo "serveo.net vous donnera une URL comme : https://abc123.serveo.net"
echo ""
echo "Une fois configuré, vous pourrez :"
echo "1. Accéder à votre site Drupal via l'URL serveo"
echo "2. Le widget utilisera automatiquement votre API ngrok"
echo ""
echo "=== Test de l'API ngrok ==="
echo "Test en cours..."

# Test de l'API avec l'en-tête ngrok
response=$(curl -s -X POST https://24b727fa91d4.ngrok-free.app/chat \
  -H "Content-Type: application/json" \
  -H "ngrok-skip-browser-warning: true" \
  -d '{"question": "Test rapide"}' \
  --max-time 10)

if echo "$response" | grep -q "answer"; then
    echo "✅ API ngrok fonctionne parfaitement !"
    echo "Réponse reçue : $(echo "$response" | jq -r '.answer' 2>/dev/null | head -c 100)..."
else
    echo "❌ Problème avec l'API ngrok"
    echo "Réponse : $response"
fi

echo ""
echo "=== Prochaines étapes ==="
echo "1. Lancez : ssh -R 80:localhost:8888 serveo.net"
echo "2. Copiez l'URL serveo générée"
echo "3. Testez votre site avec le widget !"
