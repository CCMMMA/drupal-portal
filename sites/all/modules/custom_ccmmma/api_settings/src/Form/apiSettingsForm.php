<?php
  
  /**
 * @file
 * Contains \Drupal\example\Form\exampleSettingsForm
 */
namespace Drupal\api_settings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class apiSettingsForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'api_admin_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'api.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('api.settings');
    dpm('Api attualmente settata: '.$config->get('api'));

    $form['api'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URL API'),
      '#default_value' => $config->get('api'),
    );  

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('api.settings');
    $config->set('api', $form_state->getValue('api'))->save();

    parent::submitForm($form, $form_state);
  }
}
