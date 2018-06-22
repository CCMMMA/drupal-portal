<?php
  
  /**
 * @file
 * Contains \Drupal\example\Form\exampleSettingsForm
 */
namespace Drupal\home\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class bollettinoSettingsForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bollettino_admin_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bollettino.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bollettino.settings');

    $config = $this->config('bollettino.settings');
    //dpm('place attualmente settato: '.$config->get('place'));


    $nid = $config->get('place');
    // Get a node storage object.
    $node_place = entity_load('node', $nid);



    $default_place_id = 'com63049';
    $place_node_default = isset($place_id) ? $node_place :  $this->get_place_node_by_id($default_place_id);

    $default_options_colums = $config->get('active_colums');

    $options_colums = array(
      'icon' => 'icon',
      'wd10' => 'wd10',
      'ws10-max' => 'ws10-max',
      'ws10-min' => 'ws10-min',
      'ws10' => 'ws10',
      'crh' => 'crh',
      't2c-min' => 't2c-min',
      't2c-max' => 't2c-max',
      't2c' => 't2c',
    );

    $form['place'] = array(
      '#type' => 'entity_autocomplete',
      '#title' => t('PLACE'),
      '#target_type' => 'node',
      '#default_value' => $place_node_default,
      '#selection_settings' => array(
        'target_bundles' => array('node', 'place'),
      ),
      '#size' => 30,
      '#maxlength' => 60,
    );
    $form['active_colums'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Columns to display'),
      '#options' => $options_colums,
      '#title' => $this->t('Columns to display'),
      '#default_value' => isset($default_options_colums) ? $default_options_colums : [],
    );


    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('bollettino.settings');
    $config->set('place', $form_state->getValue('place'))->save();
    $config->set('active_colums', $form_state->getValue('active_colums'))->save();


    parent::submitForm($form, $form_state);
  }

  private function get_place_node_by_id($place_id){
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('field_id_place', $place_id);

    $nids = $query->execute();
    $nid_value = array_values($nids);
    $nid =  array_shift($nid_value);
    $entity_place = entity_load('node', $nid);
    return $entity_place;
  }
}
