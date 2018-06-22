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

    //get place
    $place_nid = \Drupal::config('bollettino.settings')->get('place');
    $node_place = entity_load('node', $place_nid);

    //get colum list
    $colums_lists = \Drupal::config('bollettino.settings')->get('active_colums');
    //dpm($colums_lists);

    //@todo ottenere il place id e settarlo come default



    \Drupal::service('page_cache_kill_switch')->trigger();
    //$build['#cache']['max-age'] = 0;
    $api = \Drupal::config('api.settings')->get('api');
    $url_napoli = $api . '/products/wrf5/timeseries/com63049?step=24&date=' . date('Ymd\Z\0\0', time());


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

    $list_of_day[date('Ymd\Z\0\000', time())] = $days[date('w', time())] . ' ' . date('d', time());//oggi
    $i = 1;
    for ($i; $i <= 7; $i++) {
      //$list_of_day[gmdate('Ymd\Zhi', time()+(86400*$i))] = gmdate('w', time()+(86400*$i));
      $list_of_day[date('Ymd\Z\0\000', time() + (86400 * $i))] = $days[date('w', time() + (86400 * $i))] . ' ' . date('d', time() + (86400 * $i)); //oggi
    }
    //dpm($list_of_day);
    //get result of api
    $client = new \GuzzleHttp\Client();
    try {
      $request = $client->get($url_napoli, ['http_errors' => FALSE]);
      //WRF($url_napoli);
      //dpm($list_of_day);
      $response = json_decode($request->getBody());
      if (isset($response->timeseries)) {
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
    global $base_url;
    $path_publich = $base_url . '/sites/all/themes/zircon_custom/js/images/';

    $markup = '';

    $data = [];

    //dpm($list_of_result);
    //dpm($list_of_day);

    $markup .= '    <div id="box">  <div class="title">Meteo Comune di Napoli    <a href="http://meteo.uniparthenope.it" target="_blank" title="meteo.uniparthenope.it">    </a>  </div><div id="oBox_loading" style="display: none;">    <img src="/sites/default/files/animated_progress_bar.gif" width="400"></div>';


    $icon_setted = (in_array('icon', $colums_lists) && $colums_lists['icon'] === 'icon') ? true : false;
    $wd10_setted = (in_array('wd10', $colums_lists) && $colums_lists['wd10'] === 'wd10') ? true : false;
    $ws10_max_setted = (in_array('ws10-max', $colums_lists) && $colums_lists['ws10-max'] === 'ws10-max') ? true : false;
    $ws10_min_setted = (in_array('ws10-min', $colums_lists) && $colums_lists['ws10-min'] === 'ws10-min') ? true : false;
    $ws10_setted = (in_array('ws10', $colums_lists) && $colums_lists['ws10'] === 'ws10') ? true : false;
    $crh_setted = (in_array('crh', $colums_lists) && $colums_lists['crh'] === 'crh') ? true : false;
    $t2c_min_setted = (in_array('t2c-min', $colums_lists) && $colums_lists['t2c-min'] === 't2c-min') ? true : false;
    $t2c_max_setted = (in_array('t2c-max', $colums_lists) && $colums_lists['t2c-max'] === 't2c-max') ? true : false;
    $t2c_setted = (in_array('t2c', $colums_lists) && $colums_lists['t2c'] === 't2c') ? true : false;


    foreach ($list_of_day as $time => $string) {
      //dpm($time);
      if (isset($list_of_result[$time])) {
        $result_array = get_object_vars($list_of_result[$time]);
        //dpm($result_array);
        if (isset($result_array['icon']) && $icon_setted) {
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
          $data[$string]['meteo_text'] = $result_array['text'];
        }
        else {
          $data[$string]['meteo_text'] = '';
        }
        if (isset($result_array['wd10']) && $wd10_setted) {
          $data[$string]['vento_type'] = round($result_array['wd10'], 0);
        }
        else {
          $data[$string]['vento_type'] = '';
        }
        if (isset($result_array['ws10-max']) && $ws10_max_setted) {
          $data[$string]['vento_max'] = $result_array['ws10-max'];
        }
        else {
          $data[$string]['vento_max'] = '';
        }
        if (isset($result_array['ws10-min']) && $ws10_min_setted) {
          $data[$string]['vento_min'] = $result_array['ws10-min'];
        }
        else {
          $data[$string]['vento_min'] = '';
        }
        if ($data[$string]['vento_min'] == '' && $data[$string]['vento_max'] == '') {
          if (isset($result_array['ws10']) && $ws10_setted) {
            $data[$string]['vento'] = $result_array['ws10'];
          }
          else {
            $data[$string]['vento'] = '';
          }
        }
        else {
          $data[$string]['vento'] = round((($data[$string]['vento_max'] + $data[$string]['vento_min']) / 2) * 1.9, 0); //conversione in nodi
        }

        if (isset($result_array['crh']) && $crh_setted) {
          $data[$string]['pioggia'] = round($result_array['crh'], 1);
        }
        else {
          $data[$string]['pioggia'] = '';
        }
        if (isset($result_array['t2c-min']) && $t2c_min_setted) {
          $data[$string]['temp_min'] = round($result_array['t2c-min'], 0);
        }
        else {
          $data[$string]['temp_min'] = '';
        }
        if (isset($result_array['t2c-max']) && $t2c_max_setted) {
          $data[$string]['temp_max'] = round($result_array['t2c-max'], 0);
        }
        else {
          $data[$string]['temp_max'] = '';
        }
        if (isset($result_array['t2c']) && $t2c_setted) {
          $data[$string]['temp'] = round($result_array['t2c'], 0);
        }
        else {
          $data[$string]['temp'] = '';
        }


        //caso in cui ho solo una temperatura
        if ($data[$string]['temp_min'] == '' || $data[$string]['temp_max'] == '') {
          $single_temp_value = 1;
        }
        else {
          $single_temp_value = 0;
        }
        //caso in cui ho solo una temperatura
        if ($data[$string]['vento_min'] == '' || $data[$string]['vento_max'] == '') {
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
        $markup .= '<td class="data"><img src="' . $path_publich . $value['icon'] . '" width="16&" height="16&" alt="' . $value['meteo_text'] . '" title="' . $value['meteo_text'] . '"></td>';
      }

      //parte dinamica
      if($single_temp_value){
        if($t2c_setted) {
          $markup .= '<td class="data tmin">' . $value['temp'] . '°C</td>';
        }
      } else{
        if($t2c_min_setted) {
          $markup .= '<td class="data tmin">' . $value['temp_min'] . '°C</td> ';
        }
        if($t2c_max_setted){
          $markup .= '<td class="data tmax">' . $value['temp_max'] . '°C</td> ';
        }
      }

      /*
      <td class="data">' . $value['vento_type'] . '</td>
      */

      if($single_wind_value){
        if($ws10_setted) {
          $markup .= '<td class="data tmin">' . $value['vento_type'] . '°</td>';
        }
      } else{
        if($ws10_min_setted) {
          $markup .= '<td class="data tmin">' . $value['vento_min'] . 'knt</td>';
        }
        if($ws10_max_setted) {
          $markup .= '<td class="data tmax">' . $value['vento_max'] . 'knt</td>';
        }
      }

      //parte statica
      if($crh_setted) {
        $markup .= '<td class="data">' . $value['pioggia'] . ' mm</td> ';
      }

    }
    //chiusura della tabella
    $markup .= '
        </tbody>
    </table>
    <div class="meteo.ink">  <a href="http://meteo.uniparthenope.it" target="_blank" title="Meteo">    CCMMMA: http://meteo.uniparthenope.it  </a>  <br>  ©2013  <a href="http://meteo.uniparthenope.it/" title="Meteo siti web" target="_blank">    <b>meteo.uniparthenope.it</b> - <b>CCMMMA</b> Università Parthenope  </a>  </div></div>';

    return [
      '#markup' => \Drupal\Core\Render\Markup::create($markup),
      '#cache' => [
        'max-age' => 0,
      ],
      //TODO add cache manage
    ];
  }

}

