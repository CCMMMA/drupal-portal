<?php

namespace Drupal\export_json_places;

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

class ExportJsonForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'export_places_json_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['submit_export'] = array(
      '#type' => 'submit',
      '#value' => t('Export places'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('ok');
    //Creo file json
    //Iniziare l'esportazione dei places in file json (Batch operation)  
    $context['results']['test'] = 0;
    $function = 'execute_batch_export_places';
    $_SESSION['http_request_count'] = 0; // reset counter for debug information.
    // Execute the function named execute_batch_export_places.
    batch_set($this->$function());
  }
  
  public function execute_batch_export_places(){
    //azzera file json
    $path_public_directory = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $count = 0;
    $operations = array();
    //get all taxonomy type of place
    $all_type_of_place = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('type_of_place');
    foreach($all_type_of_place as $type_of_place){
      $type = $type_of_place->name;
      $tid_type = $type_of_place->tid;
      file_put_contents($path_public_directory.'/places_data_'.$type.'.json', '[');
      $query = \Drupal::entityQuery('node')
        ->condition('type','place', '=');
        //AGgiungere type
      $nids = $query->execute();
      array_splice($nids, 1);     //limitatore per test
      foreach($nids as $nid){
        $operations[] = array('write_place', array($nid, $type, $tid_type));
        $count++;
      }
    } 
    $batch = array(
      'operations' => $operations,
      'finished' => 'finished_export_places_json_callback',
      'progress_message' => t('Processed @current out of @total.'),
      'file'     => drupal_get_path('module', 'export_json_places') . '/export_json_places.inc',
    );
    return $batch;
  }
}




