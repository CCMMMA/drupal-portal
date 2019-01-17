<?php
  
  

namespace Drupal\home\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a 'Hello' Block
 *
 * @Block(
 *   id = "map_block",
 *   admin_label = @Translation("Map block"),
 * )
 */
class mapBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $api = \Drupal::config('api.settings')->get('api');
    $url_wrf_golfo_napoli = $api.'/products/wrf5/forecast/reg15/plot';
    //$url_ww3_golfo_napoli = $api.'/products/ww33/forecast/ca001/map';
    $url_rms3_golfo_napoli = $api.'/products/rms3/forecast/reg15/plot';
    //$url_chimere_golfo_napoli = $api.'/products/chm3/forecast/ca001/map?output=gen';
    $date_strtotime = time();
    $current_minutes = date("M");
    $utc = date("H")-1;
    $date = date('Ymd\Z', time()).$utc.floor($current_minutes/10)*10;
    $date_strtotime = strtotime($date);
    $date_form = date("Y-m-d", $date_strtotime); //Y-m-d
    $date_for_api = date('Ymd\Z', strtotime($date_form)).sprintf("%02d",$utc).sprintf("%02d",floor($current_minutes/10)*10);
    $url_radar_golfo_napoli = $api.'/products/rdr1/forecast/reg15/plot?output=gen&date='.$date_for_api;
    
    $client = new \GuzzleHttp\Client();
        try{
	    $request = $client->get($url_wrf_golfo_napoli, ['http_errors' => false]);
	    $response = json_decode($request->getBody());
	    if(isset($response->map->link)){
	    	$url_img_wrf = $response->map->link;
	    } else {	    
		    $url_img_wrf = '';
	    }
    } catch(RequestException $e){
	    $url_img_wrf = '';
    }
    
    $client1 = new \GuzzleHttp\Client();
    try{
    $request = $client1->get($url_rms3_golfo_napoli, ['http_errors' => false]);
    $response = json_decode($request->getBody());
    	if(isset($response->map->link)){
	    	$url_img_ww3 = $response->map->link;
	   	} else {	    
		   $url_img_ww3 = '';
	   	}
    } catch(RequestException $e){
      	$url_img_ww3 = '';
	}

    
    $client2 = new \GuzzleHttp\Client();
    try{
    $request = $client2->get($url_radar_golfo_napoli, ['http_errors' => false]);
    $response = json_decode($request->getBody());
    	if(isset($response->map->link)){
	    	$url_img_chm = $response->map->link;
	   	} else {	    
		   $url_img_chm = '';
	   	}
    } catch(RequestException $e){
		$url_img_chm = '';
	}

    //elenco di date disponibili
    $markup = '';
    
    $markup .= '
      <div class="scelte">
    ';
    
    //scelta data
    $list_of_day = array();
    $list_of_result = array();
    $days = array('Domenica', 'Lunedì', 'Martedì', 'Mercoledì','Giovedì','Venerdì', 'Sabato');
    $list_of_day[date('Ymd\Z\0\0', time())] = $days[date('w', time())].' '.date('d', time());//oggi
    $i = 1;
    for($i; $i<=6; $i++){
      //$list_of_day[gmdate('Ymd\Zhi', time()+(86400*$i))] = gmdate('w', time()+(86400*$i));
      $list_of_day[date('Ymd\Z\0\0', time()+(86400*$i))] = $days[date('w', time()+(86400*$i))].' '.date('d', time()+(86400*$i));//oggi
    }
    $first_loop = true;
    foreach($list_of_day as $data_value => $day){
      if($first_loop){
        $selected = 'selected';
        $first_loop = false;
      }
      else{
        $selected = '';
      }
      $markup .= '
      <div class="scelta-singola '.$selected.'" data="'.$data_value.'">
        <p>'.$day.'</p>
      </div>';
    }
    //select hour
    $current_hour = date('H');
    $markup .= '
      <div class="select-hour">
        <div class="dec button"><</div>
        <input type="number" name="hour-selected" id="hour-selected" disabled min="0" max="23" value="'.$current_hour.'">
        <div class="inc button">></div>
      </div>
    ';
    
    //select type of map
    //end scelte
    $markup .= '</div>';
    $markup .='
    
    <div class="mapid" id="mapid-com">&nbsp;</div>';
    
    $markup .= ' 
    <i class="fa fa-spinner fa-spin" style="font-size:24px"></i>   
      <select class="selectpicker">
        <option>CA</option>
        <option selected="selected">COM</option>
        <option>IIM</option>
        <option>POI</option>
        <option>VET</option>
        <option>EURO</option>
        <option>PORTI</option>
      </select>'; 
  
    $markup .= '
    
    <div class="row" style="margin-top: 16px;">
    <div class="col-md-4 wrf">
    <div style="border: 1px solid black; margin-top:2px; text-align:center;">
    <div class="wrf-title style_title"><a class="attendi" title="go to weather forecast..." href="/forecast/forecast?product=wrf5&place=ca000&mappa=technical">Meteo</a></div>
    
    <div class="img-box"><a class="attendi"  title="go to weather forecast..." href="/forecast/forecast?product=wrf5&place=ca000"><img id="imgfor" src="'.$url_img_wrf.'" /></a></div>
    </div>
    </div>
    
    <div class="col-md-4 ww3">
    <div style="border: 1px solid black; margin-top:2px;  text-align:center;">
    <div class="ww3-title style_title"><a class="attendi" title="go to sea forecast..." href="/forecast/forecast?product=rms3&place=ca000&mappa=technical">Sea</a></div>
    
    <div class="img-box"><a class="attendi" title="go to sea forecast..." href="/forecast/forecast?product=rms3&place=ca000"><img id="imgfor" src="'.$url_img_ww3.'" /></a></div>
    </div>
    </div>
    
    <div class="col-md-4 chimere">
    <div style="border: 1px solid black; margin-top:2px;  text-align:center;">
    <div class="chimere-title style_title"><a class="attendi" title="go to radar chart..." href="/instruments/radar-form?product=rdr1&place=ca000&mappa=technical">Radar</a></div>
    
    <div class="img-box"><a class="attendi" title="go to radar chart..." href="/instruments/radar-form?product=rdr1&place=ca000"><img id="imgfor" src="'.$url_img_chm.'" /></a>
     <!--<img id="bar_right" src="http://blackjeans.uniparthenope.it/prods/getbar.php?model=chm3&amp;position=v&amp;output=caqi" />-->
    </div>
    </div>
    </div>
    </div>
    ';
  
    return array(
      '#markup' => \Drupal\Core\Render\Markup::create($markup),
      '#cache' => array(
        'max-age' => 0,
      ),
    );
  }
}
