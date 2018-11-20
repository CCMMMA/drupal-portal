<?php

namespace Drupal\home\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Hello' Block
 *
 * @Block(
 *   id = "table_block",
 *   admin_label = @Translation("Table block"),
 * )
 */
class tableBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $api = \Drupal::config('api.settings')->get('api');
    /*
    $url_wrf_golfo_napoli = $api.'/products/wrf3/forecast/ca001/map';
    $url_ww3_golfo_napoli = $api.'/products/ww33/forecast/ca001/map';
    $url_chimere_golfo_napoli = $api.'/products/chm3/forecast/ca001/map?output=gen';
    
    
    $client = \Drupal::httpClient();
    $request = $client->get($url_wrf_golfo_napoli);
    $response = json_decode($request->getBody());
    $url_img_wrf = $response->map->link;
    
    $client1 = \Drupal::httpClient();
    $request = $client1->get($url_ww3_golfo_napoli);
    $response = json_decode($request->getBody());
    $url_img_ww3 = $response->map->link;
    
    $client2 = \Drupal::httpClient();
    $request = $client2->get($url_chimere_golfo_napoli);
    $response = json_decode($request->getBody());
    $url_img_chm = $response->map->link;
    
  
    $markup = '
    <div class="type-of-place"><!--<p class="label-search">Type of place:</p>--><!--<div class="scelta-singola selected" data="all"><p>ALL</p></div>-->
      <div class="scelta-singola" data="ca">
        <p>CA</p>
      </div>
      
      <div class="scelta-singola selected" data="com">
        <p>COM</p>
      </div>
      
      <div class="scelta-singola" data="IIM">
        <p>IIM</p>
      </div>
      
      <div class="scelta-singola" data="poi">
        <p>POI</p>
      </div>
      
      <div class="scelta-singola" data="VET">
        <p>VET</p>
      </div>
      
      <div class="scelta-singola" data="euro">
        <p>EURO</p>
      </div>
      
      <div class="scelta-singola" data="porti">
        <p>PORTI</p>
      </div>
      <!--<div class="scelta-singola" data="other"><p>OTHER</p></div>--></div>
      
      <div class="mapid" id="mapid-com">&nbsp;</div>
      
      <div class="row" style="margin-top: 16px;">
      <div class="col-md-4 wrf">
      <div style="border: 1px solid black; margin-top:2px; text-align:center;">
      <div class="wrf-title style_title">WRF</div>
      
      <div class="img-box"><img id="imgfor" src="'.$url_img_wrf.'" /></div>
      </div>
      </div>
      
      <div class="col-md-4 ww3">
      <div style="border: 1px solid black; margin-top:2px;  text-align:center;">
      <div class="ww3-title style_title">WW3</div>
      
      <div class="img-box"><img id="imgfor" src="'.$url_img_ww3.'" /></div>
      </div>
      </div>
      
      <div class="col-md-4 chimere">
      <div style="border: 1px solid black; margin-top:2px;  text-align:center;">
      <div class="chimere-title style_title">CHIMERE</div>
      
      <div class="img-box"><img id="imgfor" src="'.$url_img_chm.'" />
       <!--<img id="bar_right" src="http://blackjeans.uniparthenope.it/prods/getbar.php?model=chm3&amp;position=v&amp;output=caqi" />-->
      </div>
      </div>
      </div>
    </div>
  ';
  */
    
    return array(
      '#markup' => \Drupal\Core\Render\Markup::create($markup),
    );
  }
}
