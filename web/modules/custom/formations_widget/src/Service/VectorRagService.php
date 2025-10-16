<?php

namespace Drupal\formations_widget\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service RAG avec PostgreSQL + pgvector pour les formations et sessions.
 * 
 * Ce service implémente une recherche vectorielle intelligente qui permet
 * de trouver les formations et sessions les plus pertinentes pour une question.
 */
class VectorRagService {
  private Connection $database;
  private ClientInterface $httpClient;
  private CacheBackendInterface $cache;
  private ConfigFactoryInterface $configFactory;
  private Oo2Client $oo2Client;

  public function __construct(
    Connection $database,
    ClientInterface $http_client,
    CacheBackendInterface $cache,
    ConfigFactoryInterface $config_factory,
    Oo2Client $oo2_client
  ) {
    $this->database = $database;
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->configFactory = $config_factory;
    $this->oo2Client = $oo2_client;
  }

  /**
   * Initialise les tables vectorielles.
   */
  public function initializeVectorTables(): void {
    // Table pour les formations
    $this->database->query("
      CREATE TABLE IF NOT EXISTS formations_vectors (
        id SERIAL PRIMARY KEY,
        formation_id VARCHAR(255) UNIQUE NOT NULL,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        embedding VECTOR(384),
        metadata JSONB,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    ");

    // Table pour les sessions
    $this->database->query("
      CREATE TABLE IF NOT EXISTS sessions_vectors (
        id SERIAL PRIMARY KEY,
        session_id VARCHAR(255) UNIQUE NOT NULL,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        embedding VECTOR(384),
        metadata JSONB,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    ");

    // Index pour la recherche vectorielle
    $this->database->query("
      CREATE INDEX IF NOT EXISTS formations_embedding_idx 
      ON formations_vectors USING ivfflat (embedding vector_cosine_ops)
    ");

    $this->database->query("
      CREATE INDEX IF NOT EXISTS sessions_embedding_idx 
      ON sessions_vectors USING ivfflat (embedding vector_cosine_ops)
    ");
  }

  /**
   * Indexe toutes les données de formations et sessions.
   */
  public function indexAllData(): array {
    $formations = $this->oo2Client->getFormations();
    $sessions = $this->oo2Client->getSessions();
    
    $indexedFormations = $this->indexFormations($formations);
    $indexedSessions = $this->indexSessions($sessions);
    
    return [
      'formations_indexed' => $indexedFormations,
      'sessions_indexed' => $indexedSessions,
      'total' => $indexedFormations + $indexedSessions
    ];
  }

  /**
   * Indexe les formations avec leurs embeddings.
   */
  private function indexFormations(array $formations): int {
    $indexed = 0;
    
    foreach ($formations as $formation) {
      $content = $this->buildFormationContent($formation);
      $embedding = $this->generateEmbedding($content);
      
      if ($embedding) {
        $this->database->merge('formations_vectors')
          ->key(['formation_id' => $this->extractValue($formation, 'product_id') ?: uniqid()])
          ->fields([
            'title' => $this->extractValue($formation, 'title'),
            'content' => $content,
            'embedding' => '[' . implode(',', $embedding) . ']',
            'metadata' => json_encode([
              'code' => $this->extractValue($formation, 'field_code'),
              'duration' => $this->extractValue($formation, 'field_duration2'),
              'hours' => $this->extractValue($formation, 'field_hours'),
              'theme' => $this->extractNestedValue($formation, 'field_theme', 'name'),
              'certification' => $this->extractValue($formation, 'field_certification_included'),
            ])
          ])
          ->execute();
        
        $indexed++;
      }
    }
    
    return $indexed;
  }

  /**
   * Indexe les sessions avec leurs embeddings.
   */
  private function indexSessions(array $sessions): int {
    $indexed = 0;
    
    foreach ($sessions as $session) {
      $content = $this->buildSessionContent($session);
      $embedding = $this->generateEmbedding($content);
      
      if ($embedding) {
        $this->database->merge('sessions_vectors')
          ->key(['session_id' => $session['variation_id'] ?? uniqid()])
          ->fields([
            'title' => $session['title'] ?? 'Session',
            'content' => $content,
            'embedding' => '[' . implode(',', $embedding) . ']',
            'metadata' => json_encode([
              'sku' => $session['sku'] ?? '',
              'product_title' => $session['product_title'] ?? '',
              'location' => $session['field_ville'] ?? '',
              'price_eur' => $session['field_price_eur_number'] ?? '',
              'hours' => $session['field_hours'] ?? '',
              'certification' => $session['field_certification_included'] ?? '',
              'status' => $session['status'] ?? '',
            ])
          ])
          ->execute();
        
        $indexed++;
      }
    }
    
    return $indexed;
  }

  /**
   * Construit le contenu textuel d'une formation pour l'embedding.
   */
  private function buildFormationContent(array $formation): string {
    // Extraction des données selon la structure OO2
    $title = $this->extractValue($formation, 'title');
    $description = $this->extractValue($formation, 'body');
    $duration = $this->extractValue($formation, 'field_duration2');
    $hours = $this->extractValue($formation, 'field_hours');
    $code = $this->extractValue($formation, 'field_code');
    $prerequisites = $this->extractValue($formation, 'field_prerequisites');
    $program = $this->extractValue($formation, 'field_program');
    $public = $this->extractValue($formation, 'field_public');
    $theme = $this->extractNestedValue($formation, 'field_theme', 'name');
    
    // Nettoyage HTML
    $description = strip_tags($description);
    $prerequisites = strip_tags($prerequisites);
    $program = strip_tags($program);
    $public = strip_tags($public);
    
    return sprintf(
      "Formation: %s\nCode: %s\nDescription: %s\nDurée: %s jours (%s heures)\nPrérequis: %s\nProgramme: %s\nPublic cible: %s\nSecteur: %s",
      $title,
      $code,
      substr($description, 0, 500) . '...',
      $duration,
      $hours,
      substr($prerequisites, 0, 300) . '...',
      substr($program, 0, 500) . '...',
      substr($public, 0, 300) . '...',
      $theme
    );
  }

  /**
   * Construit le contenu textuel d'une session pour l'embedding.
   */
  private function buildSessionContent(array $session): string {
    return sprintf(
      "Session: %s\nFormation: %s\nLieu: %s\nPrix: %s€\nDurée: %s heures\nCertification: %s\nStatut: %s",
      $session['title'] ?? 'Session',
      $session['product_title'] ?? '',
      $session['field_ville'] ?? '',
      $session['field_price_eur_number'] ?? '',
      $session['field_hours'] ?? '',
      $session['field_certification_included'] ? 'Incluse' : 'Non incluse',
      $session['status'] == '1' ? 'Actif' : 'Inactif'
    );
  }

  /**
   * Extrait une valeur simple d'un champ OO2.
   */
  private function extractValue(array $data, string $field): string {
    if (!isset($data[$field])) {
      return '';
    }
    
    $value = $data[$field];
    
    // Structure des formations : liste avec premier élément contenant 'value'
    if (is_array($value) && count($value) > 0 && isset($value[0]['value'])) {
      return (string) $value[0]['value'];
    }
    
    // Structure des sessions : valeur directe
    if (is_array($value) && isset($value['value'])) {
      return (string) $value['value'];
    }
    
    return (string) $value;
  }

  /**
   * Extrait une valeur imbriquée d'un champ OO2.
   */
  private function extractNestedValue(array $data, string $field, string $subfield): string {
    if (!isset($data[$field])) {
      return '';
    }
    
    $value = $data[$field];
    
    // Structure des formations : liste avec premier élément
    if (is_array($value) && count($value) > 0 && isset($value[0][$subfield])) {
      $nested = $value[0][$subfield];
      
      // Si c'est une liste, prendre le premier élément
      if (is_array($nested) && count($nested) > 0 && isset($nested[0]['value'])) {
        return (string) $nested[0]['value'];
      }
      
      // Si c'est un objet direct
      if (is_array($nested) && isset($nested['value'])) {
        return (string) $nested['value'];
      }
      
      return (string) $nested;
    }
    
    // Structure directe
    if (is_array($value) && isset($value[$subfield])) {
      $nested = $value[$subfield];
      if (is_array($nested) && isset($nested['value'])) {
        return (string) $nested['value'];
      }
      return (string) $nested;
    }
    
    return '';
  }

  /**
   * Génère un embedding via l'API OpenAI.
   */
  private function generateEmbedding(string $text): ?array {
    $config = $this->configFactory->get('formations_widget.settings');
    $apiKey = $config->get('openai_api_key');
    
    if (!$apiKey) {
      return null;
    }

    try {
      $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/embeddings', [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => 'text-embedding-3-small',
          'input' => $text,
        ],
      ]);

      $data = json_decode($response->getBody(), true);
      return $data['data'][0]['embedding'] ?? null;
    }
    catch (GuzzleException $e) {
      \Drupal::logger('formations_widget')->error('Erreur génération embedding: @error', ['@error' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * Recherche les données les plus pertinentes pour une question.
   */
  public function searchRelevantData(string $question, int $limit = 5): array {
    $cacheKey = 'vector_search:' . md5($question) . ':' . $limit;
    
    if ($cached = $this->cache->get($cacheKey)) {
      return $cached->data;
    }

    // Générer l'embedding de la question
    $questionEmbedding = $this->generateEmbedding($question);
    if (!$questionEmbedding) {
      return ['formations' => [], 'sessions' => []];
    }

    $embeddingString = '[' . implode(',', $questionEmbedding) . ']';

    // Recherche vectorielle dans les formations
    $formations = $this->database->query("
      SELECT 
        formation_id,
        title,
        content,
        metadata,
        1 - (embedding <=> :embedding) as similarity_score
      FROM formations_vectors 
      WHERE embedding IS NOT NULL
      ORDER BY embedding <=> :embedding
      LIMIT :limit
    ", [
      ':embedding' => $embeddingString,
      ':limit' => $limit
    ])->fetchAll();

    // Recherche vectorielle dans les sessions
    $sessions = $this->database->query("
      SELECT 
        session_id,
        title,
        content,
        metadata,
        1 - (embedding <=> :embedding) as similarity_score
      FROM sessions_vectors 
      WHERE embedding IS NOT NULL
      ORDER BY embedding <=> :embedding
      LIMIT :limit
    ", [
      ':embedding' => $embeddingString,
      ':limit' => $limit
    ])->fetchAll();

    $result = [
      'formations' => $this->formatSearchResults($formations, 'formation'),
      'sessions' => $this->formatSearchResults($sessions, 'session'),
    ];

    // Cache pour 1 heure
    $this->cache->set($cacheKey, $result, time() + 3600);
    
    return $result;
  }

  /**
   * Formate les résultats de recherche.
   */
  private function formatSearchResults(array $results, string $type): array {
    $formatted = [];
    
    foreach ($results as $result) {
      $metadata = json_decode($result->metadata, true) ?? [];
      
      $formatted[] = [
        'id' => $result->formation_id ?? $result->session_id,
        'title' => $result->title,
        'content' => $result->content,
        'similarity_score' => round($result->similarity_score * 100, 2),
        'type' => $type,
        'metadata' => $metadata,
      ];
    }
    
    return $formatted;
  }

  /**
   * Génère une réponse contextuelle avec l'IA.
   */
  public function generateAnswer(string $question): array {
    // Recherche des données pertinentes
    $relevantData = $this->searchRelevantData($question, 5);
    
    // Construction du contexte
    $context = $this->buildContext($relevantData);
    
    // Génération de la réponse via OpenAI
    $answer = $this->generateResponseWithOpenAI($question, $context);
    
    return [
      'answer' => $answer,
      'sources' => $this->extractSources($relevantData),
      'context_used' => count($relevantData['formations']) + count($relevantData['sessions'])
    ];
  }

  /**
   * Construit le contexte pour l'IA.
   */
  private function buildContext(array $relevantData): string {
    $context = "Données pertinentes trouvées :\n\n";
    
    if (!empty($relevantData['formations'])) {
      $context .= "FORMATIONS PERTINENTES :\n";
      foreach ($relevantData['formations'] as $formation) {
        $context .= "• " . $formation['title'] . " (Pertinence: " . $formation['similarity_score'] . "%)\n";
        $context .= "  " . substr($formation['content'], 0, 200) . "...\n\n";
      }
    }
    
    if (!empty($relevantData['sessions'])) {
      $context .= "SESSIONS PERTINENTES :\n";
      foreach ($relevantData['sessions'] as $session) {
        $context .= "• " . $session['title'] . " (Pertinence: " . $session['similarity_score'] . "%)\n";
        $context .= "  " . substr($session['content'], 0, 200) . "...\n\n";
      }
    }
    
    return $context;
  }

  /**
   * Génère une réponse avec OpenAI en utilisant le contexte.
   */
  private function generateResponseWithOpenAI(string $question, string $context): string {
    $config = $this->configFactory->get('formations_widget.settings');
    $apiKey = $config->get('openai_api_key');
    $model = $config->get('llm_model') ?? 'gpt-4o-mini';
    
    if (!$apiKey) {
      return 'Clé API OpenAI manquante. Contactez un administrateur.';
    }

    try {
      $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => $model,
          'messages' => [
            [
              'role' => 'system',
              'content' => 'Tu es un assistant spécialisé dans les formations et sessions. Réponds uniquement en te basant sur les informations fournies. Réponds en français de manière concise et utile.'
            ],
            [
              'role' => 'user',
              'content' => "Contexte :\n" . $context . "\n\nQuestion : " . $question
            ]
          ],
          'temperature' => 0.2,
          'max_tokens' => 500,
        ],
      ]);

      $data = json_decode($response->getBody(), true);
      return $data['choices'][0]['message']['content'] ?? 'Pas de réponse générée.';
    }
    catch (GuzzleException $e) {
      \Drupal::logger('formations_widget')->error('Erreur génération réponse: @error', ['@error' => $e->getMessage()]);
      return 'Erreur lors de la génération de la réponse.';
    }
  }

  /**
   * Extrait les sources des données pertinentes.
   */
  private function extractSources(array $relevantData): array {
    $sources = [];
    
    foreach ($relevantData['formations'] as $formation) {
      $sources[] = [
        'type' => 'formation',
        'title' => $formation['title'],
        'score' => $formation['similarity_score'],
        'metadata' => $formation['metadata']
      ];
    }
    
    foreach ($relevantData['sessions'] as $session) {
      $sources[] = [
        'type' => 'session',
        'title' => $session['title'],
        'score' => $session['similarity_score'],
        'metadata' => $session['metadata']
      ];
    }
    
    return $sources;
  }

  /**
   * Obtient les statistiques de l'index vectoriel.
   */
  public function getIndexStats(): array {
    $formationsCount = $this->database->query("SELECT COUNT(*) FROM formations_vectors")->fetchField();
    $sessionsCount = $this->database->query("SELECT COUNT(*) FROM sessions_vectors")->fetchField();
    
    return [
      'formations_indexed' => (int) $formationsCount,
      'sessions_indexed' => (int) $sessionsCount,
      'total_indexed' => (int) $formationsCount + (int) $sessionsCount,
      'last_updated' => $this->getLastUpdateTime()
    ];
  }

  /**
   * Obtient la date de dernière mise à jour.
   */
  private function getLastUpdateTime(): ?string {
    $result = $this->database->query("
      SELECT MAX(created_at) as last_update 
      FROM (
        SELECT created_at FROM formations_vectors 
        UNION ALL 
        SELECT created_at FROM sessions_vectors
      ) as combined
    ")->fetchField();
    
    return $result ?: null;
  }
}
