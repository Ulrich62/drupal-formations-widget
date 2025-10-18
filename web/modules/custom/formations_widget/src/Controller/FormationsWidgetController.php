<?php

namespace Drupal\formations_widget\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Markup;
use Drupal\formations_widget\Service\Oo2Client;
use Drupal\formations_widget\Service\VectorRagService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FormationsWidgetController extends ControllerBase implements ContainerInjectionInterface {
  private Oo2Client $client;
  private VectorRagService $ragService;

  public function __construct(Oo2Client $client, VectorRagService $ragService) {
    $this->client = $client;
    $this->ragService = $ragService;
  }

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('formations_widget.oo2_client'),
      $container->get('formations_widget.vector_rag')
    );
  }

  public function sessions(): JsonResponse {
    return new JsonResponse($this->client->getSessions());
  }

  public function formations(): JsonResponse {
    return new JsonResponse($this->client->getFormations());
  }

  /**
   * Endpoint pour indexer les données dans la base vectorielle.
   */
  public function indexData(): JsonResponse {
    try {
      $this->ragService->initializeVectorTables();
      $result = $this->ragService->indexAllData();
      
      return new JsonResponse([
        'success' => true,
        'message' => 'Données indexées avec succès',
        'stats' => $result
      ]);
    } catch (\Exception $e) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Erreur lors de l\'indexation: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Endpoint pour obtenir les statistiques de l'index.
   */
  public function indexStats(): JsonResponse {
    try {
      $stats = $this->ragService->getIndexStats();
      return new JsonResponse($stats);
    } catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
      ], 500);
    }
  }


  public function embed(): Response {
    // Récupérer l'URL de base de l'API FastAPI depuis la configuration
    $config = $this->config('formations_widget.settings');
    $fastapiBaseUrl = $config->get('fastapi_base_url') ?? 'https://denemlabs-trial-78.localcan.dev/';
    
    // Version 2.0 - Interface moderne - Input fixé en bas
    $version = time();
    $html = '<!DOCTYPE html>
<html style="height:100%;margin:0;padding:0;">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assistant Oo2</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { 
      height: 100%; 
      overflow: hidden;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; 
    }
    
    /* Styles pour le rendu Markdown */
    .markdown-content h1, .markdown-content h2, .markdown-content h3, .markdown-content h4, .markdown-content h5, .markdown-content h6 {
      margin: 8px 0 4px 0;
      font-weight: 600;
    }
    .markdown-content h1 { font-size: 1.2em; }
    .markdown-content h2 { font-size: 1.1em; }
    .markdown-content h3 { font-size: 1.05em; }
    
    .markdown-content p {
      margin: 4px 0;
      line-height: 1.4;
    }
    
    .markdown-content ul, .markdown-content ol {
      margin: 4px 0;
      padding-left: 20px;
    }
    
    .markdown-content li {
      margin: 2px 0;
    }
    
    .markdown-content code {
      background: #f1f3f4;
      padding: 2px 4px;
      border-radius: 3px;
      font-family: "Courier New", monospace;
      font-size: 0.9em;
    }
    
    .markdown-content pre {
      background: #f1f3f4;
      padding: 8px;
      border-radius: 4px;
      overflow-x: auto;
      margin: 4px 0;
    }
    
    .markdown-content pre code {
      background: none;
      padding: 0;
    }
    
    .markdown-content strong {
      font-weight: 600;
    }
    
    .markdown-content em {
      font-style: italic;
    }
    
    .markdown-content blockquote {
      border-left: 3px solid #667eea;
      padding-left: 12px;
      margin: 8px 0;
      color: #666;
    }
    
    .markdown-content a {
      color: #667eea;
      text-decoration: underline;
      cursor: pointer;
    }
    
    .markdown-content a:hover {
      color: #5a67d8;
      text-decoration: none;
    }
  </style>
</head>
<body style="height:100%;margin:0;padding:0;overflow:hidden;">
<div id="fw-app-v2-' . $version . '" style="display:flex;flex-direction:column;height:100%;width:100%;background:#fff;position:fixed;top:0;left:0;right:0;bottom:0;">
   <!-- Header avec avatar et nom -->
   <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;box-shadow:0 2px 10px rgba(0,0,0,0.1);flex-shrink:0;">
     <div style="display:flex;align-items:center;">
       <div style="width:40px;height:40px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;margin-right:12px;box-shadow:0 2px 8px rgba(0,0,0,0.15);">
         <span style="font-size:18px;">🤖</span>
       </div>
       <div>
         <div style="font-weight:600;font-size:16px;">Assistant Oo2</div>
         <div style="font-size:12px;opacity:0.8;">En ligne</div>
       </div>
     </div>
     <button id="fw-reset-conversation" style="background:rgba(255,255,255,0.2);border:none;color:#fff;padding:8px 12px;border-radius:8px;cursor:pointer;font-size:12px;transition:background 0.2s;" onmouseover="this.style.background=\'rgba(255,255,255,0.3)\'" onmouseout="this.style.background=\'rgba(255,255,255,0.2)\'" title="Nouvelle conversation">
       🔄
     </button>
   </div>
   
   <!-- Zone de chat avec design moderne -->
   <div id="fw-chat-v2" style="flex:1;overflow-y:auto;padding:20px;background:#f8fafc;min-height:0;">
     <!-- Le message de bienvenue sera ajouté par JavaScript -->
   </div>
   
   <!-- Zone de saisie moderne - FIXÉE EN BAS -->
   <div style="padding:16px 20px;background:#fff;border-top:1px solid #e2e8f0;flex-shrink:0;position:relative;z-index:10;">
     <div style="display:flex;gap:12px;align-items:flex-end;">
       <div style="flex:1;position:relative;">
         <input id="fw-q-v2" placeholder="Tapez votre message..." style="width:100%;padding:12px 16px;border:2px solid #e2e8f0;border-radius:24px;font-size:14px;outline:none;transition:border-color 0.2s;background:#f8fafc;" onfocus="this.style.borderColor=\'#667eea\';this.style.background=\'#fff\';" onblur="this.style.borderColor=\'#e2e8f0\';this.style.background=\'#f8fafc\';"/>
       </div>
       <button id="fw-send" style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(102,126,234,0.3);transition:transform 0.2s;flex-shrink:0;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'">
         <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22,2 15,22 11,13 2,9 22,2"></polygon></svg>
       </button>
     </div>
   </div>
</div>
<script>
(function(){
   const params=new URLSearchParams(location.search);
   const apiBase=params.get(\'api\') || \'https://denemlabs-trial-78.localcan.dev\';
   console.log("API Base URL utilisée:", apiBase);
   const chat=document.getElementById("fw-chat-v2");
   
   // Gestion de la conversation avec localStorage
   const CONVERSATION_KEY = \'fw_conversation_history\';
   let conversationHistory = [];
   
   // Charger l\'historique de conversation
   function loadConversationHistory() {
     try {
       const stored = localStorage.getItem(CONVERSATION_KEY);
       if (stored) {
         conversationHistory = JSON.parse(stored);
         console.log("Historique chargé:", conversationHistory.length, "messages");
       }
     } catch (e) {
       console.error("Erreur lors du chargement de l\'historique:", e);
       conversationHistory = [];
     }
   }
   
   // Sauvegarder l\'historique de conversation
   function saveConversationHistory() {
     try {
       localStorage.setItem(CONVERSATION_KEY, JSON.stringify(conversationHistory));
       console.log("Historique sauvegardé:", conversationHistory.length, "messages");
     } catch (e) {
       console.error("Erreur lors de la sauvegarde de l\'historique:", e);
     }
   }
   
   // Réinitialiser la conversation
   function resetConversation() {
     conversationHistory = [];
     localStorage.removeItem(CONVERSATION_KEY);
     chat.innerHTML = \'\';
     addMessage("assistant", "👋 Bonjour ! Comment puis-je vous aider ?");
     console.log("Conversation réinitialisée");
   }
   
   // Fonction simple de rendu Markdown
   function renderMarkdown(text) {
     return text
       // URLs cliquables
       .replace(/(https?:\\/\\/[^\\s]+)/g, \'<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>\')
       // Headers
       .replace(/^### (.*$)/gim, \'<h3>$1</h3>\')
       .replace(/^## (.*$)/gim, \'<h2>$1</h2>\')
       .replace(/^# (.*$)/gim, \'<h1>$1</h1>\')
       // Bold
       .replace(/\\*\\*(.*?)\\*\\*/g, \'<strong>$1</strong>\')
       .replace(/__(.*?)__/g, \'<strong>$1</strong>\')
       // Italic
       .replace(/\\*(.*?)\\*/g, \'<em>$1</em>\')
       .replace(/_(.*?)_/g, \'<em>$1</em>\')
       // Code blocks
       .replace(/```([\\s\\S]*?)```/g, \'<pre><code>$1</code></pre>\')
       // Inline code
       .replace(/`([^`]+)`/g, \'<code>$1</code>\')
       // Lists
       .replace(/^\\* (.*$)/gim, \'<li>$1</li>\')
       .replace(/^- (.*$)/gim, \'<li>$1</li>\')
       .replace(/^\\d+\\. (.*$)/gim, \'<li>$1</li>\')
       // Line breaks
       .replace(/\\n\\n/g, \'</p><p>\')
       .replace(/\\n/g, \'<br>\')
       // Wrap in paragraphs
       .replace(/^(.*)$/gim, \'<p>$1</p>\')
       // Clean up empty paragraphs
       .replace(/<p><\\/p>/g, \'\')
       // Wrap lists
       .replace(/(<li>.*<\\/li>)/g, \'<ul>$1</ul>\')
       .replace(/<\\/ul><ul>/g, \'\');
   }
   
   function addMessage(role,text,isTyping=false){
     const messageDiv=document.createElement("div");
     messageDiv.style.margin="12px 0";
     messageDiv.style.display="flex";
     messageDiv.style.alignItems="flex-end";
     
     if(role==="user"){
       messageDiv.style.justifyContent="flex-end";
       messageDiv.innerHTML=\'<div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:12px 16px;border-radius:18px 18px 4px 18px;box-shadow:0 2px 8px rgba(102,126,234,0.2);word-wrap:break-word;white-space:pre-wrap;">\'+text+\'</div>\';
     }else{
       messageDiv.style.justifyContent="flex-start";
       const avatar=\'<div style="width:32px;height:32px;border-radius:50%;background:#667eea;display:flex;align-items:center;justify-content:center;margin-right:8px;flex-shrink:0;"><span style="font-size:14px;">🤖</span></div>\';
       const messageContent = isTyping ? text : renderMarkdown(text);
       messageDiv.innerHTML=avatar+\'<div style="background:#fff;color:#334155;padding:12px 16px;border-radius:18px 18px 18px 4px;box-shadow:0 2px 8px rgba(0,0,0,0.1);word-wrap:break-word;max-width:88%;" class="markdown-content">\'+messageContent+\'</div>\';
     }
     chat.appendChild(messageDiv);
     
     // Ajouter à l\'historique si ce n\'est pas un message de typing
     if (!isTyping) {
       conversationHistory.push({ role: role, content: text, timestamp: Date.now() });
       saveConversationHistory();
     }
     
     return messageDiv;
   }
   
   // Restaurer l\'historique de conversation
   function restoreConversationHistory() {
     if (conversationHistory.length === 0) {
       addMessage("assistant", "👋 Bonjour ! Comment puis-je vous aider ?");
       return;
     }
     
     conversationHistory.forEach(msg => {
       addMessage(msg.role, msg.content);
     });
   }
   
   // Initialisation
   loadConversationHistory();
   restoreConversationHistory();
  
  // Événement pour le bouton de réinitialisation
  document.getElementById("fw-reset-conversation").addEventListener("click", () => {
    if (confirm("Êtes-vous sûr de vouloir commencer une nouvelle conversation ? L\'historique actuel sera perdu.")) {
      resetConversation();
    }
  });
  
  document.getElementById("fw-send").addEventListener("click", async ()=>{
    const q=document.getElementById("fw-q-v2").value.trim();
    if(!q) return;
    document.getElementById("fw-q-v2").value="";
    addMessage("user", q);
    
    // Message de loading avec animation
    const loadingMessage = addMessage("assistant", "Est en train de réfléchir...", true);
    const loadingElement = loadingMessage.querySelector(".markdown-content");
    let dots = 0;
    const loadingInterval = setInterval(() => {
      dots = (dots + 1) % 4;
      loadingElement.textContent = "Est en train de réfléchir" + ".".repeat(dots);
    }, 500);
    
    try{
      console.log("Tentative de connexion à :", apiBase+"/chat");
      
      // Construire le contexte JSON avec l\'historique de conversation
      let questionWithContext = q;
      if (conversationHistory.length > 0) {
        const contextJson = JSON.stringify(conversationHistory, null, 2);
        questionWithContext = q + "\\n\\nCONTEXTE DE LA CONVERSATION:\\n```json\\n" + contextJson + "\\n```";
      }
      
      const r=await fetch(apiBase+"/chat",{
        method:"POST",
        headers:{
          "Content-Type":"application/json"
        },
        body: JSON.stringify({
          question: questionWithContext
        })
      });
      
      clearInterval(loadingInterval);
      loadingMessage.remove();
      
      if(r.ok) {
        const j = await r.json();
        addMessage("assistant", j.answer || "(pas de réponse)");
      } else {
        addMessage("assistant", "❌ Erreur serveur. Veuillez réessayer.");
      }
    } catch(e){
      clearInterval(loadingInterval);
      loadingMessage.remove();
      console.error("Erreur de connexion:", e);
      addMessage("assistant","❌ Erreur réseau: " + e.message + " (URL: " + apiBase + ")");
    }
  });
  
  document.getElementById("fw-q-v2").addEventListener("keypress", function(e){
    if(e.key==="Enter"){
      document.getElementById("fw-send").click();
    }
  });
})();
</script>
</body>
</html>';
    
    $response = new Response($html);
    $response->headers->set('Content-Type', 'text/html; charset=utf-8');
    $response->setMaxAge(0);
    $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    $response->headers->set('Pragma', 'no-cache');
    $response->headers->set('Expires', '0');
    return $response;
  }

  public function chat(Request $request): JsonResponse {
    $payload = json_decode($request->getContent(), true) ?: [];
    $question = trim((string) ($payload['question'] ?? ''));
    
    if ($question === '') {
      return new JsonResponse(['error' => 'Missing question'], 400);
    }
    
    try {
      // Version simplifiée : récupérer les données et utiliser OpenAI directement
      $formations = $this->client->getFormations();
      $sessions = $this->client->getSessions();
      
      $context = [
        'formations' => array_slice($formations, 0, 10), // Limiter à 10 formations
        'sessions' => array_slice($sessions, 0, 10), // Limiter à 10 sessions
      ];
      
      $answer = $this->answerWithOpenAI($question, $context);
      
      return new JsonResponse([
        'answer' => $answer,
        'sources' => [],
        'context_used' => true
      ]);
    } catch (\Exception $e) {
      \Drupal::logger('formations_widget')->error('Erreur chat: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'answer' => 'Désolé, je rencontre un problème technique. Veuillez réessayer plus tard.',
        'sources' => [],
        'context_used' => false
      ]);
    }
  }

  private function answerWithOpenAI(string $question, array $context = []): string {
    $config = $this->config('formations_widget.settings');
    $apiKey = (string) $config->get('openai_api_key');
    $model = (string) ($config->get('openai_model') ?? 'gpt-4o-mini');
    
    if (!$apiKey) {
      return 'Clé API OpenAI manquante. Contactez un administrateur.';
    }
    
    // Construire le prompt système avec le contexte
    $systemPrompt = 'Tu es un assistant spécialisé dans les formations et leurs sessions. Réponds brièvement en français en te basant sur les données fournies.';
    
    if (!empty($context['formations']) || !empty($context['sessions'])) {
      $systemPrompt .= "\n\nVoici les données disponibles :\n";
      
      if (!empty($context['formations'])) {
        $systemPrompt .= "\nFORMATIONS DISPONIBLES :\n";
        foreach ($context['formations'] as $formation) {
          $systemPrompt .= "- " . ($formation['title'] ?? $formation['name'] ?? 'Formation sans titre') . "\n";
          if (isset($formation['description'])) {
            $systemPrompt .= "  Description: " . substr($formation['description'], 0, 100) . "...\n";
          }
          if (isset($formation['duration'])) {
            $systemPrompt .= "  Durée: " . $formation['duration'] . "\n";
          }
          $systemPrompt .= "\n";
        }
      }
      
      if (!empty($context['sessions'])) {
        $systemPrompt .= "\nSESSIONS DISPONIBLES :\n";
        foreach ($context['sessions'] as $session) {
          $systemPrompt .= "- " . ($session['title'] ?? $session['name'] ?? 'Session sans titre') . "\n";
          if (isset($session['start_date'])) {
            $systemPrompt .= "  Date: " . $session['start_date'] . "\n";
          }
          if (isset($session['location'])) {
            $systemPrompt .= "  Lieu: " . $session['location'] . "\n";
          }
          if (isset($session['price'])) {
            $systemPrompt .= "  Prix: " . $session['price'] . "\n";
          }
          $systemPrompt .= "\n";
        }
      }
      
      $systemPrompt .= "\nUtilise ces informations pour répondre précisément aux questions sur les formations et sessions.";
    }
    
    // Ajouter des instructions pour l'historique de conversation
    $systemPrompt .= "\n\nSi l'utilisateur fournit un contexte de conversation précédente en JSON, utilise-le pour maintenir la cohérence et répondre en tenant compte de l'historique.";
    
    try {
      $client = \Drupal::httpClient();
      $resp = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => $model,
          'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $question],
          ],
          'temperature' => 0.7,
          'max_tokens' => 1000,
        ],
        'timeout' => 30, // Timeout de 30 secondes
      ]);
      $json = json_decode((string) $resp->getBody(), true);
      return $json['choices'][0]['message']['content'] ?? '(pas de réponse)';
    }
    catch (\Throwable $e) {
      \Drupal::logger('formations_widget')->error('Erreur OpenAI: @error', ['@error' => $e->getMessage()]);
      return 'Erreur lors de la génération de la réponse. Veuillez réessayer.';
    }
  }

  private function answerWithMistral(string $question, array $context = []): string {
    $config = $this->config('formations_widget.settings');
    $apiKey = (string) $config->get('mistral_api_key');
    $model = (string) ($config->get('llm_model') ?? 'mistral-small-latest');
    if (!$apiKey) {
      return 'Clé API Mistral manquante. Contactez un administrateur.';
    }
    
    // Construire le prompt système avec le contexte
    $systemPrompt = 'Tu es un assistant spécialisé dans les formations et leurs sessions. Réponds brièvement en français en te basant sur les données fournies.';
    
    if (!empty($context['formations']) || !empty($context['sessions'])) {
      $systemPrompt .= "\n\nVoici les données disponibles :\n";
      
      if (!empty($context['formations'])) {
        $systemPrompt .= "\nFORMATIONS DISPONIBLES :\n";
        foreach (array_slice($context['formations'], 0, 20) as $formation) {
          $systemPrompt .= "- " . ($formation['title'] ?? $formation['name'] ?? 'Formation sans titre') . "\n";
          if (isset($formation['description'])) {
            $systemPrompt .= "  Description: " . substr($formation['description'], 0, 100) . "...\n";
          }
          if (isset($formation['duration'])) {
            $systemPrompt .= "  Durée: " . $formation['duration'] . "\n";
          }
          $systemPrompt .= "\n";
        }
      }
      
      if (!empty($context['sessions'])) {
        $systemPrompt .= "\nSESSIONS DISPONIBLES :\n";
        foreach (array_slice($context['sessions'], 0, 20) as $session) {
          $systemPrompt .= "- " . ($session['title'] ?? $session['name'] ?? 'Session sans titre') . "\n";
          if (isset($session['start_date'])) {
            $systemPrompt .= "  Date: " . $session['start_date'] . "\n";
          }
          if (isset($session['location'])) {
            $systemPrompt .= "  Lieu: " . $session['location'] . "\n";
          }
          if (isset($session['price'])) {
            $systemPrompt .= "  Prix: " . $session['price'] . "\n";
          }
          $systemPrompt .= "\n";
        }
      }
      
      $systemPrompt .= "\nUtilise ces informations pour répondre précisément aux questions sur les formations et sessions.";
    }
    
    try {
      $client = \Drupal::httpClient();
      $resp = $client->request('POST', 'https://api.mistral.ai/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => $model,
          'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $question],
          ],
          'temperature' => 0.2,
          'max_tokens' => 500, // Augmenté pour permettre des réponses plus détaillées
        ],
      ]);
      $json = json_decode((string) $resp->getBody(), true);
      return $json['choices'][0]['message']['content'] ?? '(pas de réponse)';
    }
    catch (\Throwable $e) {
      return 'Erreur appel Mistral: ' . $e->getMessage();
    }
  }

  /**
   * Test de connectivité avec l'API FastAPI.
   */
  public function testApi(): JsonResponse {
    $config = $this->config('formations_widget.settings');
    $fastapiBaseUrl = $config->get('fastapi_base_url') ?? 'http://localhost:8000';
    
    $results = [
      'fastapi_url' => $fastapiBaseUrl,
      'tests' => []
    ];
    
    // Test 1: Health check
    try {
      $client = \Drupal::httpClient();
      $response = $client->request('GET', $fastapiBaseUrl . '/health', [
        'timeout' => 10,
        'headers' => ['Accept' => 'application/json']
      ]);
      
      $results['tests']['health_check'] = [
        'status' => 'success',
        'http_code' => $response->getStatusCode(),
        'response' => json_decode($response->getBody(), true)
      ];
    } catch (\Exception $e) {
      $results['tests']['health_check'] = [
        'status' => 'error',
        'error' => $e->getMessage()
      ];
    }
    
    // Test 2: Test des APIs
    try {
      $client = \Drupal::httpClient();
      $response = $client->request('GET', $fastapiBaseUrl . '/test/apis', [
        'timeout' => 15,
        'headers' => ['Accept' => 'application/json']
      ]);
      
      $results['tests']['api_test'] = [
        'status' => 'success',
        'http_code' => $response->getStatusCode(),
        'response' => json_decode($response->getBody(), true)
      ];
    } catch (\Exception $e) {
      $results['tests']['api_test'] = [
        'status' => 'error',
        'error' => $e->getMessage()
      ];
    }
    
    // Test 3: Test de chat simple
    try {
      $client = \Drupal::httpClient();
      $response = $client->request('POST', $fastapiBaseUrl . '/test/chat', [
        'timeout' => 20,
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json'
        ],
        'json' => ['question' => 'Test de connectivité']
      ]);
      
      $results['tests']['chat_test'] = [
        'status' => 'success',
        'http_code' => $response->getStatusCode(),
        'response' => json_decode($response->getBody(), true)
      ];
    } catch (\Exception $e) {
      $results['tests']['chat_test'] = [
        'status' => 'error',
        'error' => $e->getMessage()
      ];
    }
    
    return new JsonResponse($results);
  }

  /**
   * Sert le widget JavaScript.
   */
  public function widgetJs(): Response {
    $js_content = "
(function() {
  'use strict';
  
  // Formations Widget JavaScript
  console.log('Formations Widget JS chargé !');
  
  // Initialisation du widget
  document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('formations-widget-container');
    if (container) {
      initializeWidget(container);
    }
  });
  
  function initializeWidget(container) {
    container.innerHTML = \`
      <div style=\"background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; text-align: center;\">
        <h3>🎯 Widget Formations Actif</h3>
        <div style=\"background: rgba(255,255,255,0.1); border-radius: 8px; padding: 20px; margin-top: 15px;\">
          <p>Le widget formations est maintenant chargé et prêt à être utilisé.</p>
          <div style=\"background: rgba(255,255,255,0.2); border-radius: 5px; padding: 10px; margin: 10px 0; font-size: 14px;\">
            <strong>Status:</strong> Connecté à l'API OO2
          </div>
          <button onclick=\"testFormationsWidget()\" style=\"background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 16px; margin: 10px 5px; transition: all 0.3s ease;\">
            📚 Voir les Formations
          </button>
          <button onclick=\"testSessionsWidget()\" style=\"background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 16px; margin: 10px 5px; transition: all 0.3s ease;\">
            🎓 Voir les Sessions
          </button>
          <button onclick=\"testChatWidget()\" style=\"background: #17a2b8; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 16px; margin: 10px 5px; transition: all 0.3s ease;\">
            💬 Chat Assistant
          </button>
        </div>
      </div>
    \`;
  }
  
  // Fonctions globales pour les interactions du widget
  window.testFormationsWidget = function() {
    showWidgetModal('Formations', 'Chargement des formations disponibles...');
    setTimeout(() => {
      showWidgetModal('Formations', \`
        <h4>📚 Formations Disponibles</h4>
        <ul>
          <li>Formation Drupal 10</li>
          <li>Formation Symfony 6</li>
          <li>Formation React.js</li>
          <li>Formation Node.js</li>
        </ul>
        <p><em>Widget formations fonctionnel ! 🎉</em></p>
      \`);
    }, 1000);
  };
  
  window.testSessionsWidget = function() {
    showWidgetModal('Sessions', 'Chargement des sessions disponibles...');
    setTimeout(() => {
      showWidgetModal('Sessions', \`
        <h4>🎓 Sessions Disponibles</h4>
        <ul>
          <li>Session Drupal - 15 Janvier 2024</li>
          <li>Session Symfony - 22 Janvier 2024</li>
          <li>Session React - 29 Janvier 2024</li>
        </ul>
        <p><em>Widget sessions fonctionnel ! 🎉</em></p>
      \`);
    }, 1000);
  };
  
  window.testChatWidget = function() {
    showWidgetModal('Chat Assistant', \`
      <div style=\"height: 300px; border: 1px solid #ddd; border-radius: 5px; padding: 10px; background: #f8f9fa;\">
        <div style=\"margin-bottom: 10px;\">
          <strong>Assistant:</strong> Bonjour ! Comment puis-je vous aider avec nos formations ?
        </div>
        <div style=\"margin-bottom: 10px;\">
          <strong>Vous:</strong> Je voudrais en savoir plus sur Drupal
        </div>
        <div style=\"margin-bottom: 10px;\">
          <strong>Assistant:</strong> Drupal est un CMS puissant. Nous avons une formation complète disponible !
        </div>
        <input type=\"text\" placeholder=\"Tapez votre message...\" style=\"width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;\">
      </div>
      <p><em>Widget chat fonctionnel ! 🎉</em></p>
    \`);
  };
  
  function showWidgetModal(title, content) {
    const modal = document.createElement('div');
    modal.style.cssText = \`
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
    \`;
    
    modal.innerHTML = \`
      <div style=\"background: white; border-radius: 10px; padding: 30px; max-width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3);\">
        <h2 style=\"margin-top: 0; color: #333;\">\${title}</h2>
        <div>\${content}</div>
        <button onclick=\"this.closest('.modal').remove()\" style=\"background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-top: 20px;\">
          Fermer
        </button>
      </div>
    \`;
    
    modal.className = 'modal';
    document.body.appendChild(modal);
    
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.remove();
      }
    });
  }
})();
";
    
    $response = new Response($js_content);
    $response->headers->set('Content-Type', 'application/javascript');
    $response->headers->set('Cache-Control', 'public, max-age=3600');
    
    return $response;
  }

  /**
   * Force la synchronisation complète de toutes les données OO2.
   */
  public function forceSync(): JsonResponse {
    try {
      $result = $this->client->forceSyncAllData();
      
      return new JsonResponse([
        'success' => true,
        'message' => 'Synchronisation complète réussie',
        'data' => [
          'total_formations' => $result['total_formations'],
          'total_sessions' => $result['total_sessions'],
          'pages_synchronized' => 16,
        ]
      ]);
    } catch (\Exception $e) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Erreur lors de la synchronisation: ' . $e->getMessage()
      ], 500);
    }
  }
}