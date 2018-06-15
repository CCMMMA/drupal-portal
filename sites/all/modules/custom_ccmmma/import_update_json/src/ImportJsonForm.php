<?php

namespace Drupal\import_update_json;

use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use \Drupal\Core\Queue\Batch;

use Symfony\Component\Debug\Debug;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;

//Debug::enable();
//ErrorHandler::register();
//ExceptionHandler::register();

class ImportJsonForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'import_update_json_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['file_places_json'] = array(
      '#type' => 'file',
      '#title' => t('Upload file places.json'),
      '#upload_location' => 'public://',
      '#disabled' => TRUE,
      '#prefix' => '<h3>'.t('Carica i singoli file').'</h3>',
    );

    $form['file_prods_json'] = array(
      '#type' => 'file',
      '#title' => t('Upload file prods.json'),
      '#disabled' => TRUE,
    );
    $data = file_get_contents('public://json/places.json');
    ini_set('memory_limit', '-1');
    $res = json_decode($data, TRUE);
    $count_places = count($res);
    $data = file_get_contents('public://json/prods.json');
    //print_r($data);
    $res = json_decode($data, TRUE);
    $count_prods = count($res['it-IT']['products']);
    $count_outputs = count($res['it-IT']['outputs']);
    
    
    $form['sinc_api'] = array(
      '#type' => 'checkbox',
      '#description' => 'I file sono presenti nella cartella "public://json/".<br> Attualmente verranno sincronizzati:<br> '.$count_places .' places; <br> 
      '.$count_prods.' prods;<br> '.$count_outputs.' outputs;',
      '#title' => t('Sincronizza con i file già presenti'),
      '#prefix' => '<h3>'.t('Effettua la sincronizzazione').'</h3>',

    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Process file'),
      '#button_type' => 'primary',
    );

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //eventuale validate
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  if ($form_state->getValue('sinc_api') == 1){
    dpm('è stato scelto di sinc con file già presente');
    $context['results']['test'] = 0;
    
    $function = 'execute_batch_prods_and_outputs';
    $_SESSION['http_request_count'] = 0; // reset counter for debug information.
    // Execute the function named execute_batch_prods_and_outputs.
    batch_set($this->$function());
    
    
    //batch places
    $function = 'execute_batch_places';
    $_SESSION['http_request_count'] = 0; // reset counter for debug information.
    // Execute the function named execute_batch_places.
    batch_set($this->$function());
    
    }
  }
  
  public function execute_batch_prods_and_outputs(){
    $operations = array();
    /*
     * Get prods e outputs
     */
    $data = file_get_contents('public://json/prods.json');
    ini_set('memory_limit', '-1');
    $res = json_decode($data, TRUE);
    $number_of_prods = count($res['it-IT']['products']);
    $count = 0;
    foreach($res['it-IT']['products'] as $id => $product){
      $operations[] = array('get_prod', array($id, $product, $count));
      $count++;
    }
    $count = 0;
    foreach($res['it-IT']['outputs'] as $id => $output){
      $operations[] = array('get_output', array($id, $output, $count));
      $count++;
    }
    $batch = array(
      'operations' => $operations,
      'finished' => 'finished_importer_data_callback_prods_and_outputs',
      'progress_message' => t('Processed @current out of @total.'),
      'file'     => drupal_get_path('module', 'import_update_json') . '/import_update_json.inc',
    );
    return $batch;
  }
  
  public function execute_batch_places(){
    $operations = array();
    $context['results']['places'] = 0;
    /*
     * Get places
     */
    $data = file_get_contents('public://json/places.json');   //File test
    ini_set('memory_limit', '-1');
    $res = json_decode($data, TRUE);
    $count = count($res);
    $count_attuale = 1;
    foreach($res as $id => $element){
      $operations[] = array('get_place', array($id, $element, $count_attuale));
      $count_attuale++;
    }
    $batch = array(
      'error_message' => t('Fix has encountered an error.'),
      'operations' => $operations,
      'progress_message' => t('Processed @current out of @total.'),
      'finished' => 'finished_importer_data_callback_places',
      'file'     => drupal_get_path('module', 'import_update_json') . '/import_update_json.inc',
    );
    return $batch;
  }
}




