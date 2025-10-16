# API Endpoints - Exemples de Requêtes et Réponses

## Base URL
```
http://localhost:8000
```

## 1. Chat RAG - `/chat`

### Requête
```bash
curl -X POST http://localhost:8000/chat \
  -H "Content-Type: application/json" \
  -d '{"question":"Quelles formations Python sont disponibles ?"}'
```

### Réponse
```json
{
  "answer": "Les formations Python disponibles sont :\n- Maitriser Robot Framework avec Python\n- Web Scraping : analyse et extraction de données avec Python\n- Apprendre à programmer avec le langage Python",
  "sources": [
    {
      "type": "formation",
      "id": "4020",
      "title": "Maitriser Robot Framework avec Python",
      "relevance_score": 0.8,
      "excerpt": null
    },
    {
      "type": "formation",
      "id": "3759",
      "title": "Web Scraping : analyse et extraction de données avec Python",
      "relevance_score": 0.8,
      "excerpt": null
    }
  ],
  "context_used": false,
  "processing_time_ms": 3177,
  "model_used": "gemini-2.5-flash-lite"
}
```

---

## 2. Synchronisation OO2 - `/sync/oo2`

### Requête
```bash
curl -X POST http://localhost:8000/sync/oo2
```

### Réponse
```json
{
  "message": "Synchronisation terminée avec succès",
  "formations_created": 150,
  "formations_updated": 25,
  "sessions_created": 300,
  "sessions_updated": 50,
  "processing_time_ms": 15420
}
```

---

## 3. Reconstruction Index - `/index/rebuild`

### Requête
```bash
curl -X POST http://localhost:8000/index/rebuild
```

### Réponse
```json
{
  "message": "Index reconstruit avec succès",
  "formations_indexed": 175,
  "processing_time_ms": 8500
}
```

---

## 4. Statistiques - `/stats`

### Requête
```bash
curl http://localhost:8000/stats
```

### Réponse
```json
{
  "total_formations": 175,
  "total_sessions": 350,
  "last_sync": "2024-01-15T10:30:00Z",
  "database_size_mb": 45.2
}
```

---

## 5. Santé API - `/health`

### Requête
```bash
curl http://localhost:8000/health
```

### Réponse
```json
{
  "status": "healthy",
  "timestamp": "2024-01-15T10:30:00Z",
  "version": "1.0.0",
  "database": "connected",
  "openai": "connected"
}
```

---

## 6. Test APIs - `/test/apis`

### Requête
```bash
curl http://localhost:8000/test/apis
```

### Réponse
```json
{
  "openai_status": "ok",
  "oo2_status": "ok",
  "database_status": "ok",
  "all_services_healthy": true
}
```

---

## 7. Test Chat - `/test/chat`

### Requête
```bash
curl http://localhost:8000/test/chat
```

### Réponse
```json
{
  "test_question": "Test de fonctionnement du chat",
  "response": "Le système de chat fonctionne correctement.",
  "context_used": true,
  "processing_time_ms": 1200,
  "test_passed": true
}
```

---

## Exemples de Questions pour `/chat`

```bash
# Formations par technologie
curl -X POST http://localhost:8000/chat \
  -H "Content-Type: application/json" \
  -d '{"question":"Formations Python"}'

# Formations par domaine
curl -X POST http://localhost:8000/chat \
  -H "Content-Type: application/json" \
  -d '{"question":"Formations en cybersécurité"}'

# Formations par niveau
curl -X POST http://localhost:8000/chat \
  -H "Content-Type: application/json" \
  -d '{"question":"Formations débutant"}'

# Formations par durée
curl -X POST http://localhost:8000/chat \
  -H "Content-Type: application/json" \
  -d '{"question":"Formations courtes"}'
```

## Codes d'Erreur

- **200**: Succès
- **400**: Requête malformée
- **422**: Erreur de validation
- **500**: Erreur serveur

### Exemple d'erreur
```json
{
  "detail": "Validation error: question is required"
}
```
