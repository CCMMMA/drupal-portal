<?php

namespace Drupal\import_update_json\Controller;
use Drupal\Core\Controller\ControllerBase;

class ImportUpdateJsonController extends ControllerBase{
  public function import(){
    return array(
      '#title' => T('Import file json'),
      '#markup' => t('content'),
    );
  }
}