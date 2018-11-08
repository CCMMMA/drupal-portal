<?php

namespace Drupal\radar_manage\Form;

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


class radarForm extends FormBase {

  private $products;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'radar_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#cache'] = ['max-age' => 0];

    //add radar library
    $form['#attached']['library'][] = 'radar_manage/radar-library';

    // get url of api
    $api = \Drupal::config('api.settings')->get('api');


    //Default value
    $prod = 'wrf5';
    $place_id = 'com63049'; // reg15
    $output = 'gen';

    // get data from args
    if(isset($_GET['product']) && !empty($_GET['product'])){
      $prod = $_GET['product'];
    }

    if (isset($_GET['mappa']) && !empty($_GET['mappa'])) {
      $mappa = $_GET['mappa'];
      if ($mappa == 'technical') {
        $tipomappa = "plot";
      }
      else {
        $tipomappa = "map";
      };
    } else{
      $tipomappa = "map";
    }


    if(isset($_GET['place']) && !empty($_GET['place'])){
      $place_id = $_GET['place'];
    }

    if(isset($_GET['output']) && !empty($_GET['output'])){
      $output = $_GET['output'];
    }

    if(isset($_GET['date']) && !empty($_GET['date'])){
      $date = $_GET['date'];
      $date_strtotime = strtotime($date) - 7200; //-2 ore
      $utc = date("H",$date_strtotime);
      $current_minutes = date('i', $date_strtotime);
    } else{
      //default case
      $date_strtotime = time();
      $current_minutes = 0;
      $utc = date("H");
      $date = date('Ymd\Z', time()).$utc.floor($current_minutes/10)*10;
    }

    // load node entity of place
    $place_node_default = $this->get_place_node_by_id($place_id);
    $id_field = $place_node_default->get('field_id_place');
    $id_place = $id_field->value;

    //get default outputs of default product
    $url_get_outputs = $api.'/products/'.$prod.'/outputs';
    $client = \Drupal::httpClient();
    $request = $client->get($url_get_outputs, ['http_errors' => FALSE]);
    $response = json_decode($request->getBody());
    $output_options = array();
    foreach($response->outputs as $nome => $value){
      $output_options[$nome] = $value->en;
    }

    //recupero tutti i products disponibili
    $api = \Drupal::config('api.settings')->get('api');
    $url_get_products = $api.'/products';
    $client = \Drupal::httpClient();
    $request = $client->get($url_get_products, ['http_errors' => FALSE]);
    $response = json_decode($request->getBody());
    $product_options = array();

    $this->products = $response;

    foreach($response->products as $nome => $value){
     if ($nome=="rdr1" || $nome=="rdr2") {
      $product_options[$nome] = $value->desc->en;
     }
    }


    $date_used = date("Y-m-d", $date_strtotime); //Y-m-d
    $date_form = $date_used;  //da utilizzare nel form
    $utc_list = range(0, 23);

    $form['#prefix'] = "<div class='form-ajax-reload'>";
    $form['#suffix'] = "</div>";
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

    if(isset($output_options[$output])){
      $form['output']['#default_value'] = $output;
    }


    $form['date'] = array(
      '#type' => 'date',
      '#title' => $this->t('DATE'),
      '#default_value' => $date_form,
    );

    $form['utc'] = array(
      '#type' => 'select',
      '#title' => $this->t('UTC (CET=UTC+1)'),
      '#options' => $utc_list,
      '#default_value' => (int)$utc,
    );

    $form['minutes'] = array(
      '#type' => 'select',
      '#title' => $this->t('Minutes'),
      '#options' => $this->getOptionsMinutesFromProduct($prod),
      '#default_value' => floor($current_minutes/10),
    );

    $tmap_type = ['technical' => 'Technical', 'nonTecnical' => 'Non technical'];
    $form['mappa'] = array(
      '#type' => 'select',
      '#title' => $this->t('Change Map type'),
      '#options' => $tmap_type,
      '#default_value' => 'technical',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Generate'),
      '#button_type' => 'primary',
      '#prefix' => '<div class="hidden-submit">',
      '#suffix' => '</div>',
    );


    $form['#attributes']['class'][] = 'form-radar';

    $form['#suffix'] = "<div id='ajax-loader-marker' style='width: 100%; text-align: center; display: none'><img id='ajax_loader' style='width: 3%' src='/sites/all/themes/zircon_custom/images/ajax-loader.gif'></div>";

    //@todo gestire questi link
    $link_change_hour = '<div class="container-link"><p class="change-hour previous"><< (-1h) Previous</p><p class="change-hour next">(+1h) Next >></p></div>';

    //get data from url for generate img
    $api = \Drupal::config('api.settings')->get('api');

    $date_for_api = date('Ymd\Z', strtotime($date_form)).$utc.floor($current_minutes/10)*10;

    $url_call = $api.'/products/rdr1/forecast/'.$prod.'/'.$tipomappa.'?output='.$output.'&date='.$date_for_api;

    $client = \Drupal::httpClient();

    $request = $client->get($url_call);
    $response = json_decode($request->getBody());

    $link_map = NULL;

    if(isset($response->map->link)){
      $link_map = $response->map->link;
    }
    $markup_legend_left = '<div class="col-lg-2"><img class="legend-left" src="http://193.205.230.6/products/'.$prod.'/radar/legend/left/gen?width=64&height=563&date='.$date.'"></div>';
    $markup_legend_right = '<div class="col-lg-2"><img class="legend-right" src="http://193.205.230.6/products/'.$prod.'/radar/legend/right/gen?width=64&height=563&date='.$date.'"></div>';
    $markup_legend_bottom = '<div class="col-lg-8 col-lg-offset-2"><img class="legend-bottom" src="http://193.205.230.6/products/'.$prod.'/radar/legend/bottom/gen?width=768&height=64&date='.$date.'"></div>';

    //dpm('link alla mappa: '.$link_map);
    if($link_map === NULL){
      $img_result = '<p>Impossibile caricare immagine</p>';
    }
    else{
      $img_result = '<div class="col-lg-8"><img class="img-radar" src="'.$link_map.'"></div>';
    }

    $markup_image = '<div class="row">';
    $markup_image .= $markup_legend_left . $img_result . $markup_legend_right . $markup_legend_bottom;
    $markup_image .= '</div>';



    $suffix_markup = $link_change_hour . $markup_image;
    $form['#suffix'] .= $suffix_markup;

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
    $minutes = $form_state->getValue('minutes')*10;
    $utc = $form_state->getValue('utc');
    $mappa = $form_state->getValue('mappa');
    if ($mappa=='technical') {
        $tipomappa="plot";
    } else {
        $tipomappa="map";
    };

    //recupero l'id del place dal nid ottenuto
    $node = \Drupal\node\Entity\Node::load($place_nid);
    $id_field = $node->get('field_id_place');
    $id_place = $id_field->value;

    // gestisco il formato della data
    $date_strtotime = strtotime($date);
    $part_date = date('Ymd', $date_strtotime);
    $final_date_now = $part_date.'Z'.$utc.$minutes;


    $form_state->setResponse(new RedirectResponse('/instruments/radar?product='.$product.'&place='.$id_place.'&output='.$output.'&date='.$final_date_now, 302));
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
    $mappa = $form_state->getValue('mappa');
    if ($mappa=='technical') {
        $tipomappa="plot";
    } else {
        $tipomappa="map";
    };

    //dpm($url_get_outputs);

    foreach($response->outputs as $nome => $value){
      $option_output[$nome] = $value->en;
    }
    $form['output']['#options'] = $option_output;

    if(in_array('gen', $option_output)){
      $form['output']['#default_value'] = 'gen';
    } else{
      unset($form['output']['#default_value']);
    }

    $options_minutes = $this->getOptionsMinutesFromProduct($product);

    $form['minutes']['#options'] = $options_minutes;


    $response_ajax->addCommand(new ReplaceCommand('.form-ajax-reload', $form));
    return $response_ajax;
  }

  private function get_place_node_by_id($place_id){
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('field_id_place', $place_id);

    $nids = $query->execute();
    $nid_value = array_values($nids);
    $nid =  array_shift($nid_value);
    $entity_place = Node::load($nid);
    return $entity_place;
  }

  private function getOptionsMinutesFromProduct($product){
    $api = \Drupal::config('api.settings')->get('api');
    $url = $api.'/products/'.$product;

    $client = \Drupal::httpClient();
    $request = $client->get($url);

    $response = json_decode($request->getBody());
    $timestep = $response->outputs->timestep;

    $options_timestep_60 = ['00' => '00'];
    $options_timestep_10 = ['00' => '00', '10' => '10', '20' => '20', '30' => '30', '40' => '40', '50' => '50'];

    if($timestep == 60){
      return $options_timestep_60;
    } else {
      return $options_timestep_10;

    }

  }

}




