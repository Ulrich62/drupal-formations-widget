<?php

namespace Drupal\formations_widget\Commands;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\formations_widget\Service\Oo2Client;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Commandes Drush pour le module Formations Widget.
 */
class FormationsWidgetCommands extends DrushCommands implements ContainerInjectionInterface {

  private Oo2Client $oo2Client;

  public function __construct(Oo2Client $oo2Client) {
    $this->oo2Client = $oo2Client;
  }

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('formations_widget.oo2_client')
    );
  }

  /**
   * Force la synchronisation complète de toutes les données OO2.
   *
   * @command formations-widget:force-sync
   * @aliases fw:sync
   * @usage drush formations-widget:force-sync
   */
  public function forceSync(): void {
    $this->logger()->info('Début de la synchronisation complète...');
    
    try {
      $result = $this->oo2Client->forceSyncAllData();
      
      $this->logger()->success('Synchronisation terminée avec succès !');
      $this->logger()->info('Formations: ' . $result['total_formations']);
      $this->logger()->info('Sessions: ' . $result['total_sessions']);
      $this->logger()->info('Total: ' . ($result['total_formations'] + $result['total_sessions']) . ' éléments');
      
    } catch (\Exception $e) {
      $this->logger()->error('Erreur lors de la synchronisation: ' . $e->getMessage());
    }
  }
}
