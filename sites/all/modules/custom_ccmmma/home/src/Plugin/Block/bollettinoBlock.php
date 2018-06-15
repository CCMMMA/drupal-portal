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
    
    \Drupal::service('page_cache_kill_switch')->trigger();
    //$build['#cache']['max-age'] = 0;
    $api = \Drupal::config('api.settings')->get('api');
    $url_napoli = $api.'/products/wrf5/timeseries/com63049?step=24&date='.date('Ymd\Z\0\0', time());
    
    //creo un array con 6 date a partire da oggi
    $list_of_day = array();
    $list_of_result = array();
    $days = array('Domenica', 'Lunedì', 'Martedì', 'Mercoledì','Giovedì','Venerdì', 'Sabato');
    
    $list_of_day[date('Ymd\Z\0\000', time())] = $days[date('w', time())].' '.date('d', time());//oggi
    $i = 1;
    for($i; $i<=7; $i++){
      //$list_of_day[gmdate('Ymd\Zhi', time()+(86400*$i))] = gmdate('w', time()+(86400*$i));
      $list_of_day[date('Ymd\Z\0\000', time()+(86400*$i))] = $days[date('w', time()+(86400*$i))].' '.date('d', time()+(86400*$i)); //oggi
    }
    //dpm($list_of_day);
    //get result of api
    $client = new \GuzzleHttp\Client();
    try{
	    $request = $client->get($url_napoli, ['http_errors' => false]);
	    //dpm($url_napoli);
	    //dpm($list_of_day);
	    $response = json_decode($request->getBody());
	    if(isset($response->timeseries)){
		    foreach($response->timeseries as $single_value){
				if(array_key_exists($single_value->dateTime, $list_of_day)){
					$list_of_result[$single_value->dateTime] = $single_value;
		  		}
			}
    	}
    } catch(RequestException $e){
	    $list_of_result = [];
    }


    
    
    //dpm($list_of_result);
    global $base_url;
    $path_publich = $base_url.'/sites/all/themes/zircon_custom/js/images/';
    
    $markup = '';
   
    //dpm($list_of_result);
    //dpm($list_of_day);
    foreach($list_of_day as $time => $string){
      //dpm($time);
      if(isset($list_of_result[$time])){
        $result_array = get_object_vars($list_of_result[$time]);
        //dpm($result_array);
        if(isset($result_array['icon'])){
          $icon = $result_array['icon'];
          $pos = strpos($icon, '_night');
          if($pos){
            $icon = str_replace('_night', '', $icon);
          }
        } else{
          $icon = '';
        }
        if(isset($result_array['icon'])){
          $meteo_text = $result_array['text'];
        } else{
          $meteo_text = '';
        }
        
        if(isset($result_array['wd10'])){
          $vento_type = $result_array['wd10']->value;
        } else{
          $vento_type = '';
        } 
        if(isset($result_array['ws10-max'])){
          $vento_max = $result_array['ws10-max']->value;
        } else{
          $vento_max = 0;
        }
        if(isset($result_array['ws10-min'])){
          $vento_min = $result_array['ws10-min']->value;
        } else{
          $vento_min = 0;
        }
        if($vento_max == 0 && $vento_min == 0){
	        if(isset($result_array['ws10'])){
				$vento = $result_array['ws10']->value;
        	} else{
				$vento = 0;
        	}
        } else{
        	$vento = round((($vento_max + $vento_min) /2) * 1.9, 0); //conversione in nodi
        }
        
        if(isset($result_array['crh'])){
          $pioggia = round($result_array['crh']->value, 1);
        } else{
          $pioggia = 0;
        }
        if(isset($result_array['t2c-min'])){
          $temp_min = round($result_array['t2c-min']->value, 0);
        } else{
          $temp_min = 0;
        }
        if(isset($result_array['t2c-max'])){
          $temp_max = round($result_array['t2c-max']->value, 0);
        } else{
          $temp_max = 0;
        }
        if(isset($result_array['t2c'])){
          $temp = round($result_array['t2c']->value, 0);
        } else{
          $temp = 0;
        }

        //caso in cui ho solo una temperatura
        if($temp_min == 0 && $temp_max == 0){
	        $markup .=' 
    
    <div id="box">  <div class="title">Meteo Comune di Napoli    <a href="http://meteo.uniparthenope.it" target="_blank" title="meteo.uniparthenope.it">    </a>  </div><div id="oBox_loading" style="display: none;">    <img src="http://meteo.uniparthenope.it/sites/default/files/animated_progress_bar.gif" width="400"></div>

    <table id="oBox_table" width="100%" cellspacing="0" cellpadding="2" border="0" style="">

        <tbody>
          <tr class="legenda">
            <td width="24%" colspan="2">Previsione</td>
            <td width="18%" class="tMin">Temp</td>
            <td width="21%" colspan="2">Vento</td>
            <td width="14%">Pioggia</td>
          </tr>';

	        $markup .= '
				<tr>
					<td class="data">'.$string.'</td><td class="data"><img src="'.$path_publich.$icon.'" width="16&" height="16&" alt="'.$meteo_text.'" title="'.$meteo_text.'"></td>  <td class="data tmin">'.$temp.'°C</td><td class="data">'.$vento_type.'</td>  <td class="data">'.$vento.' knt</td><td class="data">'.$pioggia.' mm</td> 
				</tr>';
        } else{
	        
	        $markup .=' 
    
    <div id="box">  <div class="title">Meteo Comune di Napoli    <a href="http://meteo.uniparthenope.it" target="_blank" title="meteo.uniparthenope.it">    </a>  </div><div id="oBox_loading" style="display: none;">    <img src="http://meteo.uniparthenope.it/sites/default/files/animated_progress_bar.gif" width="400"></div>

    <table id="oBox_table" width="100%" cellspacing="0" cellpadding="2" border="0" style="">

        <tbody>
          <tr class="legenda">
            <td width="24%" colspan="2">Previsione</td>
            <td width="9%" class="tMin">T&nbsp;min</td>
            <td width="9%" class="tMax">T&nbsp;max</td>
            <td width="21%" colspan="2">Vento</td>
            <td width="14%">Pioggia</td>
          </tr>';

        
        $markup .= '
        <tr>
          <td class="data">'.$string.'</td><td class="data"><img src="'.$path_publich.$icon.'" width="16&" height="16&" alt="'.$meteo_text.'" title="'.$meteo_text.'"></td>  <td class="data tmin">'.$temp_min.'°C</td>  <td class="data tmax">'.$temp_max.'°C</td>  <td class="data">'.$vento_type.'</td>  <td class="data">'.$vento.' knt</td><td class="data">'.$pioggia.' mm</td> 
        </tr>
        ';
      }
    }  
    }     
      $markup .= '
        </tbody>
    </table>
    <div class="meteo.ink">  <a href="http://meteo.uniparthenope.it" target="_blank" title="Meteo">    CCMMMA: http://meteo.uniparthenope.it  </a>  <br>  ©2013  <a href="http://meteo.uniparthenope.it/" title="Meteo siti web" target="_blank">    <b>meteo.uniparthenope.it</b> - <b>CCMMMA</b> Università Parthenope  </a>  </div></div>';

    return array(
      '#markup' => \Drupal\Core\Render\Markup::create($markup),
      '#cache' => array(
        'max-age' => 0,
      ),
      //TODO add cache manage
    );
  }

}

