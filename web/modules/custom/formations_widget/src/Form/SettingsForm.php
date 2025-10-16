<?php

namespace Drupal\formations_widget\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {
  protected function getEditableConfigNames() {
    return ['formations_widget.settings'];
  }

  public function getFormId() {
    return 'formations_widget_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('formations_widget.settings');
    
    $form['fastapi_settings'] = [
      '#type' => 'fieldset',
      '#title' => 'Configuration API FastAPI',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    
    $form['fastapi_settings']['fastapi_base_url'] = [
      '#type' => 'url',
      '#title' => 'URL de base de l\'API FastAPI',
      '#default_value' => $config->get('fastapi_base_url') ?? 'http://localhost:8000',
      '#description' => 'URL complète de votre API FastAPI (ex: http://localhost:8000 ou https://api.votre-domaine.com)',
      '#required' => TRUE,
    ];
    
    $form['fastapi_settings']['test_api'] = [
      '#type' => 'markup',
      '#markup' => '<div><a href="/formations-widget/test-api" target="_blank" class="button">🧪 Tester la connectivité API</a></div>',
      '#description' => 'Cliquez pour tester la connectivité avec votre API FastAPI.',
    ];
    
    $form['legacy_settings'] = [
      '#type' => 'fieldset',
      '#title' => 'Configuration Legacy (pour compatibilité)',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => 'Ces paramètres ne sont plus utilisés avec l\'API FastAPI mais conservés pour compatibilité.',
    ];
    
    $form['legacy_settings']['oo2_basic_auth'] = [
      '#type' => 'textfield',
      '#title' => 'OO2 Basic Authorization (base64)',
      '#default_value' => $config->get('oo2_basic_auth'),
      '#description' => 'Ex: Qm9va2luZzpSaHVQb2k2VA==',
    ];
    $form['legacy_settings']['openai_api_key'] = [
      '#type' => 'textfield',
      '#title' => 'OpenAI API Key',
      '#default_value' => $config->get('openai_api_key'),
      '#description' => 'Obtenez votre clé sur https://platform.openai.com/api-keys',
    ];
    $form['legacy_settings']['llm_model'] = [
      '#type' => 'select',
      '#title' => 'Modèle OpenAI',
      '#default_value' => $config->get('llm_model') ?? 'gpt-4o-mini',
      '#options' => [
        'gpt-4o-mini' => 'GPT-4o Mini (recommandé - $0.15/1M tokens)',
        'gpt-4o' => 'GPT-4o ($5/1M tokens)',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo ($0.5/1M tokens)',
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->configFactory->getEditable('formations_widget.settings')
      ->set('fastapi_base_url', $form_state->getValue('fastapi_base_url'))
      ->set('oo2_basic_auth', $form_state->getValue('oo2_basic_auth'))
      ->set('openai_api_key', $form_state->getValue('openai_api_key'))
      ->set('llm_model', $form_state->getValue('llm_model'))
      ->save();
  }
}