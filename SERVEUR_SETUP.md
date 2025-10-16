# Configuration des serveurs pour le widget Drupal

## 🎯 Configuration actuelle

- **API ngrok** : `https://24b727fa91d4.ngrok-free.app/` ✅
- **Serveur Drupal** : `localhost:8888` ✅
- **Widget configuré** pour utiliser l'API ngrok ✅

## 🚀 Étapes pour exposer Drupal avec serveo.net

### 1. Ouvrir un nouveau terminal
```bash
ssh -R 80:localhost:8888 serveo.net
```

### 2. serveo.net vous donnera une URL comme :
```
https://abc123.serveo.net
```

### 3. Tester votre configuration
- Accédez à l'URL serveo
- Le widget devrait apparaître en bas à droite
- Testez le chat - il utilisera automatiquement votre API ngrok

## 🔧 Configuration technique

### Widget JavaScript
Le widget est configuré pour utiliser :
```javascript
const apiBase = 'https://24b727fa91d4.ngrok-free.app';
```

### URLs de test
- **Site Drupal** : `https://VOTRE-URL-SERVEO.serveo.net`
- **Widget direct** : `https://VOTRE-URL-SERVEO.serveo.net/formations-widget/embed`
- **API** : `https://24b727fa91d4.ngrok-free.app/chat`

## 🐛 Dépannage

### Si le widget ne fonctionne pas :
1. Vérifiez la console du navigateur (F12)
2. Regardez les logs : `console.log("API Base URL utilisée:", apiBase)`
3. Vérifiez que l'API ngrok fonctionne : `curl https://24b727fa91d4.ngrok-free.app/chat`

### Si serveo ne fonctionne pas :
1. Vérifiez que votre serveur Drupal fonctionne sur le port 8888
2. Essayez une autre commande serveo : `ssh -R 8080:localhost:8888 serveo.net`

## 📋 Résumé des commandes

```bash
# Terminal 1 : Serveur Drupal (déjà en cours)
vendor/bin/drush serve

# Terminal 2 : API ngrok (déjà en cours)  
ngrok http 8000

# Terminal 3 : Drupal avec serveo
ssh -R 80:localhost:8888 serveo.net
```

## ✅ Test final

Une fois tout configuré :
1. Accédez à votre URL serveo
2. Cliquez sur le widget en bas à droite
3. Posez une question
4. Le widget devrait répondre en utilisant votre API ngrok !
