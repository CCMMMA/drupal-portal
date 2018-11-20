<?php

namespace Drupal\home\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 *
 * @Block(
 *   id = "bollettino_meteo_block",
 *   admin_label = @Translation("Bollettino meteo block"),
 * )
 */
class bollettinoBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    //servizio per ottenere products and fields
    $bollettino_service = \Drupal::service('home.BollettinoServices');

    //get place
    $place_nid = \Drupal::config('bollettino.settings')->get('place');
    $node_place = entity_load('node', $place_nid);
    if(isset($node_place) && !empty($node_place)){
      $id_place = $node_place->get('field_id_place')->getValue()[0]['value'];
    } else{
      $id_place = 'com63049';
    }

    //get prod
    $prod = \Drupal::config('bollettino.settings')->get('product');

    //get fields to display
    $fields = \Drupal::config('bollettino.settings')->get('fields');

    //get fields position
    $fields_position = \Drupal::config('bollettino.settings')->get('fields_position');
    $fields_position = explode("\n", $fields_position);


    //get step
    $step = \Drupal::config('bollettino.settings')->get('step');

    //manage cache
    \Drupal::service('page_cache_kill_switch')->trigger();

    // get host of api
    $api = \Drupal::config('api.settings')->get('api');

    // create url api
    $url_timeseries = $api . '/products/'.$prod.'/timeseries/'.$id_place.'?step='.$step.'&date=' . date('Ymd\Z\0\0', time());


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
          if (array_key_exists($single_value->dateTime, $list_of_day)) {
            $list_of_result[$single_value->dateTime] = $single_value;
          }
        }
      }
    } catch (RequestException $e) {
      $list_of_result = [];
    }

    //dpm($list_of_result);


    // ottengo il base_path per visualizzare le immagini
    global $base_url;
    $path_publich = $base_url . '/sites/all/themes/zircon_custom/js/images/';


    $markup = '';
    $data = [];

    // intestazione tabella
    $markup .= '    <div id="box">  <div class="title">Meteo Comune di Napoli    <a href="https://meteo.uniparthenope.it" target="_blank" title="meteo.uniparthenope.it">    </a>  </div>';

    // gestisco i campi da visualizzare
    foreach($fields as $field => $display){
      if($field == $display && $display !== 0){
        $field_name = str_replace("-","_",$field);
        ${$field_name.'_setted'} = TRUE;
      } else{
        $field_name = str_replace("-","_",$field);
        ${$field_name.'_setted'} = FALSE;
      }
    }


    $bollettino_service->SetAllFields($prod);
    $all_fields = $bollettino_service->GetAllFields();


    //creazione header della tabella
    $markup .= '
      <table id="oBox_table" width="100%" cellspacing="0" cellpadding="2" border="0" style="">
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

/*
    foreach($all_fields as $field_name => $value){
      if(isset(${$field_name .'_setted'}) && ${$field_name .'_setted'}){
        $markup .= '<td>'.$value->title->it.'</td>';
      }
    }
*/
    $markup .= '</tr>';


    foreach ($list_of_day as $time => $string_date) {
      if (isset($list_of_result[$time])) {
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

      }
      $markup .= '</tr>';
    }
    $markup .= '
        </tbody>
    </table>
    <div class="meteo.ink">  <a href="https://meteo.uniparthenope.it" target="_blank" title="Meteo">    CCMMMA: https://meteo.uniparthenope.it  </a>  <br>  ©2013  <a href="https://meteo.uniparthenope.it/" title="Meteo siti web" target="_blank">    <b>meteo.uniparthenope.it</b> - <b>CCMMMA</b> Università Parthenope  </a>  </div></div>';



    return [
      '#markup' => \Drupal\Core\Render\Markup::create($markup),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}

