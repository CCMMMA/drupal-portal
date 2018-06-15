<?php

namespace Drupal\export_places_json\Controller;
use Drupal\Core\Controller\ControllerBase;

class ExportPlacesJsonController extends ControllerBase{
  public function export(){
    return array(
      '#title' => T('Export places in json file'),
      '#markup' => t('content'),
    );
  }
}