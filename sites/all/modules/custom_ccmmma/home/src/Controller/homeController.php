<?php
namespace Drupal\home\Controller;
 
use Drupal\Core\Controller\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;

class homeController extends ControllerBase {
  public function content() {
/*    $markup = '<div class="type-of-place"> 
                <p class="label-search">Type of place: </p>
                <!--<div class="scelta-singola selected" data="all"><p>ALL</p></div>-->
                <div class="scelta-singola" data="ca"><p>CA</p></div>
                <div class="scelta-singola selected" data="com"><p>COM</p></div>
                <div class="scelta-singola" data="IIM"><p>IIM</p></div>
                <div class="scelta-singola" data="poi"><p>POI</p></div>
                <div class="scelta-singola" data="VET"><p>VET</p></div>
                <div class="scelta-singola" data="euro"><p>EURO</p></div>
                <div class="scelta-singola" data="porti"><p>PORTI</p></div>
                <!--<div class="scelta-singola" data="other"><p>OTHER</p></div>-->
                
              </div>
    '; */ $markup="";
    $markup .= '<div id="mapid-com" class="mapid"></div>';
    return array(
        '#type' => 'markup',
        '#markup' => $markup,
    );
  }
}


