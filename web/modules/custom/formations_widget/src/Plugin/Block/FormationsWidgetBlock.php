<?php

namespace Drupal\formations_widget\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Formations Widget block.
 *
 * @Block(
 *   id = "formations_widget_block",
 *   admin_label = @Translation("Formations Widget Block"),
 *   category = @Translation("Custom")
 * )
 */
class FormationsWidgetBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    
    $build['#markup'] = '
      <div id="formations-widget-container" style="background: #f8f9fa; border: 2px dashed #dee2e6; padding: 20px; border-radius: 8px; text-align: center;">
        <h3>🔧 Widget Formations</h3>
        <p>Chargement du widget formations...</p>
        <div id="widget-content"></div>
      </div>
    ';
    
    $build['#attached']['library'][] = 'formations_widget/widget';
    $build['#attached']['drupalSettings']['formationsWidget'] = [
      'containerId' => 'formations-widget-container',
      'apiUrl' => '/formations-widget',
    ];
    
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Titre du widget'),
      '#default_value' => 'Formations Widget',
    ];
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
  }

}
