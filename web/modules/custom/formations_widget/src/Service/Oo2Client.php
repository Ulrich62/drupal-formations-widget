<?php

namespace Drupal\formations_widget\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class Oo2Client {
  private ClientInterface $httpClient;
  private CacheBackendInterface $cache;
  private ConfigFactoryInterface $configFactory;

  public function __construct(ClientInterface $http_client, CacheBackendInterface $cache, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->configFactory = $config_factory;
  }

  public function getSessions(): array {
    $cid = 'formations_widget:sessions:v1';
    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }
    $data = $this->requestJson('https://www.oo2.fr/webservices/sessions/json');
    $this->cache->set($cid, $data, time() + 60 * 60 * 6); // 6h TTL
    return $data;
  }

  public function getFormations(): array {
    $cid = 'formations_widget:formations:v2'; // Version mise à jour
    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }
    
    \Drupal::logger('formations_widget')->info('Début de la synchronisation de 16 pages de formations');
    
    // Traitement par chunks pour éviter les problèmes de mémoire
    $all = [];
    $totalPages = 16;
    $chunkSize = 4; // Traiter 4 pages à la fois
    
    for ($chunk = 0; $chunk < $totalPages; $chunk += $chunkSize) {
      $chunkData = [];
      $endPage = min($chunk + $chunkSize, $totalPages);
      
      for ($page = $chunk; $page < $endPage; $page++) {
        $url = 'https://www.oo2.fr/webservices/formations?page=' . $page;
        \Drupal::logger('formations_widget')->info('Tentative de récupération de la page @page', ['@page' => $page + 1]);
        
        $pageData = $this->requestJson($url);
        if (is_array($pageData) && !empty($pageData)) {
          $chunkData = array_merge($chunkData, $pageData);
          \Drupal::logger('formations_widget')->info('✅ Page @page récupérée: @count formations', [
            '@page' => $page + 1,
            '@count' => count($pageData)
          ]);
        } else {
          \Drupal::logger('formations_widget')->warning('❌ Page @page vide ou erreur', ['@page' => $page + 1]);
        }
        
        // Pause pour éviter la surcharge du serveur OO2
        usleep(500000); // 0.5 seconde
      }
      
      // Ajouter le chunk aux données totales
      $all = array_merge($all, $chunkData);
      
      // Libérer la mémoire du chunk
      unset($chunkData);
      
      // Pause plus longue entre les chunks
      usleep(200000); // 0.2 seconde
    }
    
    \Drupal::logger('formations_widget')->info('Synchronisation terminée: @total formations récupérées', ['@total' => count($all)]);
    
    $this->cache->set($cid, $all, time() + 60 * 60 * 24 * 7); // 7j TTL
    return $all;
  }

  /**
   * Force la synchronisation complète en vidant le cache.
   */
  public function forceSyncAllData(): array {
    // Vider le cache des formations
    $this->cache->delete('formations_widget:formations:v2');
    
    // Vider le cache des sessions
    $this->cache->delete('formations_widget:sessions:v1');
    
    \Drupal::logger('formations_widget')->info('Cache vidé, synchronisation complète forcée');
    
    // Récupérer toutes les données
    $formations = $this->getFormations();
    $sessions = $this->getSessions();
    
    return [
      'formations' => $formations,
      'sessions' => $sessions,
      'total_formations' => count($formations),
      'total_sessions' => count($sessions),
    ];
  }

  private function requestJson(string $url): array {
    $config = $this->configFactory->get('formations_widget.settings');
    $authHeader = $config->get('oo2_basic_auth');
    
    // Utiliser file_get_contents avec un contexte personnalisé et timeout long
    $context = stream_context_create([
      'http' => [
        'method' => 'GET',
        'header' => !empty($authHeader) ? 'Authorization: Basic ' . $authHeader . "\r\n" : '',
        'timeout' => 300, // 5 minutes de timeout
        'ignore_errors' => true,
      ],
    ]);
    
    try {
      $content = file_get_contents($url, false, $context);
      if ($content === false) {
        return [];
      }
      
      $json = json_decode($content, true);
      return is_array($json) ? $json : [];
    }
    catch (\Exception $e) {
      \Drupal::logger('formations_widget')->error('Erreur requête @url: @error', [
        '@url' => $url,
        '@error' => $e->getMessage()
      ]);
      return [];
    }
  }
}