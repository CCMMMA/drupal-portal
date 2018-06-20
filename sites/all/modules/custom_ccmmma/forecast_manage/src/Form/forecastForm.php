<?php

namespace Drupal\forecast_manage\Form;
//namespace Drupal\Core\Ajax;

use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use \Drupal\Core\Queue\Batch;

use Symfony\Component\Debug\Debug;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;

use Drupal\Core\Ajax\ReplaceCommand;

use Drupal\Core\Ajax\AppendCommand;

//Debug::enable();
//ErrorHandler::register();
//ExceptionHandler::register();

class forecastForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forecast_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    //add forecast library
    $form['#attached']['library'][] = 'forecast_manage/forecast-library';

    $date_now = date('Y-m-d'); // Y-m-d now
    $date_time_series = date('Ymd');  // Ymd
    $hour_now = date('H'); // H
    $final_date_now = $date_time_series.'Z'.$hour_now.'00';  // YmdNH

    
    //Default value
    $prod = 'ww33';
    $place_id = 'ca000';
    $output = 'gen';
    $date = $final_date_now;
    $utc = $hour_now;
    
  
    
    
    if(isset($_GET['product']) && !empty($_GET['product'])){
      $prod = $_GET['product'];
    }
    if(isset($_GET['place']) && !empty($_GET['place'])){
      $place_id = $_GET['place'];
    }
    
    if(isset($_GET['output']) && !empty($_GET['output'])){
      $output = $_GET['output'];
    }
    
    if(isset($_GET['date']) && !empty($_GET['date'])){
      $date = $_GET['date'];
    }
    if(isset($_GET['utc']) && !empty($_GET['utc'])){
      $utc = $_GET['utc'];
    }
    
    $place_node_default = $this->get_place_node_by_id($place_id);
    
    //get default output of default product
    $api = \Drupal::config('api.settings')->get('api');
    $url_get_products = $api.'/products/'.$prod.'/outputs';
    $client = \Drupal::httpClient();
    $request = $client->get($url_get_products);
    $response = json_decode($request->getBody());
    $output_options = array();
    foreach($response->outputs as $nome => $value){
      $output_options[$nome] = $value->en;
    }
    //dpm($output_options);
    //dpm($url_get_products);
    
    
    //recupero tutti i products disponibili
    $api = \Drupal::config('api.settings')->get('api');
    $url_get_products = $api.'/products';
    $client = \Drupal::httpClient();
    $request = $client->get($url_get_products);
    $response = json_decode($request->getBody());
    //dpm($response->products);
    $product_options = array();
    foreach($response->products as $nome => $value){
      $product_options[$nome] = $value->desc->en;
      //dpm($nome);
    }

    //dpm($product_options);
    //dpm($output_options);
    
    /*
    dpm($prod);
    dpm($place_id);
    dpm($output);
    dpm($date);
    dpm($utc);
    */
    
    //dpm('data richiesta: '.$date);
    
    $date_used = date("Y-m-d", strtotime($date)); //Y-m-d 
    $date_form = $date_used;  //da utilizzare nel form
    $utc_list = range(0, 24);
    
    
    /*************************/
    
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
     
    //todo get default place from url and load node by field_id_place 
    
    $form['product'] = array(
      '#type' => 'select',
      '#title' => t('PRODUCT'),
      '#options' => $product_options,
      '#default_value' => $prod,
      '#ajax' => [
        'callback' => array($this, 'ajax_populateOutput'),
        'wrapper' => 'edit-load-output',
      ],
    );

    $form['output'] = array(
      '#type' => 'select',
      '#title' => t('OUTPUT'),
      '#options' => $output_options,
      '#prefix' => '<span id="edit-load-output">',
      '#suffix' => '</span>',
    );
    $form['date'] = array(
      '#type' => 'date',
      '#title' => t('DATA'),
      '#default_value' => $date_form,
    );
    $form['utc'] = array(
      '#type' => 'select',
      '#title' => t('UTC (CET=UTC+2)'),
      '#options' => $utc_list,
      '#default_value' => $hour_now,
    );


    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Generate'),
      '#button_type' => 'primary',
    );


    $form['#attributes']['class'][] = 'form-forecast';
    
    $link_change_hour = '<div class="container-link"><p class="change-hour previous"><< (-1h) Previous</p><p class="change-hour next">(+1h) Next >></p></div>';
    
    //get data from url for generate img
    $api = \Drupal::config('api.settings')->get('api');
        
    $url_call = $api.'/products/'.$prod.'/forecast/'.$place_id.'/map';

    $client = \Drupal::httpClient();

    $request = $client->get($url_call);
    $response = json_decode($request->getBody());

    $link_map = NULL;

    if(isset($response->map->link)){
      $link_map = $response->map->link;

    }
/*
    foreach($response->data->place->forecast as $value){
      if(property_exists($value, $date)){
        $link_map = $value->$date->map;
        //dpm($link_map);
      }
    }
*/
   
    //dpm('link alla mappa: '.$link_map);
    if($link_map === NULL){
      $img_result = '<p>Impossibile caricare immagine</p>';
    }
    else{
      $img_result = '<img class="img-forecast" src="'.$link_map.'">'; 
    }
    
    $suffix_markup = $link_change_hour . $img_result;
    $form['#suffix'] = $suffix_markup;
    
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
    $product = $form_state->getValue('product');
    $place_nid = $form_state->getValue('place');
    $output = $form_state->getValue('output');
    $date = $form_state->getValue('date');
    $utc = $form_state->getValue('utc');
    
    //recupero l'id del place dal nid ottenuto
    $node = \Drupal\node\Entity\Node::load($place_nid);
    $id_field = $node->get('field_id_place');
    $id_place = $id_field->value;
    $date = str_replace('-', "", $date);
    $final_date_now = $date.'Z'.$utc.'00';

    $host = \Drupal::request()->getHost();

        
    $form_state->setResponse(new RedirectResponse('/forecast/forecast?product='.$product.'&place='.$id_place.'&output='.$output.'&date='.$final_date_now.'&utc='.$utc, 302));
  }
  
  // Ajax Call for output
  public function ajax_populateOutput($form, FormStateInterface $form_state){
    $option_output = array();
    $response_ajax = new AjaxResponse();
    
    //get option output of product
    $product = $form_state->getValue('product');
    $api = \Drupal::config('api.settings')->get('api');
    $url_get_outputs = $api.'/products/'.$product.'/outputs';
    $client = \Drupal::httpClient();
    $request = $client->get($url_get_outputs);
    $response = json_decode($request->getBody());
    
    //dpm($url_get_outputs);
    
    foreach($response->outputs as $nome => $value){
      $option_output[$nome] = $value->en;
    }
    $form['output']['#options'] = $option_output;    
    
    $response_ajax->addCommand(new ReplaceCommand('#edit-load-output', $form['output']));
    return $response_ajax;
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




