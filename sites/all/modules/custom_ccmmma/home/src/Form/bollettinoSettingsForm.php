<?php
  
  /**
 * @file
 * Contains \Drupal\example\Form\exampleSettingsForm
 */
namespace Drupal\home\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;



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
    $nid = $config->get('place');

    // manage ajax callback
    $triggeringElement = $form_state->getTriggeringElement();
    if(!is_null($triggeringElement) && $triggeringElement['#type'] == 'select' && $triggeringElement['#name'] == 'product'){
      $prod = $form_state->getValue('product');
      $fields = $this->get_all_fields($prod, TRUE);
      $fields_default = [];
    } else{
      //load default config
      $prod = $config->get('product');
      $fields = $this->get_all_fields($prod, TRUE);
      $fields_default = $config->get('fields');
    }

    $prod_default = isset($prod) ? $prod : 'wrf5';
    $product_options = $this->get_all_products(true);

    if(!isset($prod) || empty($prod)){
      $fields = $this->get_all_fields($prod_default, TRUE);
    }


    //get default step
    $step = $config->get('step');
    $default_step = isset($step) && !empty($step) ? $step : '24';


    $node_place = entity_load('node', $nid);
    $default_place_id = 'com63049';
    $place_node_default = isset($node_place) ? $node_place :  $this->get_place_node_by_id($default_place_id);

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

    $form['step'] = array(
      '#type' => 'textfield',
      '#attributes' => array(
        ' type' => 'number',
      ),
      '#title' => 'Step',
      '#required' => true,
      '#default_value' => $default_step,
      '#maxlength' => 3
    );


    $form['product'] = array(
      '#type' => 'select',
      '#title' => t('Product'),
      '#options' => $product_options,
      '#default_value' => $prod_default ,
      '#ajax' => [
        'callback' => array($this, 'ajax_populateFields'),
        'wrapper' => 'edit-load-fields',
      ],
    );

    $form['fields'] = array(
      '#type' => 'checkboxes',
      '#options' => $fields,
      '#title' => $this->t('Fields to display'),
      '#default_value' => $fields_default,
      '#prefix' => '<span id="edit-load-fields">',
      '#suffix' => '</span>',
      '#required' => TRUE,
    );


    return parent::buildForm($form, $form_state);
  }

  // Ajax Call for output
  public function ajax_populateFields($form, FormStateInterface $form_state){
    $option_output = array();
    $response_ajax = new AjaxResponse();

    $product = $form_state->getValue('product');
    $fields = $this->get_all_fields($product, true);

    foreach($fields as $nome => $value){
      $option_fields[$nome] = $value->title->it;
    }
    $form['fields']['#options'] = $option_fields;
    if(empty($option_fields)){
      $form['fields']['#suffix'] = '<p>'.$this->t('No fields available').'</p>';
    }

    $response_ajax->addCommand(new ReplaceCommand('#edit-load-fields', $form['fields']));
    return $response_ajax;
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('bollettino.settings');
    $config->set('place', $form_state->getValue('place'))->save();
    $config->set('fields', $form_state->getValue('fields'))->save();
    $config->set('product', $form_state->getValue('product'))->save();
    $config->set('step', $form_state->getValue('step'))->save();


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

  private function get_all_products($only_key = FALSE){
    $bollettino_service = \Drupal::service('home.BollettinoServices');
    $bollettino_service->SetAllProducts($only_key);
    $products = $bollettino_service->GetAllProducts();
    //@todo pass arguments to costructor
    return $products;
  }

  private function get_all_fields($product, $only_key = FALSE){
    $bollettino_service = \Drupal::service('home.BollettinoServices');
    $bollettino_service->SetAllFields($product, $only_key);
    $fields = $bollettino_service->GetAllFields();
    //@todo pass arguments to costructor
    return $fields;
  }

}
