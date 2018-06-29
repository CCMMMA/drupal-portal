<?php

namespace Drupal\home\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

use Drupal\Core\Ajax\AjaxResponse;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\Core\Ajax\ReplaceCommand;



class forecastTableForm extends FormBase {

  private $prod;
  private $id_place;
  private $date;
  private $place_name;
  private $step;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forecast_table_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    if(isset($_GET['product']) && !empty($_GET['product'])){
      $this->prod = $_GET['product'];
    } else{
      $this->prod = \Drupal::config('forecast-table.settings')->get('product');;
    }

    if(isset($_GET['place']) && !empty($_GET['place'])){
      $this->id_place = $_GET['place'];
      $place_node_default = $this->get_place_node_by_id($this->id_place);
    } else{
      $nid = \Drupal::config('forecast-table.settings')->get('place');
      $place_node_default = entity_load('node', $nid);
      $this->id_place = $place_node_default->get('field_id_place')->getValue()[0]['value'];
    }

    $this->place_name = $place_node_default->get('field_long_name')->getValue()[0]['value'];


    if(isset($_GET['step']) && $_GET['step'] != ''){
      $this->step = $_GET['step'];
    } else{
      $this->step = \Drupal::config('forecast-table.settings')->get('step');
    }

    if(isset($_GET['date']) && !empty($_GET['date'])){
      $this->date = $_GET['date'];
    } else{
      $this->date = date('Ymd\Z\0\0', time());
    }

    //get default output of default product
    $api = \Drupal::config('api.settings')->get('api');


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

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Generate'),
      '#button_type' => 'primary',
    );

    $table_markup = $this->GenerateMarkupTable();

    $form['table_markup'] = array(
      '#type' => 'markup',
      '#markup' => $table_markup,
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
    $place_nid = $form_state->getValue('place');

    //recupero l'id del place dal nid ottenuto
    $node = \Drupal\node\Entity\Node::load($place_nid);
    $id_field = $node->get('field_id_place');
    $this->place_id = $id_field->value;
    $date = str_replace('-', "", $this->date);

    $host = \Drupal::request()->getHost();


    $form_state->setResponse(new RedirectResponse('/forecast/table?product=wrf5&place='.$this->place_id.'&date='.$date, 302));
  }


  private function get_place_node_by_id($place_id){
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('field_id_place', $place_id);

    $nids = $query->execute();
    $nid_value = array_values($nids);
    $nid =  array_shift($nid_value);
    $entity_place = \Drupal\node\Entity\Node::load($nid);
    return $entity_place;
  }

  private function GenerateMarkupTable(){
    $bollettino_service = \Drupal::service('home.BollettinoServices');

    $fields = \Drupal::config('forecast-table.settings')->get('fields');

    $fields_position = \Drupal::config('forecast-table.settings')->get('fields_position');
    $fields_position = explode("\n", $fields_position);

    $api = \Drupal::config('api.settings')->get('api');


    $url_timeseries = $api . '/products/'.$this->prod.'/timeseries/'.$this->id_place.'?step='.$this->step.'&date=' . date('Ymd\Z\0\0', time());
    //dpm($url_timeseries);

    //creo un array con 6 date a partire da oggi
    $list_of_day = [];
    $list_of_result = [];
    $days = [
      'Domenica',
      'Lunedì',
      'Martedì',
      'Mercoledì',
      'Giovedì',
      'Venerdì',
      'Sabato',
    ];

    $list_of_day[date('Ymd\Z\0\000', time())] = $days[date('w', time())] . ' ' . date('d', time()); //oggi
    $i = 1;
    for ($i; $i <= 7; $i++) {
      // calcolo i successivi n giorni
      $list_of_day[date('Ymd\Z\0\000', time() + (86400 * $i))] = $days[date('w', time() + (86400 * $i))] . ' ' . date('d', time() + (86400 * $i)); //oggi
    }

    //effettuo la chiamata http alle api
    $client = new \GuzzleHttp\Client();
    try {
      $request = $client->get($url_timeseries, ['http_errors' => FALSE]);
      $response = json_decode($request->getBody());
      if (isset($response->timeseries)) {
        // gestisco i dati ottenuti
        foreach ($response->timeseries as $single_value) {
          //if (array_key_exists($single_value->dateTime, $list_of_day)) {
            $list_of_result[$single_value->dateTime] = $single_value;
          //}
        }
      }
    } catch (RequestException $e) {
      $list_of_result = [];
    }

    //dpm($list_of_day);
    //dpm($list_of_result);




    // ottengo il base_path per visualizzare le immagini
    global $base_url;
    $path_publich = $base_url . '/sites/all/themes/zircon_custom/js/images/';


    $markup = '';
    $data = [];


    // intestazione tabella
    $markup .= '    <div id="box">  <div class="title">Meteo '.$this->place_name.'   <a href="http://meteo.uniparthenope.it" target="_blank" title="meteo.uniparthenope.it">    </a>  </div>';


    // gestisco i campi da visualizzare
    foreach($fields as $field => $display){
      $field_name = str_replace("-","_",$field);
      if($field == $display && $display !== 0){
        $field_name = str_replace("-","_",$field);
        ${$field_name.'_setted'} = TRUE;
      } else{
        $field_name = str_replace("-","_",$field);
        ${$field_name.'_setted'} = FALSE;
      }
    }

    $bollettino_service->SetAllFields($this->prod);
    $all_fields = $bollettino_service->GetAllFields();

    //creazione header della tabella
    $markup .= '
      <table id="table-forecast" width="100%" cellspacing="0" cellpadding="2" border="0" style="">
        <tbody>
          <tr class="legenda">
            <td>Previsione</td>';


    foreach($fields_position as $field_name){
      $field_name = preg_replace('/\s+/', '', $field_name);
      $field_name_underscore = str_replace('-','_', $field_name);

      if(isset(${$field_name_underscore .'_setted'}) && ${$field_name_underscore .'_setted'}){
        $markup .= '<td>'.$all_fields->{$field_name}->title->it.'</td>';
      }
    }

    $markup .= '</tr>';

    foreach ($list_of_result as $time => $value) {
      $string_date = $this->GetTimestampFromDate($time);
    //foreach ($list_of_day as $time => $string_date) {
      //if (isset($list_of_result[$time])) {
        $markup .= '<tr>';
        //stampo la data in versione stringa e link.
        $url_forecast = $base_url.'/forecast/forecast?'.$list_of_result[$time]->link;
        $markup .= '<td class="data"><a target="_blank" href="'.$url_forecast.'">' . $string_date . '</a></td>';

        //stampo tutti i dati
        $result_array = get_object_vars($list_of_result[$time]);

        foreach($fields_position as $field_name){
          $field_name = preg_replace('/\s+/', '', $field_name);
          $field_name_underscore = str_replace('-','_', $field_name);

          if(isset(${$field_name_underscore.'_setted'}) && ${$field_name_underscore.'_setted'}) {

            //gestione icona
            if($field_name == 'icon'){
              $pos = strpos($result_array[$field_name], '_night');
              if ($pos) {
                $value = str_replace('_night', '', $result_array[$field_name]);
              }
              $markup .= '<td class="data"><img src="' . $path_publich . $result_array[$field_name] . '" width="16&" height="16&" alt="' . $result_array[$field_name] . '" title="' . $result_array[$field_name] . '"></td>';

            } else {
              // caso generale
              $unit = isset($all_fields->{$field_name}->unit) ? $all_fields->{$field_name}->unit : '' ;
              $markup .= '<td class="data">' . $result_array[$field_name] . ' ' . $unit . '</td>';
            }
          }
        }

      //}
      $markup .= '</tr>';
    }
    $markup .= '
        </tbody>
    </table>
    <div class="meteo.ink">  <a href="http://meteo.uniparthenope.it" target="_blank" title="Meteo">    CCMMMA: http://meteo.uniparthenope.it  </a>  <br>  ©2013  <a href="http://meteo.uniparthenope.it/" title="Meteo siti web" target="_blank">    <b>meteo.uniparthenope.it</b> - <b>CCMMMA</b> Università Parthenope  </a>  </div></div>';


    return $markup;

  }

  public function GetTimestampFromDate($string){
    $days = [
      'Domenica',
      'Lunedì',
      'Martedì',
      'Mercoledì',
      'Giovedì',
      'Venerdì',
      'Sabato',
    ];
    $arr = explode("Z", $string, 2);
    $date = $arr[0];
    $dtime = DrupalDateTime::createFromFormat("Ymd", "$date");
    $timestamp = $dtime->getTimestamp();
    $data_string = $days[date('w', $timestamp)] . ' ' . date('d', $timestamp);
    return $data_string;
  }

}