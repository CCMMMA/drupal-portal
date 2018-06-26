<?php

namespace Drupal\home\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a 'Hello' Block
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
    $markup .= '    <div id="box">  <div class="title">Meteo Comune di Napoli    <a href="http://meteo.uniparthenope.it" target="_blank" title="meteo.uniparthenope.it">    </a>  </div><div id="oBox_loading" style="display: none;">    <img src="/sites/default/files/animated_progress_bar.gif" width="400"></div>';


    // gestisco i campi da visualizzare
    foreach($fields as $field => $display){
      $field_name = str_replace("-","_",$field);
      if($field == $display && $display !== 0){
        ${$field_name.'_setted'} = TRUE;
      } else{
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
      if(isset(${$field_name .'_setted'}) && ${$field_name .'_setted'}){
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
          if(isset(${$field_name.'_setted'}) && ${$field_name.'_setted'}) {

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

/*

        foreach($result_array as $field_name => $value){
          if(isset(${$field_name.'_setted'}) && ${$field_name.'_setted'}) {
            //gestione icona
            if($field_name == 'icon'){
              $pos = strpos($value, '_night');
              if ($pos) {
                $value = str_replace('_night', '', $value);
              }
              $markup .= '<td class="data"><img src="' . $path_publich . $value . '" width="16&" height="16&" alt="' . $value . '" title="' . $value . '"></td>';

            } else {
              $markup .= '<td class="data">' . $value . ' ' . $all_fields->{$field_name}->unit . '</td>';
            }
          }
        }
*/
      }
      $markup .= '</tr>';
    }
    $markup .= '
        </tbody>
    </table>
    <div class="meteo.ink">  <a href="http://meteo.uniparthenope.it" target="_blank" title="Meteo">    CCMMMA: http://meteo.uniparthenope.it  </a>  <br>  ©2013  <a href="http://meteo.uniparthenope.it/" title="Meteo siti web" target="_blank">    <b>meteo.uniparthenope.it</b> - <b>CCMMMA</b> Università Parthenope  </a>  </div></div>';


      /**
     *
     *
     * ###########################
     *
     *
     */

/*
    foreach ($list_of_day as $time => $string) {
      //dpm($time);
      if (isset($list_of_result[$time])) {
        $result_array = get_object_vars($list_of_result[$time]);
        //dpm($result_array);
        if (isset($result_array['icon']) && isset($icon_setted) && $icon_setted) {
          $data[$string]['icon'] = $result_array['icon'];
          $pos = strpos($data[$string]['icon'], '_night');
          if ($pos) {
            $data[$string]['icon'] = str_replace('_night', '', $data[$string]['icon']);
          }
        }
        else {
          $data[$string]['icon'] = '';
        }
        if (isset($result_array['text'])) {
          $data[$string]['text'] = $result_array['text'];
        }
        else {
          $data[$string]['text'] = '';
        }
        if (isset($result_array['winds'])) {
          $data[$string]['winds'] = $result_array['winds'];
        }
        else {
          $data[$string]['winds'] = '';
        }
        if (isset($result_array['wd10']) && isset($wd10_setted) && $wd10_setted) {
          $data[$string]['wd10'] = round($result_array['wd10'], 0);
        }
        else {
          $data[$string]['wd10'] = '';
        }
        if (isset($result_array['ws10-max']) && isset($ws10_max_setted) && $ws10_max_setted) {
          $data[$string]['ws10-max'] = $result_array['ws10-max'];
        }
        else {
          $data[$string]['ws10-max'] = '';
        }
        if (isset($result_array['ws10-min']) && isset($ws10_min_setted) && $ws10_min_setted) {
          $data[$string]['ws10-min'] = $result_array['ws10-min'];
        }
        else {
          $data[$string]['ws10-min'] = '';
        }
        if ($data[$string]['ws10-min'] == '' && $data[$string]['ws10-max'] == '') {
          if (isset($result_array['ws10']) && isset($ws10_setted) && $ws10_setted) {
            $data[$string]['ws10'] = $result_array['ws10'];
          }
          else {
            $data[$string]['ws10'] = '';
          }
        }
        else {
          $data[$string]['ws10'] = round((($data[$string]['ws10-max'] + $data[$string]['ws10-min']) / 2) * 1.9, 0); //conversione in nodi
        }

        if (isset($result_array['crh']) && isset($crh_setted) && $crh_setted) {
          $data[$string]['crh'] = round($result_array['crh'], 1);
        }
        else {
          $data[$string]['crh'] = '';
        }
        if (isset($result_array['t2c-min']) && isset($t2c_min_setted) && $t2c_min_setted) {
          $data[$string]['t2c-min'] = round($result_array['t2c-min'], 0);
        }
        else {
          $data[$string]['t2c-min'] = '';
        }
        if (isset($result_array['t2c-max']) && isset($t2c_max_setted) && $t2c_max_setted) {
          $data[$string]['t2c-max'] = round($result_array['t2c-max'], 0);
        }
        else {
          $data[$string]['t2c-max'] = '';
        }
        if (isset($result_array['t2c']) && isset($t2c_setted) && $t2c_setted) {
          $data[$string]['t2c'] = round($result_array['t2c'], 0);
        }
        else {
          $data[$string]['t2c'] = '';
        }


        //caso in cui ho solo una temperatura
        if ($data[$string]['t2c-min'] == '' || $data[$string]['t2c-max'] == '') {
          $single_temp_value = 1;
        }
        else {
          $single_temp_value = 0;
        }
        //caso in cui ho solo una temperatura
        if ($data[$string]['ws10-min'] == '' || $data[$string]['ws10-min'] == '') {
          $single_wind_value = 1;
        }
        else {
          $single_wind_value = 0;
        }
      }
    }

    $markup .= '
      <table id="oBox_table" width="100%" cellspacing="0" cellpadding="2" border="0" style="">
        <tbody>
          <tr class="legenda">
            <td width="25%" colspan="2">Previsione</td>';

    if($winds_setted){
      $markup .= '<td width="18%">Vento</td>';
    }

    if($single_temp_value) {
      if($t2c_setted) {
        $markup .= '<td width="18%" class="tMin">Temp</td>';
      }
    } else{
      if($t2c_min_setted) {
        $markup .= '<td width="9%" class="tMin">T&nbsp;min</td>';
      }
      if($t2c_max_setted){
        $markup .= '<td width="9%" class="tMax">T&nbsp;max</td>';
      }
    }

    if($single_wind_value){
      if($ws10_setted) {
        $markup .= '<td width="18%" class="tMin">Vento</td>';
      }
    } else{
      if($ws10_min_setted) {
        $markup .= '<td width="9%" class="tMin">V&nbsp;min</td>';
      }
      if($ws10_max_setted){
        $markup .= '<td width="9%" class="tMax">V&nbsp;max</td>';
      }
    }

    if($crh_setted) {
      $markup .= '<td width="14%">Pioggia</td>';
    }
    $markup .= '</tr>';

    //creazione parte dinamica della tabella
    foreach($data as $string => $value){

      //parte statica
      $markup .= '<tr><td class="data">' . $string . '</td>';

      if($icon_setted){
        $markup .= '<td class="data"><img src="' . $path_publich . $value['icon'] . '" width="16&" height="16&" alt="' . $value['text'] . '" title="' . $value['text'] . '"></td>';
      }

      if($winds_setted){
        $markup .= '<td class="data">' . $value['winds'] .'</td>';
      }

      //parte dinamica
      if($single_temp_value){
        if($t2c_setted) {
          $markup .= '<td class="data tmin">' . $value['t2c'] . '°C</td>';
        }
      } else{
        if($t2c_min_setted) {
          $markup .= '<td class="data tmin">' . $value['t2c-min'] . '°C</td> ';
        }
        if($t2c_max_setted){
          $markup .= '<td class="data tmax">' . $value['t2c-max'] . '°C</td> ';
        }
      }
*/
      /*
      <td class="data">' . $value['vento_type'] . '</td>
      */
/*
      if($single_wind_value){
        if($ws10_setted) {
          $markup .= '<td class="data tmin">' . $value['wd10'] . '°</td>';
        }
      } else{
        if($ws10_min_setted) {
          $markup .= '<td class="data tmin">' . $value['ws10-min'] . 'knt</td>';
        }
        if($ws10_max_setted) {
          $markup .= '<td class="data tmax">' . $value['ws10-max'] . 'knt</td>';
        }
      }

      //parte statica
      if($crh_setted) {
        $markup .= '<td class="data">' . $value['crh'] . ' mm</td> ';
      }

    }
    //chiusura della tabella
    $markup .= '
        </tbody>
    </table>
    <div class="meteo.ink">  <a href="http://meteo.uniparthenope.it" target="_blank" title="Meteo">    CCMMMA: http://meteo.uniparthenope.it  </a>  <br>  ©2013  <a href="http://meteo.uniparthenope.it/" title="Meteo siti web" target="_blank">    <b>meteo.uniparthenope.it</b> - <b>CCMMMA</b> Università Parthenope  </a>  </div></div>';
*/
    return [
      '#markup' => \Drupal\Core\Render\Markup::create($markup),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}

